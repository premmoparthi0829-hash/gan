<?php
/**
 * VK Logistics - Core Validation & Helper Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Validate UK Postcode Format using official UK Regex
 */
function validate_uk_postcode($postcode) {
    $postcode = strtoupper(trim(preg_replace('/\s+/', '', $postcode)));
    // Standard UK Postcode regex pattern
    $pattern = '/^(GIR0AA|(?:[A-PR-UWYZ][0-9][0-9]?|[A-PR-UWYZ][A-HK-Y][0-9][0-9]?|[A-PR-UWYZ][0-9][A-HJKPSTUW]|[A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVW-Y])[0-9][ABD-HJLNP-UW-Z]{2})$/';
    return preg_match($pattern, $postcode) === 1;
}

/**
 * Format UK Postcode nicely (e.g., SW1A 1AA)
 */
function format_uk_postcode($postcode) {
    $clean = strtoupper(trim(preg_replace('/\s+/', '', $postcode)));
    if (strlen($clean) >= 5) {
        $inward = substr($clean, -3);
        $outward = substr($clean, 0, -3);
        return $outward . ' ' . $inward;
    }
    return strtoupper($postcode);
}

/**
 * Validate UK Mobile Number Format (+44 7xxx xxx xxx or 07xxx xxx xxx)
 */
function validate_uk_mobile($mobile) {
    $clean = preg_replace('/[\s\-\(\)]/', '', $mobile);
    $pattern = '/^(?:\+44|0)7\d{9}$/';
    return preg_match($pattern, $clean) === 1;
}

/**
 * Format UK Mobile Number cleanly (+44 7XXX XXXXXX)
 */
function format_uk_mobile($mobile) {
    $clean = preg_replace('/[\s\-\(\)]/', '', $mobile);
    if (strpos($clean, '07') === 0) {
        $clean = '+44' . substr($clean, 1);
    }
    return $clean;
}

/**
 * Validate Email Address
 */
function validate_email($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Retrieve setting value from database settings table (with fallback)
 */
function get_setting($key, $default = '') {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch();
            if ($row && $row['setting_value'] !== null) {
                return $row['setting_value'];
            }
        } catch (Exception $e) {
            log_system_error("Error fetching setting '$key': " . $e->getMessage());
        }
    }
    
    // Default fallbacks if DB not initialized yet
    $defaults = [
        'product_name' => 'Ganesh Statue / Vinayaka Vigraha',
        'unit_price' => DEFAULT_UNIT_PRICE,
        'shipping_charge' => DEFAULT_SHIPPING_FEE,
        'currency_symbol' => '£',
        'currency_code' => 'GBP',
        'service_area' => 'United Kingdom',
        'bank_account_name' => 'VK LOGISTICS LTD',
        'bank_name' => 'Barclays Bank UK',
        'bank_sort_code' => '20-45-77',
        'bank_account_number' => '83920144',
        'paypal_client_id' => 'sb',
        'paypal_mode' => 'sandbox',
        'support_phone' => '+44 7700 900888',
        'support_email' => 'bappa@vklogistics.co.uk'
    ];

    return $defaults[$key] ?? $default;
}

/**
 * Fetch all settings array
 */
function get_all_settings() {
    $keys = [
        'product_name', 'unit_price', 'shipping_charge', 'currency_symbol',
        'currency_code', 'service_area', 'bank_account_name', 'bank_name',
        'bank_sort_code', 'bank_account_number', 'paypal_client_id', 'paypal_mode',
        'support_phone', 'support_email'
    ];
    $settings = [];
    foreach ($keys as $key) {
        $settings[$key] = get_setting($key);
    }
    return $settings;
}
