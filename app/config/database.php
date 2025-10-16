<?php
// PDO MySQL connection helper for XAMPP
// Usage:
//   $db = Database::getConnection();
//   $stmt = $db->prepare('SELECT 1');

class Database {
    private static $connection = null;

    public static function getConnection(): PDO {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Load environment configuration if not already loaded
        if (!defined('DB_HOST')) {
            require_once dirname(dirname(__DIR__)) . '/config/environment.php';
        }
        
        // Use environment-aware settings
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbName = DB_NAME;

        try {
            // Create database connection
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, $user, $pass, $options);
            
            // Create database if not exists (development only; disabled on shared hosting)
            if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
            }
            
            // Connect to the specific database
            $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
            self::$connection = new PDO($dsn, $user, $pass, $options);
            
            return self::$connection;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'ok' => false,
                'error' => 'Database connection failed',
                'details' => $e->getMessage(),
            ]));
        }
    }
}

// JSON header helper for APIs
function json_headers(): void {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

