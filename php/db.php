<?php
/* ================================================================
   EventHub – db.php
   Database Connection (PDO)
   ================================================================ */

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $session_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($session_path)) @mkdir($session_path, 0777, true);
        if (is_writable($session_path)) session_save_path($session_path);
        
        session_start();
    }
}

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'eventhub_db');
define('DB_USER', 'root');      // Change to your MySQL username
define('DB_PASS', '');          // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
