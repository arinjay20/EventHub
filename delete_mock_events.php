<?php
require 'php/db.php';
$pdo = getDB();

// The user is organizer ID 2
// We want to remove all cards that are not published by this organizer
$stmt = $pdo->prepare("DELETE FROM events WHERE organizer_id != 2 OR organizer_id IS NULL");
$stmt->execute();

echo "Deleted " . $stmt->rowCount() . " events not belonging to organizer 2.\n";

$stmt2 = $pdo->query("SELECT id, name FROM events");
while($row = $stmt2->fetch()) {
    echo "Remaining: " . $row['name'] . "\n";
}
?>
