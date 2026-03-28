<?php
require_once 'db.php';
$pdo = getDB();

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "New hash: $hash\n";

$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, 'john.doe@geu.edu']);
$stmt->execute([$hash, 'techclub@geu.edu']);
$stmt->execute([$hash, 'admin@eventhub.edu']);

echo "Passwords updated for john.doe, techclub, and admin to 'password123'\n";
?>
