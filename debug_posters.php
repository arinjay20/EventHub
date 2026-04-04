<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, poster FROM events");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($events, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
