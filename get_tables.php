<?php
require 'php/db.php';
$pdo = getDB();
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
file_put_contents('tables.json', json_encode($tables));
echo "Tables written";
?>
