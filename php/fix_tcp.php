<?php
/**
 * fix_tcp.php
 * Use 127.0.0.1 to force TCP and skip unix sockets.
 */
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''");
    echo "SUCCESS: TCP FIX APPLIED\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
