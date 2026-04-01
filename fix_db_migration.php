<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    // 1. Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'poster'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE events ADD COLUMN poster VARCHAR(255) DEFAULT 'assets/img/default-event.jpg' AFTER description");
        echo "✅ SUCCESS: 'poster' column added successfully!";
    } else {
        echo "ℹ️ INFO: 'poster' column already exists.";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
