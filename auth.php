<?php
/*
 * AUTHENTICATION & SESSION MANAGEMENT
 * Handles login, logout, session validation, user info
 */

// Ensure config is loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Check if user is logged in
 * Returns: true/false
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Get current logged-in user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged-in user role
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current logged-in user details
 */
function get_current_user($db) {
    if (!is_logged_in()) {
        return null;
    }

    $user_id = get_user_id();

    $stmt = $db->prepare("SELECT id, name, email, role, phone FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

/**
 * Login user with ID/Email and PIN
 * Returns: array with success/error
 */
function login_user($db, $login_id, $pin) {
    // Find user by ID or email
    $stmt = $db->prepare("SELECT id, name, email, role, pin FROM users WHERE (id = ? OR email = ?) AND pin = ? LIMIT 1");
    $stmt->bind_param("sss", $login_id, $login_id, $pin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Invalid ID/Email or PIN'
        ];
    }

    $user = $result->fetch_assoc();

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['login_time'] = time();

    // Log login
    log_action($db, $user['id'], 'LOGIN', 'User logged in');

    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Logout user
 */
function logout_user($db) {
    if (is_logged_in()) {
        $user_id = get_user_id();
        log_action($db, $user_id, 'LOGOUT', 'User logged out');
    }

    session_destroy();
    header('Location: index.php');
    exit;
}

/**
 * Check if user is admin
 */
function is_admin() {
    return get_user_role() === ROLE_ADMIN;
}

/**
 * Check if user is employee
 */
function is_employee() {
    return get_user_role() === ROLE_EMPLOYEE;
}

/**
 * Check if user is intern
 */
function is_intern() {
    return get_user_role() === ROLE_INTERN;
}

/**
 * Require login - redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Require admin - redirect if not admin
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        header('HTTP/1.0 403 Forbidden');
        exit('Access Denied');
    }
}

/**
 * Log action in audit log
 */
function log_action($db, $user_id, $action, $details = '', $related_id = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $timestamp = date('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, related_id, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $action, $details, $related_id, $ip_address, $timestamp);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get server time (for all time-based operations)
 */
function get_server_time() {
    return new DateTime('now', new DateTimeZone('Asia/Kolkata'));
}

/**
 * Get server time as string
 */
function get_server_time_string() {
    return get_server_time()->format('Y-m-d H:i:s');
}

/**
 * Get server time as timestamp
 */
function get_server_timestamp() {
    return get_server_time()->getTimestamp();
}

?>
