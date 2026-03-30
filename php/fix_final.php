<?php
/**
 * fix_final.php
 * Final attempt to switch root@localhost auth plugin.
 */
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''");
    echo "SUCCESS: DATABASE FIXED\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
