<?php
/**
 * VK Logistics - Database Connection (PDO)
 * Easy configuration for XAMPP / WAMP / MAMP / cPanel / Localhost
 */

require_once __DIR__ . '/config.php';

// Easy Database Configuration
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'vk_logistics');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            $host   = DB_HOST;
            $port   = DB_PORT;
            $dbname = DB_NAME;
            $user   = DB_USER;
            $pass   = DB_PASS;

            try {
                // Try localhost first with fast 1-second timeout
                $dsn = "mysql:host=localhost;port={$port};dbname={$dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 1,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];

                self::$conn = new PDO($dsn, $user, $pass, $options);

            } catch (PDOException $e) {
                // If database does not exist yet (error 1049), attempt auto-creation
                if (strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() == 1049) {
                    self::$conn = self::autoCreateDatabase($host, $port, $dbname, $user, $pass);
                } else {
                    // Try fallback to host '127.0.0.1'
                    try {
                        $dsn_fallback = "mysql:host=127.0.0.1;port={$port};dbname={$dbname};charset=utf8mb4";
                        self::$conn = new PDO($dsn_fallback, $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 1
                        ]);
                        return self::$conn;
                    } catch (PDOException $e2) {
                        // Fallback failed
                    }
                    log_system_error("Database Connection Notice: MySQL not connected ({$e->getMessage()}). Operating with default system fallback.");
                    return null;
                }
            }
        }
        return self::$conn;
    }

    /**
     * Auto-create database & import vklogistics.sql schema on first run
     */
    private static function autoCreateDatabase($host, $port, $dbname, $user, $pass) {
        try {
            $root_dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $root_pdo = new PDO($root_dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Connect to newly created DB
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Auto-import vklogistics.sql schema if file exists
            $sql_file = __DIR__ . '/../vklogistics.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                if (!empty($sql_content)) {
                    $pdo->exec($sql_content);
                }
            }

            return $pdo;
        } catch (Exception $ex) {
            log_system_error("Auto database creation failed: " . $ex->getMessage());
            return null;
        }
    }
}
