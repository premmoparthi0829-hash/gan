<?php
/**
 * VK Logistics - Simple & Direct Database Connection (PDO)
 */

require_once __DIR__ . '/config.php';

defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'gan');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $hosts = [DB_HOST, '127.0.0.1', 'localhost'];
        $ports = [DB_PORT, '3306'];
        $db_names = [DB_NAME, 'gan', 'vk_logistics'];
        $passwords = [DB_PASS, '', 'root'];

        $last_exception = null;

        // Try standard defined connection first
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            self::$conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$conn;
        } catch (Exception $e) {
            $last_exception = $e;
        }

        // Try socket connection for XAMPP on macOS
        if (file_exists('/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock')) {
            foreach ($db_names as $dbname) {
                foreach ($passwords as $pass) {
                    try {
                        $dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=" . $dbname . ";charset=utf8mb4";
                        self::$conn = new PDO($dsn, DB_USER, $pass, $options);
                        return self::$conn;
                    } catch (Exception $e) {
                        $last_exception = $e;
                    }
                }
            }
        }

        // Try candidate permutations
        foreach ($hosts as $host) {
            foreach ($ports as $port) {
                foreach ($db_names as $dbname) {
                    foreach ($passwords as $pass) {
                        try {
                            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $dbname . ";charset=utf8mb4";
                            self::$conn = new PDO($dsn, DB_USER, $pass, $options);
                            return self::$conn;
                        } catch (Exception $e) {
                            $last_exception = $e;
                        }
                    }
                }
            }
        }

        if ($last_exception) {
            log_system_error("Database connection failure: " . $last_exception->getMessage());
        }

        return null;
    }
}
