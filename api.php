<?php
/*
 * API ENDPOINTS
 * Handles all AJAX requests from frontend
 * Returns JSON responses
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Initialize database
$db = get_db();

// Get action parameter
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// ============================================================
// LOGIN ENDPOINT
// ============================================================
if ($action === 'login') {
    $login_id = $_POST['login_id'] ?? '';
    $pin = $_POST['pin'] ?? '';

    $result = login_user($db, $login_id, $pin);
    echo json_encode($result);
    exit;
}

// ============================================================
// ALL FOLLOWING ENDPOINTS REQUIRE LOGIN
// ============================================================
require_login();

// ============================================================
// ATTENDANCE ENDPOINTS
// ============================================================

// Punch In
if ($action === 'punch_in') {
    $slot = $_POST['slot'] ?? '';
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;
    $photo = $_FILES['photo'] ?? null;

    // Validate slot
    if (!in_array($slot, [SLOT_OFFICE, SLOT_WFH, SLOT_SITE_VISIT])) {
        echo json_encode(['success' => false, 'message' => 'Invalid slot']);
        exit;
    }

    // Handle photo upload
    $photo_path = null;
    if ($photo && $photo['size'] > 0) {
        $photo_name = 'punch_' . get_user_id() . '_in_' . time() . '.jpg';
        $upload_path = UPLOAD_DIR . '/photos/' . $photo_name;

        if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
            $photo_path = UPLOAD_URL . '/photos/' . $photo_name;
        }
    }

    $result = punch_in($db, get_user_id(), $slot, $latitude, $longitude, $photo_path);
    echo json_encode($result);
    exit;
}

// Punch Out
if ($action === 'punch_out') {
    $work_summary = $_POST['work_summary'] ?? '';
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;
    $photo = $_FILES['photo'] ?? null;

    // Handle photo upload
    $photo_path = null;
    if ($photo && $photo['size'] > 0) {
        $photo_name = 'punch_' . get_user_id() . '_out_' . time() . '.jpg';
        $upload_path = UPLOAD_DIR . '/photos/' . $photo_name;

        if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
            $photo_path = UPLOAD_URL . '/photos/' . $photo_name;
        }
    }

    $result = punch_out($db, get_user_id(), $work_summary, $latitude, $longitude, $photo_path);
    echo json_encode($result);
    exit;
}

// Convert to Site Visit
if ($action === 'convert_site_visit') {
    $latitude = $_POST['latitude'] ?? 0;
    $longitude = $_POST['longitude'] ?? 0;
    $photo = $_FILES['photo'] ?? null;

    // Handle photo upload
    $photo_path = null;
    if ($photo && $photo['size'] > 0) {
        $photo_name = 'site_visit_' . get_user_id() . '_' . time() . '.jpg';
        $upload_path = UPLOAD_DIR . '/photos/' . $photo_name;

        if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
            $photo_path = UPLOAD_URL . '/photos/' . $photo_name;
        }
    }

    $result = convert_to_site_visit($db, get_user_id(), $latitude, $longitude, $photo_path);
    echo json_encode($result);
    exit;
}

// ============================================================
// LEAVE ENDPOINTS
// ============================================================

// Apply Leave
if ($action === 'apply_leave') {
    $leave_type = $_POST['leave_type'] ?? '';
    $leave_date = $_POST['leave_date'] ?? '';

    $result = apply_leave($db, get_user_id(), $leave_type, $leave_date);
    echo json_encode($result);
    exit;
}

// ============================================================
// EXPENSE ENDPOINTS
// ============================================================

// Add Expense
if ($action === 'add_expense') {
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $receipt = $_FILES['receipt_image'] ?? null;

    // Handle receipt upload
    $receipt_path = null;
    if ($receipt && $receipt['size'] > 0) {
        $receipt_name = 'expense_' . get_user_id() . '_' . time() . '.jpg';
        $upload_path = UPLOAD_DIR . '/expenses/' . $receipt_name;

        if (move_uploaded_file($receipt['tmp_name'], $upload_path)) {
            $receipt_path = UPLOAD_URL . '/expenses/' . $receipt_name;
        }
    }

    $result = add_expense($db, get_user_id(), $amount, $description, $expense_date, $receipt_path);
    echo json_encode($result);
    exit;
}

// ============================================================
// ADMIN ENDPOINTS
// ============================================================

if (is_admin()) {
    // Approve Leave
    if ($action === 'approve_leave') {
        $leave_id = $_POST['leave_id'] ?? 0;

        $stmt = $db->prepare("UPDATE leaves SET status = ?, updated_at = ? WHERE id = ?");
        $status = STATUS_APPROVED;
        $timestamp = get_server_time_string();
        $stmt->bind_param("ssi", $status, $timestamp, $leave_id);

        if ($stmt->execute()) {
            // Log action
            $leave_stmt = $db->prepare("SELECT user_id FROM leaves WHERE id = ?");
            $leave_stmt->bind_param("i", $leave_id);
            $leave_stmt->execute();
            $leave = $leave_stmt->get_result()->fetch_assoc();
            log_action($db, get_user_id(), 'LEAVE_APPROVED', "Leave approved", $leave_id);

            echo json_encode(['success' => true, 'message' => 'Leave approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve leave']);
        }
        exit;
    }

    // Reject Leave
    if ($action === 'reject_leave') {
        $leave_id = $_POST['leave_id'] ?? 0;

        $stmt = $db->prepare("UPDATE leaves SET status = ?, updated_at = ? WHERE id = ?");
        $status = STATUS_REJECTED;
        $timestamp = get_server_time_string();
        $stmt->bind_param("ssi", $status, $timestamp, $leave_id);

        if ($stmt->execute()) {
            log_action($db, get_user_id(), 'LEAVE_REJECTED', "Leave rejected", $leave_id);
            echo json_encode(['success' => true, 'message' => 'Leave rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject leave']);
        }
        exit;
    }

    // Approve Expense
    if ($action === 'approve_expense') {
        $expense_id = $_POST['expense_id'] ?? 0;

        $stmt = $db->prepare("UPDATE expenses SET status = ?, updated_at = ? WHERE id = ?");
        $status = STATUS_APPROVED;
        $timestamp = get_server_time_string();
        $stmt->bind_param("ssi", $status, $timestamp, $expense_id);

        if ($stmt->execute()) {
            log_action($db, get_user_id(), 'EXPENSE_APPROVED', "Expense approved", $expense_id);
            echo json_encode(['success' => true, 'message' => 'Expense approved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve expense']);
        }
        exit;
    }

    // Reject Expense
    if ($action === 'reject_expense') {
        $expense_id = $_POST['expense_id'] ?? 0;

        $stmt = $db->prepare("UPDATE expenses SET status = ?, updated_at = ? WHERE id = ?");
        $status = STATUS_REJECTED;
        $timestamp = get_server_time_string();
        $stmt->bind_param("ssi", $status, $timestamp, $expense_id);

        if ($stmt->execute()) {
            log_action($db, get_user_id(), 'EXPENSE_REJECTED', "Expense rejected", $expense_id);
            echo json_encode(['success' => true, 'message' => 'Expense rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject expense']);
        }
        exit;
    }
}

// ============================================================
// 404 - Action not found
// ============================================================
echo json_encode(['success' => false, 'message' => 'Invalid action']);

?>
