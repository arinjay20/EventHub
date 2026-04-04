<?php
require 'php/db.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT id, email, role FROM users");
while($row = $stmt->fetch()) {
    echo $row['id'] . " | " . $row['email'] . " | " . $row['role'] . "\n";
}

echo "Events:\n";
$stmt = $pdo->query("SELECT id, name, organizer_id FROM events");
while($row = $stmt->fetch()) {
    echo $row['id'] . " | " . $row['name'] . " | " . $row['organizer_id'] . "\n";
}
?>
