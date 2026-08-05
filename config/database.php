<?php
/**
 * VK Logistics - Database Connection (PDO)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $host = 'localhost';
    private static $db_name = 'vk_logistics';
    private static $username = 'root';
    private static $password = '';
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];

                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Fallback to local SQLite database if MySQL server is unavailable
                try {
                    $sqlite_path = __DIR__ . '/../vklogistics.sqlite';
                    $is_new = !file_exists($sqlite_path);
                    self::$conn = new PDO("sqlite:" . $sqlite_path);
                    self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                    if ($is_new) {
                        self::initSqliteSchema(self::$conn);
                    } else {
                        try {
                            self::$conn->exec("ALTER TABLE bookings ADD COLUMN payment_proof_image TEXT NULL");
                        } catch (Exception $ex) {
                            // Column already exists
                        }
                    }
                } catch (Exception $sqe) {
                    log_system_error("Database Connection Error: " . $e->getMessage() . " | SQLite Error: " . $sqe->getMessage());
                    return null;
                }
            }
        }
        return self::$conn;
    }

    private static function initSqliteSchema($conn) {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT NULL,
                description TEXT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                booking_reference TEXT NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                mobile TEXT NOT NULL,
                email TEXT NOT NULL,
                address_line_1 TEXT NOT NULL,
                address_line_2 TEXT NULL,
                city TEXT NOT NULL,
                county TEXT NULL,
                postcode TEXT NOT NULL,
                country TEXT NOT NULL DEFAULT 'United Kingdom',
                quantity INTEGER NOT NULL DEFAULT 1,
                unit_price REAL NOT NULL DEFAULT 14.99,
                subtotal REAL NOT NULL,
                shipping_charge REAL NOT NULL DEFAULT 3.99,
                total_amount REAL NOT NULL,
                payment_method TEXT NOT NULL,
                payment_reference TEXT NULL,
                paypal_order_id TEXT NULL,
                paypal_transaction_id TEXT NULL,
                payment_status TEXT NOT NULL DEFAULT 'PAYMENT VERIFICATION PENDING',
                booking_status TEXT NOT NULL DEFAULT 'CONFIRMED',
                payment_proof_image TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS booking_sequence (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            INSERT OR IGNORE INTO settings (setting_key, setting_value, description) VALUES
            ('product_name', 'Ganesh Statue / Vinayaka Vigraha', 'Name of the festival product'),
            ('unit_price', '14.99', 'Base unit price per statue in GBP (£)'),
            ('shipping_charge', '3.99', 'Flat shipping fee within United Kingdom in GBP (£)'),
            ('currency_symbol', '£', 'Display currency symbol'),
            ('currency_code', 'GBP', 'Standard ISO currency code'),
            ('service_area', 'United Kingdom', 'Restricted delivery region'),
            ('bank_account_name', 'VK LOGISTICS LTD', 'Bank account holder name for direct transfers'),
            ('bank_name', 'Barclays Bank UK', 'Bank name for customer transfers'),
            ('bank_sort_code', '20-45-77', 'UK Bank Sort Code'),
            ('bank_account_number', '83920144', 'UK Bank Account Number'),
            ('paypal_client_id', 'sb', 'PayPal SDK Client ID (Default: sb for Sandbox)'),
            ('paypal_mode', 'sandbox', 'PayPal Mode: sandbox or live'),
            ('support_phone', '+44 7700 900888', 'UK Support Contact Line'),
            ('support_email', 'bappa@vklogistics.co.uk', 'Support Email Address'),
            ('website_status', 'active', 'Website status: active or maintenance');
        ");
    }
}
