<?php
require 'php/db.php';
$pdo = getDB();
$stmt = $pdo->query("SHOW COLUMNS FROM registrations");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('schema.json', json_encode($columns, JSON_PRETTY_PRINT));
echo "Schema written to schema.json";
?>
