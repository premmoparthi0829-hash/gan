<?php
/**
 * VK Logistics - Ganesh Statue Booking Configuration
 */

// Secure session start
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('Europe/London');

// System Constants
define('APP_NAME', 'VK Logistics');
define('APP_TITLE', 'Ganesh Statue / Vinayaka Vigraha Booking - UK');
define('DEFAULT_UNIT_PRICE', 14.99);
define('DEFAULT_SHIPPING_FEE', 3.99);
define('CURRENCY_SYMBOL', '£');
define('CURRENCY_CODE', 'GBP');
define('TARGET_COUNTRY', 'United Kingdom');

// Environment & Error Handling
define('ENVIRONMENT', 'production'); // 'development' or 'production'

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Generate Anti-CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log technical errors silently
function log_system_error($message) {
    $log_file = __DIR__ . '/../error.log';
    $timestamp = date('[Y-m-d H:i:s]');
    @file_put_contents($log_file, "$timestamp $message" . PHP_EOL, FILE_APPEND);
}
