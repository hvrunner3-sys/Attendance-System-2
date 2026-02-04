<?php
/*
 * CONFIGURATION FILE
 * All settings, database config, constants in ONE place
 * This is the ONLY configuration file for the entire application
 */

// ERROR HANDLING - PRODUCTION SAFE
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// TIMEZONE
date_default_timezone_set('Asia/Kolkata');

// SESSION CONFIGURATION
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// DATABASE CONFIGURATION
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_system');
define('DB_PORT', 3306);

// APPLICATION CONSTANTS
define('APP_NAME', 'Attendance & Payroll System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// OFFICE GEOFENCE (Latitude, Longitude, Radius in meters)
define('OFFICE_LAT', 28.6139);
define('OFFICE_LNG', 77.2090);
define('OFFICE_RADIUS', 100); // 100 meters

// OFFICE HOURS
define('OFFICE_PUNCH_IN', '10:00');
define('OFFICE_PUNCH_OUT', '18:00');
define('RECOVERY_TIME', '19:00'); // 7:00 PM recovery time

// LATE WINDOW (minutes)
define('LATE_START', '10:00');
define('LATE_END', '10:15');

// AUTO PUNCH-OUT TIME
define('AUTO_PUNCH_OUT_TIME', '23:59');

// LEAVE ALLOCATION (every 3 months)
define('SICK_LEAVE_QUARTERLY', 2);
define('CASUAL_LEAVE_QUARTERLY', 2);

// DAY CALCULATION
define('HALF_DAY_MIN_HOURS', 4);
define('FULL_DAY_MIN_HOURS', 8);

// ROLES
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');
define('ROLE_INTERN', 'intern');

// ROLES ARRAY
$ROLES = [
    ROLE_ADMIN => 'Admin',
    ROLE_EMPLOYEE => 'Employee',
    ROLE_INTERN => 'Intern'
];

// ATTENDANCE SLOTS
define('SLOT_OFFICE', 'office');
define('SLOT_WFH', 'wfh');
define('SLOT_SITE_VISIT', 'site_visit');

$SLOTS = [
    SLOT_OFFICE => 'Office',
    SLOT_WFH => 'Work From Home',
    SLOT_SITE_VISIT => 'Site Visit'
];

// LEAVE TYPES
define('LEAVE_SICK', 'sick');
define('LEAVE_CASUAL', 'casual');

$LEAVE_TYPES = [
    LEAVE_SICK => 'Sick Leave',
    LEAVE_CASUAL => 'Casual Leave'
];

// STATUS CONSTANTS
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');

$STATUSES = [
    STATUS_PENDING => 'Pending',
    STATUS_APPROVED => 'Approved',
    STATUS_REJECTED => 'Rejected'
];

// EXPENSE STATUSES
define('EXPENSE_PENDING', 'pending');
define('EXPENSE_APPROVED', 'approved');
define('EXPENSE_REJECTED', 'rejected');

// STIPEND TYPES
define('STIPEND_HOURLY', 'hourly');
define('STIPEND_FIXED', 'fixed');

$STIPEND_TYPES = [
    STIPEND_HOURLY => 'Hourly',
    STIPEND_FIXED => 'Fixed Monthly'
];

// INTERN TYPES
define('INTERN_TYPE_HOURLY', 'hourly');
define('INTERN_TYPE_FIXED', 'fixed');

// ENSURE UPLOAD DIRECTORY EXISTS
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// CREATE SUBDIRECTORIES
$upload_dirs = ['photos', 'expenses'];
foreach ($upload_dirs as $dir) {
    $full_path = UPLOAD_DIR . '/' . $dir;
    if (!is_dir($full_path)) {
        mkdir($full_path, 0755, true);
    }
}

// PREVENT DIRECT FILE ACCESS TO UPLOADS (for security)
// This would be handled by .htaccess on Apache servers

?>
