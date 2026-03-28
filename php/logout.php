<?php
require_once 'db.php';
startSession();
session_unset();
session_destroy();
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
