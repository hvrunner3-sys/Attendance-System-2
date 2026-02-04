<?php
/*
 * MAIN APPLICATION FILE
 * Login, Dashboard, Attendance, Admin - All routed here
 * This is the primary entry point for the entire application
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Initialize database
$db = get_db();

// Get route (default: login)
$route = $_GET['page'] ?? 'login';

// Handle logout
if ($route === 'logout') {
    logout_user($db);
}

// Determine which page to show
if (!is_logged_in()) {
    $route = 'login';
} elseif (is_admin() && $route === 'login') {
    $route = 'admin';
} elseif ($route === 'login') {
    $route = 'dashboard';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_NAME; ?> - Attendance & Payroll Management System">
    <meta name="theme-color" content="#1E40AF">

    <title><?php echo APP_NAME; ?></title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'><rect fill='%231E40AF' width='192' height='192'/><text x='50%' y='50%' font-size='96' fill='white' text-anchor='middle' dominant-baseline='central' font-weight='bold'>A</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 192 192'><rect fill='%231E40AF' width='192' height='192'/><text x='50%' y='50%' font-size='96' fill='white' text-anchor='middle' dominant-baseline='central' font-weight='bold'>A</text></svg>">

    <!-- Styles -->
    <link rel="stylesheet" href="style.css">

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js').catch(err => console.log('SW registration failed'));
        }
    </script>
</head>
<body>
    <!-- Mobile status bar background -->
    <div class="status-bar-bg"></div>

    <!-- Main App Container -->
    <div id="app" class="app-container">
        <?php

        // ============================================================
        // LOGIN PAGE
        // ============================================================
        if ($route === 'login'):
        ?>
            <div class="login-page">
                <div class="login-container">
                    <div class="login-logo">A+P</div>
                    <h1><?php echo APP_NAME; ?></h1>
                    <p class="login-subtitle">Attendance & Payroll Management</p>

                    <form id="loginForm" class="login-form" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label for="login_id">Employee ID / Email</label>
                            <input type="text" id="login_id" name="login_id" required placeholder="Enter your ID or email" autofocus>
                        </div>

                        <div class="form-group">
                            <label for="login_pin">PIN</label>
                            <input type="password" id="login_pin" name="login_pin" required placeholder="Enter 4-digit PIN">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <span>Sign In</span>
                        </button>

                        <div id="loginError" class="error-message" style="display: none;"></div>
                    </form>

                    <div class="login-footer">
                        <small>Contact admin for ID/PIN</small>
                    </div>
                </div>
            </div>

        <?php
        // ============================================================
        // EMPLOYEE DASHBOARD & ATTENDANCE
        // ============================================================
        elseif ($route === 'dashboard' || $route === ''):
            require_login();
            $current_user = get_current_user($db);
            $today = date('Y-m-d');
            $today_attendance = get_today_attendance($db, get_user_id());
            $has_leave = has_approved_leave_today($db, get_user_id());
        ?>
            <div class="dashboard-page">
                <!-- Header -->
                <header class="app-header">
                    <div class="header-title">
                        <h1>Dashboard</h1>
                        <p><?php echo date('l, M d'); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="notification-bell" onclick="toggleNotifications()" style="position: relative;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <?php
                            $unread_count = get_unread_notification_count($db, get_user_id());
                            if ($unread_count > 0):
                            ?>
                            <span class="notification-badge" id="notificationBadge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notificationDropdown" class="notification-dropdown">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                                <span class="mark-all-read" onclick="markAllNotificationsRead()">Mark all read</span>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <div class="notification-empty">Loading...</div>
                            </div>
                        </div>
                        <button class="btn-icon" onclick="toggleMenu()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="18" x2="21" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </header>

                <!-- Menu Drawer -->
                <div id="menuDrawer" class="menu-drawer">
                    <div class="menu-header">
                        <h3><?php echo htmlspecialchars($current_user['name']); ?></h3>
                        <p><?php echo htmlspecialchars($current_user['email']); ?></p>
                    </div>
                    <nav class="menu-nav">
                        <a href="?page=dashboard" class="menu-item active">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                            <span>Dashboard</span>
                        </a>
                        <a href="?page=attendance" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><path d="M20 8v6m3-3h-6"></path></svg>
                            <span>Attendance</span>
                        </a>
                        <a href="?page=leaves" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            <span>Leaves</span>
                        </a>
                        <a href="?page=expenses" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22m-7-5h14M3 8h18M5 8V3h14v5"></path></svg>
                            <span>Expenses</span>
                        </a>
                        <a href="?page=salary" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 11h.01"></path><path d="M12 20v-6"></path><path d="M8 20v-6"></path></svg>
                            <span>Salary</span>
                        </a>
                        <a href="?page=contacts" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Company Directory</span>
                        </a>
                        <?php if (is_admin()): ?>
                        <a href="?page=admin_management" class="menu-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"></path><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"></path></svg>
                            <span>Admin Management</span>
                        </a>
                        <?php endif; ?>
                        <hr>
                        <a href="?page=logout" class="menu-item text-danger">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4M16 17l5-5m0 0l-5-5m5 5H9"></path></svg>
                            <span>Logout</span>
                        </a>
                    </nav>
                </div>

                <!-- Content -->
                <main class="app-main">
                    <?php if ($has_leave): ?>
                        <div class="alert alert-info">
                            <strong>Today you are on approved leave</strong>
                        </div>
                    <?php else: ?>
                        <!-- Attendance Status -->
                        <section class="card attendance-card">
                            <h2>Attendance Status</h2>

                            <?php if ($today_attendance && $today_attendance['punch_in_time']): ?>
                                <div class="attendance-status">
                                    <div class="status-row">
                                        <span class="status-label">Punch In:</span>
                                        <span class="status-value"><?php echo date('H:i', strtotime($today_attendance['punch_in_time'])); ?></span>
                                    </div>

                                    <?php if ($today_attendance['slot']): ?>
                                        <div class="status-row">
                                            <span class="status-label">Slot:</span>
                                            <span class="status-value"><?php echo ucfirst($today_attendance['slot']); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($today_attendance['punch_out_time']): ?>
                                        <div class="status-row">
                                            <span class="status-label">Punch Out:</span>
                                            <span class="status-value"><?php echo date('H:i', strtotime($today_attendance['punch_out_time'])); ?></span>
                                        </div>
                                        <div class="status-row">
                                            <span class="status-label">Day Count:</span>
                                            <span class="status-value"><?php echo $today_attendance['day_count'] == 1 ? 'Full Day' : ($today_attendance['day_count'] == 0.5 ? 'Half Day' : '0 Day'); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-live">
                                            <span class="live-indicator"></span>
                                            Punch in ongoing...
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Site Visit Button (Only shown for Office slot without punch out) -->
                                <?php if ($today_attendance['slot'] === 'office' && !$today_attendance['punch_out_time']): ?>
                                    <button class="btn btn-secondary btn-block" onclick="startSiteVisit()">
                                        I am going for Site Visit
                                    </button>
                                <?php endif; ?>

                                <!-- Punch Out Button (Only if punched in but not punched out) -->
                                <?php if (!$today_attendance['punch_out_time']): ?>
                                    <button class="btn btn-danger btn-block" onclick="showPunchOutModal()">
                                        Punch Out
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No punch in yet today</p>
                                <button class="btn btn-primary btn-block" onclick="showPunchInModal()">
                                    Punch In
                                </button>
                            <?php endif; ?>
                        </section>

                        <!-- Monthly Summary -->
                        <?php
                        $month_summary = get_monthly_attendance_summary($db, get_user_id(), date('Y'), date('m'));
                        ?>
                        <section class="card">
                            <h2>This Month</h2>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-label">Full Days</span>
                                    <span class="stat-value"><?php echo $month_summary['full_days'] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Half Days</span>
                                    <span class="stat-value"><?php echo $month_summary['half_days'] ?? 0; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Days Worked</span>
                                    <span class="stat-value"><?php echo round($month_summary['total_days_worked'] ?? 0, 1); ?></span>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </main>
            </div>

        <?php
        // ============================================================
        // ATTENDANCE PAGE
        // ============================================================
        elseif ($route === 'attendance'):
            require_login();
            $current_user = get_current_user($db);
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=dashboard" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>Attendance History</h1>
                </header>

                <main class="app-main">
                    <?php
                    $year = $_GET['year'] ?? date('Y');
                    $month = $_GET['month'] ?? date('m');
                    $current_date = sprintf("%04d-%02d", $year, $month);

                    $stmt = $db->prepare("
                        SELECT * FROM attendance
                        WHERE user_id = ? AND DATE(punch_in_time) LIKE ?
                        ORDER BY punch_in_time DESC
                    ");
                    $like_pattern = $current_date . '%';
                    $stmt->bind_param("is", $user_id = get_user_id(), $like_pattern);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $records = $result->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <section class="card">
                        <div class="month-selector">
                            <button onclick="prevMonth()" class="btn-icon">←</button>
                            <span><?php echo date('M Y', strtotime("$current_date-01")); ?></span>
                            <button onclick="nextMonth()" class="btn-icon">→</button>
                        </div>
                    </section>

                    <?php if (count($records) > 0): ?>
                        <section class="attendance-list">
                            <?php foreach ($records as $record): ?>
                                <div class="attendance-item">
                                    <div class="attendance-date">
                                        <?php echo date('M d (D)', strtotime($record['punch_in_time'])); ?>
                                    </div>
                                    <div class="attendance-details">
                                        <div class="detail-row">
                                            <span class="label">Slot:</span>
                                            <span class="value"><?php echo ucfirst($record['slot']); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">In:</span>
                                            <span class="value"><?php echo date('H:i', strtotime($record['punch_in_time'])); ?></span>
                                        </div>
                                        <?php if ($record['punch_out_time']): ?>
                                            <div class="detail-row">
                                                <span class="label">Out:</span>
                                                <span class="value"><?php echo date('H:i', strtotime($record['punch_out_time'])); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">Day:</span>
                                                <span class="value"><?php echo $record['day_count'] == 1 ? 'Full' : ($record['day_count'] == 0.5 ? 'Half' : '0'); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="detail-row">
                                                <span class="label">Status:</span>
                                                <span class="value live">Ongoing</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No attendance records for this month</p>
                        </div>
                    <?php endif; ?>
                </main>
            </div>

        <?php
        // ============================================================
        // LEAVES PAGE
        // ============================================================
        elseif ($route === 'leaves'):
            require_login();
            if (is_intern()) {
                echo '<div class="alert alert-error">Interns cannot apply for leave</div>';
            } else {
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=dashboard" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>Manage Leaves</h1>
                </header>

                <main class="app-main">
                    <section class="card">
                        <h2>Apply Leave</h2>
                        <form onsubmit="applyLeave(event)">
                            <div class="form-group">
                                <label for="leave_type">Leave Type</label>
                                <select id="leave_type" name="leave_type" required>
                                    <option value="">Select leave type</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="casual">Casual Leave</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="leave_date">Date</label>
                                <input type="date" id="leave_date" name="leave_date" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Apply Leave</button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>Leave History</h2>
                        <?php
                        $stmt = $db->prepare("
                            SELECT * FROM leaves
                            WHERE user_id = ?
                            ORDER BY leave_date DESC
                            LIMIT 20
                        ");
                        $stmt->bind_param("i", $user_id = get_user_id());
                        $stmt->execute();
                        $leaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <?php if (count($leaves) > 0): ?>
                            <div class="leaves-list">
                                <?php foreach ($leaves as $leave): ?>
                                    <div class="leave-item">
                                        <div class="leave-date"><?php echo date('M d, Y', strtotime($leave['leave_date'])); ?></div>
                                        <div class="leave-type"><?php echo ucfirst($leave['leave_type']); ?> Leave</div>
                                        <div class="leave-status">
                                            <span class="badge badge-<?php echo $leave['status']; ?>">
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No leave applications</p>
                        <?php endif; ?>
                    </section>
                </main>
            </div>
        <?php } ?>

        <?php
        // ============================================================
        // EXPENSES PAGE
        // ============================================================
        elseif ($route === 'expenses'):
            require_login();
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=dashboard" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>My Expenses</h1>
                </header>

                <main class="app-main">
                    <section class="card">
                        <h2>Add Expense</h2>
                        <form onsubmit="addExpense(event)">
                            <div class="form-group">
                                <label for="exp_amount">Amount (₹)</label>
                                <input type="number" id="exp_amount" name="amount" step="0.01" required>
                            </div>

                            <div class="form-group">
                                <label for="exp_date">Date</label>
                                <input type="date" id="exp_date" name="expense_date" required>
                            </div>

                            <div class="form-group">
                                <label for="exp_desc">Description</label>
                                <textarea id="exp_desc" name="description" required placeholder="What was this expense for?"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="exp_receipt">Receipt (optional)</label>
                                <input type="file" id="exp_receipt" name="receipt_image" accept="image/*">
                                <small>Optional: Upload receipt photo</small>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Add Expense</button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>My Expenses</h2>
                        <?php
                        $stmt = $db->prepare("
                            SELECT * FROM expenses
                            WHERE user_id = ?
                            ORDER BY expense_date DESC
                            LIMIT 50
                        ");
                        $stmt->bind_param("i", $user_id = get_user_id());
                        $stmt->execute();
                        $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <?php if (count($expenses) > 0): ?>
                            <div class="expenses-list">
                                <?php foreach ($expenses as $exp): ?>
                                    <div class="expense-item">
                                        <div class="exp-date"><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?></div>
                                        <div class="exp-desc"><?php echo htmlspecialchars($exp['description']); ?></div>
                                        <div class="exp-footer">
                                            <span class="exp-amount">₹ <?php echo number_format($exp['amount'], 2); ?></span>
                                            <span class="badge badge-<?php echo $exp['status']; ?>">
                                                <?php echo ucfirst($exp['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No expenses added</p>
                        <?php endif; ?>
                    </section>
                </main>
            </div>

        <?php
        // ============================================================
        // SALARY PAGE
        // ============================================================
        elseif ($route === 'salary'):
            require_login();
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=dashboard" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>Salary Slip</h1>
                </header>

                <main class="app-main">
                    <?php
                    $year = $_GET['year'] ?? date('Y');
                    $month = $_GET['month'] ?? date('m');
                    $salary = calculate_monthly_salary($db, get_user_id(), $year, $month);
                    ?>

                    <section class="card salary-card">
                        <h2>Salary for <?php echo date('M Y', mktime(0, 0, 0, $month, 1, $year)); ?></h2>

                        <div class="salary-details">
                            <?php if ($salary['role'] === 'Employee'): ?>
                                <div class="salary-row">
                                    <span class="label">Base Salary:</span>
                                    <span class="value">₹ <?php echo number_format($salary['base_salary'], 2); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Per Day Salary:</span>
                                    <span class="value">₹ <?php echo number_format($salary['per_day_salary'], 2); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Attendance Days:</span>
                                    <span class="value"><?php echo round($salary['attendance_days'], 1); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Attendance Salary:</span>
                                    <span class="value">₹ <?php echo number_format($salary['attendance_salary'], 2); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Leave Days:</span>
                                    <span class="value"><?php echo $salary['leave_days']; ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Leave Salary:</span>
                                    <span class="value">₹ <?php echo number_format($salary['leave_salary'], 2); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Approved Expenses:</span>
                                    <span class="value">₹ <?php echo number_format($salary['expenses'], 2); ?></span>
                                </div>
                                <hr>
                                <div class="salary-row salary-total">
                                    <span class="label">Gross Salary:</span>
                                    <span class="value">₹ <?php echo number_format($salary['gross_salary'], 2); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="salary-row">
                                    <span class="label">Stipend Type:</span>
                                    <span class="value"><?php echo ucfirst($salary['role']); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Base Amount:</span>
                                    <span class="value">₹ <?php echo number_format($salary['base_amount'], 2); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Attendance Days:</span>
                                    <span class="value"><?php echo round($salary['attendance_days'], 1); ?></span>
                                </div>
                                <div class="salary-row">
                                    <span class="label">Approved Expenses:</span>
                                    <span class="value">₹ <?php echo number_format($salary['expenses'], 2); ?></span>
                                </div>
                                <hr>
                                <div class="salary-row salary-total">
                                    <span class="label">Gross Stipend:</span>
                                    <span class="value">₹ <?php echo number_format($salary['gross_salary'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </main>
            </div>

        <?php
        // ============================================================
        // COMPANY DIRECTORY PAGE
        // ============================================================
        elseif ($route === 'contacts'):
            require_login();
            $contacts = get_all_contacts($db, false);
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=dashboard" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>Company Directory</h1>
                </header>

                <main class="app-main">
                    <?php if (is_admin()): ?>
                    <section class="card">
                        <h2>Add New Contact</h2>
                        <form onsubmit="addContact(event)">
                            <div class="form-group">
                                <label for="contact_name">Name</label>
                                <input type="text" id="contact_name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_role">Role/Department</label>
                                <input type="text" id="contact_role" name="role" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_phone">Phone</label>
                                <input type="tel" id="contact_phone" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label for="contact_email">Email (optional)</label>
                                <input type="email" id="contact_email" name="email">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Add Contact</button>
                        </form>
                    </section>
                    <?php endif; ?>

                    <section class="card">
                        <h2>Contact List</h2>
                        <?php if (count($contacts) > 0): ?>
                            <div class="contact-list" id="contactList">
                                <?php foreach ($contacts as $contact): ?>
                                    <div class="contact-item" data-id="<?php echo $contact['id']; ?>">
                                        <div class="contact-info">
                                            <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                                            <p><?php echo htmlspecialchars($contact['role']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($contact['phone']); ?></p>
                                            <?php if ($contact['email']): ?>
                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($contact['email']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (is_admin()): ?>
                                        <div class="contact-actions">
                                            <button class="btn btn-sm btn-secondary" onclick="editContact(<?php echo $contact['id']; ?>, '<?php echo addslashes($contact['name']); ?>', '<?php echo addslashes($contact['role']); ?>', '<?php echo addslashes($contact['phone']); ?>', '<?php echo addslashes($contact['email']); ?>')">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteContact(<?php echo $contact['id']; ?>)">Delete</button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No contacts available</p>
                        <?php endif; ?>
                    </section>
                </main>
            </div>

        <?php
        // ============================================================
        // ADMIN MANAGEMENT PAGE (Admin Only)
        // ============================================================
        elseif ($route === 'admin_management'):
            require_admin();
            $admins = get_all_admins($db);
        ?>
            <div class="page-container">
                <header class="app-header">
                    <a href="?page=admin" class="btn-back">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </a>
                    <h1>Admin Management</h1>
                </header>

                <main class="app-main">
                    <section class="card">
                        <h2>Add New Admin</h2>
                        <form onsubmit="addAdmin(event)">
                            <div class="form-group">
                                <label for="admin_name">Name</label>
                                <input type="text" id="admin_name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="admin_email">Email</label>
                                <input type="email" id="admin_email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="admin_phone">Phone</label>
                                <input type="tel" id="admin_phone" name="phone" required>
                            </div>

                            <div class="form-group">
                                <label for="admin_pin">PIN (4 digits)</label>
                                <input type="password" id="admin_pin" name="pin" required pattern="[0-9]{4}" maxlength="4" placeholder="4-digit PIN">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Add Admin</button>
                        </form>
                    </section>

                    <section class="card">
                        <h2>All Administrators</h2>
                        <?php if (count($admins) > 0): ?>
                            <div class="admin-list">
                                <?php foreach ($admins as $admin): ?>
                                    <div class="admin-item">
                                        <div class="admin-info">
                                            <h3><?php echo htmlspecialchars($admin['name']); ?></h3>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($admin['phone']); ?></p>
                                            <p><small>Added: <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></small></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No administrators found</p>
                        <?php endif; ?>
                    </section>
                </main>
            </div>

        <?php
        // ============================================================
        // ADMIN PANEL
        // ============================================================
        elseif ($route === 'admin'):
            require_admin();
        ?>
            <div class="admin-page">
                <header class="app-header">
                    <h1>Admin Panel</h1>
                    <div class="header-actions">
                        <button class="btn-icon" onclick="toggleAdminMenu()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="19" cy="12" r="1"></circle>
                                <circle cx="5" cy="12" r="1"></circle>
                            </svg>
                        </button>
                    </div>
                </header>

                <main class="app-main">
                    <section class="card">
                        <h2>Pending Approvals</h2>

                        <!-- Pending Leaves -->
                        <?php
                        $stmt = $db->prepare("
                            SELECT l.*, u.name FROM leaves l
                            JOIN users u ON u.id = l.user_id
                            WHERE l.status = ?
                            ORDER BY l.applied_at DESC
                        ");
                        $stmt->bind_param("s", $status = STATUS_PENDING);
                        $stmt->execute();
                        $pending_leaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <?php if (count($pending_leaves) > 0): ?>
                            <h3 style="margin-top: 20px;">Pending Leaves</h3>
                            <div class="approvals-list">
                                <?php foreach ($pending_leaves as $leave): ?>
                                    <div class="approval-item">
                                        <div class="approval-info">
                                            <strong><?php echo htmlspecialchars($leave['name']); ?></strong>
                                            <p><?php echo date('M d, Y', strtotime($leave['leave_date'])); ?> - <?php echo ucfirst($leave['leave_type']); ?> Leave</p>
                                        </div>
                                        <div class="approval-actions">
                                            <button class="btn btn-sm btn-success" onclick="approveLeave(<?php echo $leave['id']; ?>)">Approve</button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectLeave(<?php echo $leave['id']; ?>)">Reject</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pending Expenses -->
                        <?php
                        $stmt = $db->prepare("
                            SELECT e.*, u.name FROM expenses e
                            JOIN users u ON u.id = e.user_id
                            WHERE e.status = ?
                            ORDER BY e.created_at DESC
                        ");
                        $stmt->bind_param("s", $status = STATUS_PENDING);
                        $stmt->execute();
                        $pending_expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <?php if (count($pending_expenses) > 0): ?>
                            <h3 style="margin-top: 20px;">Pending Expenses</h3>
                            <div class="approvals-list">
                                <?php foreach ($pending_expenses as $exp): ?>
                                    <div class="approval-item">
                                        <div class="approval-info">
                                            <strong><?php echo htmlspecialchars($exp['name']); ?></strong>
                                            <p>₹ <?php echo number_format($exp['amount'], 2); ?> - <?php echo htmlspecialchars($exp['description']); ?></p>
                                        </div>
                                        <div class="approval-actions">
                                            <button class="btn btn-sm btn-success" onclick="approveExpense(<?php echo $exp['id']; ?>)">Approve</button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectExpense(<?php echo $exp['id']; ?>)">Reject</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (count($pending_leaves) === 0 && count($pending_expenses) === 0): ?>
                            <p class="text-muted">No pending approvals</p>
                        <?php endif; ?>
                    </section>

                    <section class="card">
                        <h2>Team Attendance</h2>
                        <?php
                        $today = date('Y-m-d');
                        $stmt = $db->prepare("
                            SELECT u.id, u.name, u.role, a.day_count, a.slot
                            FROM users u
                            LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.punch_in_time) = ?
                            WHERE u.role != ?
                            ORDER BY u.name
                        ");
                        $stmt->bind_param("ss", $today, $role = ROLE_ADMIN);
                        $stmt->execute();
                        $team_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                        <div class="team-attendance-list">
                            <?php foreach ($team_attendance as $member): ?>
                                <div class="team-member-item">
                                    <div class="member-info">
                                        <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                        <small><?php echo ucfirst($member['role']); ?></small>
                                    </div>
                                    <div class="member-status">
                                        <?php if ($member['day_count'] !== null): ?>
                                            <span class="badge"><?php echo $member['slot'] ? ucfirst($member['slot']) : 'N/A'; ?></span>
                                            <span class="day-count"><?php echo $member['day_count'] == 1 ? 'Full' : ($member['day_count'] == 0.5 ? 'Half' : '0'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No punch in</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <div class="admin-footer">
                        <a href="?page=logout" class="btn btn-danger btn-block">Logout</a>
                    </div>
                </main>
            </div>

        <?php
        // ============================================================
        // 404 - PAGE NOT FOUND
        // ============================================================
        else:
        ?>
            <div class="error-page">
                <h1>404</h1>
                <p>Page not found</p>
                <a href="?page=dashboard" class="btn btn-primary">Go to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODALS -->

    <!-- Punch In Modal -->
    <div id="punchInModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Punch In</h2>
                <button class="btn-close" onclick="closePunchInModal()">×</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handlePunchIn(event)" id="punchInForm">
                    <div class="form-group">
                        <label for="slot">Select Work Location</label>
                        <select id="slot" name="slot" required>
                            <option value="">Choose...</option>
                            <option value="office">Office</option>
                            <option value="wfh">Work From Home (WFH)</option>
                            <option value="site_visit">Site Visit</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-secondary btn-block" onclick="takePunchInPhoto()">
                        Take Live Photo
                    </button>

                    <div id="photoPreview"></div>

                    <button type="submit" class="btn btn-primary btn-block" disabled id="punchInSubmit">
                        Punch In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Punch Out Modal -->
    <div id="punchOutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Punch Out</h2>
                <button class="btn-close" onclick="closePunchOutModal()">×</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handlePunchOut(event)" id="punchOutForm">
                    <div class="form-group">
                        <label for="work_summary">Today's Work Summary (Points)</label>
                        <textarea id="work_summary" name="work_summary" required placeholder="What did you accomplish today?"></textarea>
                    </div>

                    <button type="button" class="btn btn-secondary btn-block" onclick="takePunchOutPhoto()">
                        Take Live Photo
                    </button>

                    <div id="outPhotoPreview"></div>

                    <button type="submit" class="btn btn-primary btn-block" disabled id="punchOutSubmit">
                        Punch Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Camera Modal (Full Screen) -->
    <div id="cameraModal" class="camera-modal">
        <div class="camera-header">
            <button onclick="closeCameraModal()" class="btn-icon">←</button>
            <span id="cameraTitle">Camera</span>
            <button onclick="toggleCameraFlash()" class="btn-icon">🔦</button>
        </div>
        <video id="cameraVideo" playsinline></video>
        <div class="camera-controls">
            <button onclick="capturePhoto()" class="btn btn-capture">📷</button>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="loading-indicator" style="display: none;">
        <div class="spinner"></div>
        <p id="loadingText">Processing...</p>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Scripts -->
    <script src="app.js"></script>
</body>
</html>
