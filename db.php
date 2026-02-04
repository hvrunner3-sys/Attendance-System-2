<?php
/*
 * DATABASE CONNECTION & CORE BUSINESS LOGIC
 * Handles all database operations and application rules
 */

require_once __DIR__ . '/config.php';

/**
 * Create database connection
 * Returns: mysqli object or exits on failure
 */
function get_db() {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if ($db->connect_error) {
        error_log("Database connection failed: " . $db->connect_error);
        die('Database connection failed');
    }

    $db->set_charset("utf8mb4");
    return $db;
}

/**
 * ATTENDANCE FUNCTIONS
 */

/**
 * Get today's attendance for user
 */
function get_today_attendance($db, $user_id) {
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT * FROM attendance
        WHERE user_id = ? AND DATE(punch_in_time) = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Record punch in
 */
function punch_in($db, $user_id, $slot, $latitude, $longitude, $photo_path) {
    // Check if already punched in today
    $today_attendance = get_today_attendance($db, $user_id);

    if ($today_attendance && $today_attendance['punch_in_time']) {
        return [
            'success' => false,
            'message' => 'Already punched in today'
        ];
    }

    // Verify geofence for office slot
    if ($slot === SLOT_OFFICE) {
        $distance = calculate_distance(OFFICE_LAT, OFFICE_LNG, $latitude, $longitude);
        if ($distance > OFFICE_RADIUS) {
            return [
                'success' => false,
                'message' => 'You are outside office geofence. Distance: ' . round($distance) . 'm'
            ];
        }
    }

    // Record punch in
    $punch_in_time = get_server_time_string();
    $stmt = $db->prepare("
        INSERT INTO attendance (user_id, slot, punch_in_time, punch_in_latitude, punch_in_longitude, punch_in_photo)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issdds", $user_id, $slot, $punch_in_time, $latitude, $longitude, $photo_path);

    if ($stmt->execute()) {
        log_action($db, $user_id, 'PUNCH_IN', "Punched in as $slot", $stmt->insert_id);

        return [
            'success' => true,
            'message' => 'Punched in successfully',
            'attendance_id' => $stmt->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to record punch in'
        ];
    }
}

/**
 * Record punch out
 */
function punch_out($db, $user_id, $work_summary, $latitude, $longitude, $photo_path) {
    // Get today's attendance
    $today_attendance = get_today_attendance($db, $user_id);

    if (!$today_attendance) {
        return [
            'success' => false,
            'message' => 'No punch in found for today'
        ];
    }

    if ($today_attendance['punch_out_time']) {
        return [
            'success' => false,
            'message' => 'Already punched out today'
        ];
    }

    // Work summary is mandatory for Office/WFH (not for Site Visit)
    if ($today_attendance['slot'] !== SLOT_SITE_VISIT && empty($work_summary)) {
        return [
            'success' => false,
            'message' => 'Work summary is mandatory'
        ];
    }

    // Record punch out
    $punch_out_time = get_server_time_string();
    $day_count = calculate_day_count($db, $user_id, $today_attendance);

    $stmt = $db->prepare("
        UPDATE attendance
        SET punch_out_time = ?, punch_out_latitude = ?, punch_out_longitude = ?,
            punch_out_photo = ?, work_summary = ?, day_count = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sddssii", $punch_out_time, $latitude, $longitude, $photo_path, $work_summary, $day_count, $today_attendance['id']);

    if ($stmt->execute()) {
        log_action($db, $user_id, 'PUNCH_OUT', "Punched out. Day: $day_count", $today_attendance['id']);

        return [
            'success' => true,
            'message' => 'Punched out successfully',
            'day_count' => $day_count
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to record punch out'
        ];
    }
}

/**
 * Convert to site visit
 */
function convert_to_site_visit($db, $user_id, $latitude, $longitude, $photo_path) {
    $today_attendance = get_today_attendance($db, $user_id);

    if (!$today_attendance) {
        return ['success' => false, 'message' => 'No punch in found'];
    }

    if ($today_attendance['slot'] === SLOT_SITE_VISIT) {
        return ['success' => false, 'message' => 'Already on site visit'];
    }

    // Convert to site visit
    $conversion_time = get_server_time_string();
    $stmt = $db->prepare("
        UPDATE attendance
        SET slot = ?, site_visit_time = ?, site_visit_latitude = ?, site_visit_longitude = ?, site_visit_photo = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssddsi", $slot = SLOT_SITE_VISIT, $conversion_time, $latitude, $longitude, $photo_path, $today_attendance['id']);

    if ($stmt->execute()) {
        log_action($db, $user_id, 'SITE_VISIT_CONVERT', "Converted to site visit", $today_attendance['id']);

        return [
            'success' => true,
            'message' => 'Converted to site visit',
            'attendance_id' => $today_attendance['id']
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to convert'];
    }
}

/**
 * Calculate day count based on business logic
 */
function calculate_day_count($db, $user_id, $attendance) {
    // PRIORITY 1: Site Visit Override
    if ($attendance['slot'] === SLOT_SITE_VISIT) {
        return 1; // Full day
    }

    $punch_in = new DateTime($attendance['punch_in_time'], new DateTimeZone('Asia/Kolkata'));
    $punch_out = new DateTime($attendance['punch_out_time'], new DateTimeZone('Asia/Kolkata'));

    // PRIORITY 2: Check for auto punch-out penalty (Office/WFH only)
    $is_auto_punch_out = check_auto_punch_out($db, $attendance['id']);
    if ($is_auto_punch_out && $attendance['slot'] !== SLOT_SITE_VISIT) {
        return 0; // 0 day for auto punch-out
    }

    // PRIORITY 3: Late Recovery Logic (10:00-10:15 + 7:00 PM)
    $punch_in_time_str = $punch_in->format('H:i');
    if ($punch_in_time_str >= LATE_START && $punch_in_time_str <= LATE_END) {
        // Late punch in - check recovery
        $punch_out_time_str = $punch_out->format('H:i');
        if ($punch_out_time_str >= RECOVERY_TIME) {
            return 1; // Full day (recovery achieved)
        }
    }

    // PRIORITY 4: Hour-Based Calculation (Office/WFH)
    $hours_worked = ($punch_out->getTimestamp() - $punch_in->getTimestamp()) / 3600;

    if ($hours_worked < HALF_DAY_MIN_HOURS) {
        return 0; // 0 day
    } elseif ($hours_worked < FULL_DAY_MIN_HOURS) {
        return 0.5; // Half day
    } else {
        return 1; // Full day
    }
}

/**
 * Check if attendance was auto-punched-out
 */
function check_auto_punch_out($db, $attendance_id) {
    $stmt = $db->prepare("SELECT is_auto_punch_out FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $attendance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['is_auto_punch_out'] ?? false;
}

/**
 * Auto punch-out users who forgot to punch out
 * Should be run via cron at 23:59 PM
 */
function auto_punch_out_system($db) {
    $today = date('Y-m-d');
    $auto_punch_time = get_server_time_string();

    // Find all users with punch in but no punch out today
    $stmt = $db->prepare("
        SELECT a.id, a.user_id, a.slot FROM attendance a
        WHERE DATE(a.punch_in_time) = ? AND a.punch_out_time IS NULL
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($attendance = $result->fetch_assoc()) {
        // Calculate day count BEFORE marking as auto punch-out
        $temp_attendance = $attendance;
        $temp_attendance['punch_out_time'] = $auto_punch_time;

        // For auto punch-out: Office/WFH = 0 day, Site Visit = full day
        if ($attendance['slot'] === SLOT_SITE_VISIT) {
            $day_count = 1; // Full day for site visit
        } else {
            $day_count = 0; // 0 day for office/WFH
        }

        // Update attendance with auto punch-out
        $update_stmt = $db->prepare("
            UPDATE attendance
            SET punch_out_time = ?, day_count = ?, is_auto_punch_out = 1,
                work_summary = 'Punch out automatically done by system'
            WHERE id = ?
        ");
        $update_stmt->bind_param("sii", $auto_punch_time, $day_count, $attendance['id']);
        $update_stmt->execute();

        log_action($db, $attendance['user_id'], 'AUTO_PUNCH_OUT', "Auto punch-out at $auto_punch_time", $attendance['id']);
    }

    return true;
}

/**
 * LEAVE FUNCTIONS
 */

/**
 * Apply leave
 */
function apply_leave($db, $user_id, $leave_type, $leave_date) {
    // Interns cannot apply leave
    $user = get_user_by_id($db, $user_id);
    if ($user['role'] === ROLE_INTERN) {
        return [
            'success' => false,
            'message' => 'Interns cannot apply for leave'
        ];
    }

    // Check if leave already exists for this date
    $stmt = $db->prepare("
        SELECT id FROM leaves
        WHERE user_id = ? AND leave_date = ? AND leave_type = ?
        LIMIT 1
    ");
    $stmt->bind_param("iss", $user_id, $leave_date, $leave_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Leave already applied for this date'
        ];
    }

    // Record leave
    $status = STATUS_PENDING;
    $applied_at = get_server_time_string();

    $stmt = $db->prepare("
        INSERT INTO leaves (user_id, leave_type, leave_date, status, applied_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $user_id, $leave_type, $leave_date, $status, $applied_at, $applied_at);

    if ($stmt->execute()) {
        log_action($db, $user_id, 'LEAVE_APPLIED', "Leave applied: $leave_type on $leave_date", $stmt->insert_id);

        return [
            'success' => true,
            'message' => 'Leave applied successfully',
            'leave_id' => $stmt->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to apply leave'
        ];
    }
}

/**
 * Check if user has approved leave for today
 */
function has_approved_leave_today($db, $user_id) {
    $today = date('Y-m-d');

    $stmt = $db->prepare("
        SELECT id FROM leaves
        WHERE user_id = ? AND leave_date = ? AND status = ?
        LIMIT 1
    ");
    $stmt->bind_param("iss", $user_id, $today, $status = STATUS_APPROVED);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * Get user leaves for month
 */
function get_user_leaves_for_month($db, $user_id, $year, $month) {
    $start_date = sprintf("%04d-%02d-01", $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $stmt = $db->prepare("
        SELECT * FROM leaves
        WHERE user_id = ? AND leave_date BETWEEN ? AND ? AND status = ?
        ORDER BY leave_date
    ");
    $stmt->bind_param("isss", $user_id, $start_date, $end_date, $status = STATUS_APPROVED);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * EXPENSE FUNCTIONS
 */

/**
 * Add expense
 */
function add_expense($db, $user_id, $amount, $description, $expense_date, $receipt_path = null) {
    $status = STATUS_PENDING;
    $created_at = get_server_time_string();

    $stmt = $db->prepare("
        INSERT INTO expenses (user_id, amount, description, expense_date, receipt_image, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ddsssss", $user_id, $amount, $description, $expense_date, $receipt_path, $status, $created_at, $created_at);

    if ($stmt->execute()) {
        log_action($db, $user_id, 'EXPENSE_ADDED', "Expense: $amount - $description", $stmt->insert_id);

        return [
            'success' => true,
            'message' => 'Expense added successfully',
            'expense_id' => $stmt->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add expense'
        ];
    }
}

/**
 * Get approved expenses for month
 */
function get_approved_expenses_for_month($db, $user_id, $year, $month) {
    $start_date = sprintf("%04d-%02d-01", $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $stmt = $db->prepare("
        SELECT SUM(amount) as total FROM expenses
        WHERE user_id = ? AND expense_date BETWEEN ? AND ? AND status = ?
    ");
    $stmt->bind_param("isss", $user_id, $start_date, $end_date, $status = STATUS_APPROVED);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

/**
 * UTILITY FUNCTIONS
 */

/**
 * Get user by ID
 */
function get_user_by_id($db, $user_id) {
    $stmt = $db->prepare("SELECT id, name, email, role, phone FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 */
function calculate_distance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth's radius in meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * asin(sqrt($a));
    $distance = $R * $c;

    return $distance;
}

/**
 * Get current month attendance summary
 */
function get_monthly_attendance_summary($db, $user_id, $year, $month) {
    $start_date = sprintf("%04d-%02d-01", $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_days,
            SUM(CASE WHEN day_count = 1 THEN 1 ELSE 0 END) as full_days,
            SUM(CASE WHEN day_count = 0.5 THEN 1 ELSE 0 END) as half_days,
            SUM(day_count) as total_days_worked
        FROM attendance
        WHERE user_id = ? AND DATE(punch_in_time) BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/**
 * Calculate salary for month
 */
function calculate_monthly_salary($db, $user_id, $year, $month) {
    $user = get_user_by_id($db, $user_id);

    // Get user salary data
    $stmt = $db->prepare("SELECT monthly_salary, stipend_amount, stipend_type FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $salary_data = $stmt->get_result()->fetch_assoc();

    // Get attendance summary
    $attendance = get_monthly_attendance_summary($db, $user_id, $year, $month);
    $approved_expenses = get_approved_expenses_for_month($db, $user_id, $year, $month);

    if ($user['role'] === ROLE_INTERN) {
        // Intern calculation
        if ($salary_data['stipend_type'] === STIPEND_HOURLY) {
            // Hourly intern: calculate based on hours worked
            $total_hours = $attendance['total_days_worked'] * 8; // Approximate hours
            $stipend = $total_hours * $salary_data['stipend_amount'];
        } else {
            // Fixed stipend
            $stipend = $salary_data['stipend_amount'];
        }

        return [
            'role' => 'Intern',
            'base_amount' => $stipend,
            'attendance_days' => $attendance['total_days_worked'],
            'expenses' => $approved_expenses,
            'gross_salary' => $stipend + $approved_expenses
        ];
    } else {
        // Employee calculation
        $per_day_salary = $salary_data['monthly_salary'] / 30;
        $attendance_salary = $attendance['total_days_worked'] * $per_day_salary;

        // Get approved leaves
        $start_date = sprintf("%04d-%02d-01", $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $stmt = $db->prepare("
            SELECT COUNT(*) as leave_count FROM leaves
            WHERE user_id = ? AND leave_date BETWEEN ? AND ? AND status = ?
        ");
        $stmt->bind_param("iss", $user_id, $start_date, $end_date, $status = STATUS_APPROVED);
        $stmt->execute();
        $leave_data = $stmt->get_result()->fetch_assoc();
        $leave_salary = $leave_data['leave_count'] * $per_day_salary;

        return [
            'role' => 'Employee',
            'base_salary' => $salary_data['monthly_salary'],
            'per_day_salary' => $per_day_salary,
            'attendance_days' => $attendance['total_days_worked'],
            'attendance_salary' => $attendance_salary,
            'leave_days' => $leave_data['leave_count'],
            'leave_salary' => $leave_salary,
            'expenses' => $approved_expenses,
            'gross_salary' => $attendance_salary + $leave_salary + $approved_expenses
        ];
    }
}

?>
