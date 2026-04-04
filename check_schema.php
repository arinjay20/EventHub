<?php
require 'php/db.php';
$pdo = getDB();
$stmt = $pdo->query("SHOW COLUMNS FROM registrations");
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' | ' . $row['Type'] . "\n";
}
?>
