<?php
require_once 'php/db.php';
header('Content-Type: text/plain');
try {
    $pdo = getDB();
    // 1. Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'poster'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE events ADD COLUMN poster VARCHAR(255) DEFAULT 'assets/img/default-event.jpg' AFTER description");
        echo "✅ SUCCESS: 'poster' column added successfully! You can now publish events.";
    } else {
        echo "ℹ️ INFO: 'poster' column already exists. Your database is up to date.";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
