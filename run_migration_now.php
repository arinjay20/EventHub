<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $pdo = getDB();
    // 1. Force the Column Addition
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'poster'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE events ADD COLUMN poster VARCHAR(255) DEFAULT 'assets/img/default-event.jpg' AFTER description");
        echo "✅ SUCCESS: 'poster' column added successfully! Go back and publish your event.";
    } else {
        echo "ℹ️ INFO: 'poster' column already existed. Checking table status...";
        $check = $pdo->query("SELECT poster FROM events LIMIT 1");
        if ($check) {
           echo "\n✅ CONFIRMED: Column is active and working!";
        }
    }
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage();
}
?>
