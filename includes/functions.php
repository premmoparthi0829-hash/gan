<?php
/**
 * VK Logistics - Core Validation & Helper Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/booking-functions.php';

/**
 * Validate UK Postcode Format (Flexible validation)
 */
function validate_uk_postcode($postcode) {
    $clean = strtoupper(trim(preg_replace('/\s+/', '', $postcode)));
    return strlen($clean) >= 3;
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
 * Validate UK Mobile Number Format (Flexible validation for test & real numbers)
 */
function validate_uk_mobile($mobile) {
    $clean = preg_replace('/[^\d+]/', '', $mobile);
    return strlen($clean) >= 7 && strlen($clean) <= 16;
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
        'paypal_client_secret' => '',
        'paypal_email' => 'payments@vklogistics.co.uk',
        'paypal_account_name' => 'VK LOGISTICS LTD',
        'paypal_id' => 'premmoparthi@paypal',
        'support_phone' => '+44 7700 900888',
        'support_email' => 'bappa@vklogistics.co.uk'
    ];

    return $defaults[$key] ?? $default;
}

/**
 * Update or insert a setting value in database settings table
 */
function update_setting($key, $value) {
    $db = Database::getConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (:k, :v, 'System Setting') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            return $stmt->execute([':k' => $key, ':v' => (string)$value]);
        } catch (Exception $e) {
            log_system_error("Error updating setting '$key': " . $e->getMessage());
        }
    }
    return false;
}

/**
 * Fetch all settings array in 1 single optimized database query
 */
function get_all_settings() {
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
        'paypal_client_secret' => '',
        'paypal_email' => 'payments@vklogistics.co.uk',
        'paypal_account_name' => 'VK LOGISTICS LTD',
        'paypal_id' => 'premmoparthi@paypal',
        'support_phone' => '+44 7700 900888',
        'support_email' => 'bappa@vklogistics.co.uk',
        'paypal_status' => 'enabled'
    ];

    $db = Database::getConnection();
    if ($db) {
        try {
            $rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
            foreach ($rows as $r) {
                if (isset($r['setting_key']) && $r['setting_value'] !== null) {
                    $defaults[$r['setting_key']] = $r['setting_value'];
                }
            }
        } catch (Exception $e) {
            log_system_error("Error fetching all settings: " . $e->getMessage());
        }
    }
    return $defaults;
}
