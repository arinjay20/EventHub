<?php
header('Content-Type: application/json');
require_once 'db.php';
startSession();

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'loggedIn' => true,
        'user_id'   => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'],
        'role'      => $_SESSION['user_role']
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>
