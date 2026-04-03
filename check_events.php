<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT name FROM events");
    $events = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current Events: " . implode(', ', $events);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
