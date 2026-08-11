<?php
/**
 * VK Logistics - Database Connection (PDO)
 * Connects STRICTLY to MySQL database `vk_logistics` (using vklogistics.sql schema & data).
 */

require_once __DIR__ . '/config.php';

defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', getenv('DB_PORT') ?: '8889');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'vk_logistics');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            $ports     = [DB_PORT, 8889, 3307, 3306];
            $passwords = ['root', ''];
            $dbname    = DB_NAME;
            $user      = DB_USER;

            // Connect STRICTLY to MySQL via 127.0.0.1 TCP
            foreach ($ports as $port) {
                foreach ($passwords as $pass) {
                    try {
                        $dsn = "mysql:host=127.0.0.1;port={$port};dbname={$dbname};charset=utf8mb4";
                        $options = [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                            PDO::ATTR_TIMEOUT            => 3,
                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                        ];
                        self::$conn = new PDO($dsn, $user, $pass, $options);
                        return self::$conn;
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Unknown database') !== false || $e->getCode() == 1049) {
                            $mysql_conn = self::autoCreateDatabase('127.0.0.1', $port, $dbname, $user, $pass);
                            if ($mysql_conn !== null) {
                                self::$conn = $mysql_conn;
                                return self::$conn;
                            }
                        }
                    }
                }
            }

            log_system_error("MySQL Connection Error: Could not connect to MySQL server on ports 8889/3307/3306.");
            return null;
        }
        return self::$conn;
    }

    private static function autoCreateDatabase($host, $port, $dbname, $user, $pass) {
        try {
            $root_dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $root_pdo = new PDO($root_dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $sql_file = __DIR__ . '/../vklogistics.sql';
            if (file_exists($sql_file)) {
                $sql_content = file_get_contents($sql_file);
                if (!empty($sql_content)) {
                    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
                    foreach ($statements as $stmt) {
                        if (!empty($stmt)) {
                            try { $pdo->exec($stmt); } catch (Exception $ex) {}
                        }
                    }
                }
            }
            return $pdo;
        } catch (Exception $ex) {
            log_system_error("Auto MySQL database creation failed: " . $ex->getMessage());
            return null;
        }
    }
}
