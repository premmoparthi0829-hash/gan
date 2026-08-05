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
                log_system_error("Database Connection Error: " . $e->getMessage());
                // Return null so callers can handle fallback safely
                return null;
            }
        }
        return self::$conn;
    }
}
