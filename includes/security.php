<?php
/**
 * VK Logistics - Security Functions (CSRF, XSS, Input Sanitization)
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Get active CSRF Token
 */
function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF Token
 */
function validate_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize user text input
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    return strip_tags($data);
}

/**
 * Escape output for safe HTML rendering
 */
function escape_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Standardized JSON API response output
 */
function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => (bool)$success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], $data));
    exit;
}

/**
 * Check if admin is currently authenticated in session
 */
function is_admin_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Verify admin passcode against database setting or default 'admin123'
 */
function verify_admin_password($passcode) {
    $stored_pass = function_exists('get_setting') ? get_setting('admin_password', 'admin123') : 'admin123';
    if (empty($stored_pass)) return false;

    if (str_starts_with($stored_pass, '$2y$') || str_starts_with($stored_pass, '$2a$') || str_starts_with($stored_pass, '$2b$')) {
        return password_verify($passcode, $stored_pass);
    }
    return $passcode === $stored_pass;
}

/**
 * Mark admin authenticated state in session
 */
function set_admin_logged_in($status = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_logged_in'] = (bool)$status;
}

/**
 * Destroy admin session
 */
function admin_logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_logged_in'] = false;
    unset($_SESSION['admin_logged_in']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}


