<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, poster FROM events");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($events as $ev) {
        $old = $ev['poster'];
        if (!$old) continue;
        
        // Handle both forward and backward slashes just in case
        $normalized = str_replace('\\', '/', $old);
        
        if (strpos($normalized, 'uploads/') === 0 && strpos($normalized, 'uploads/posters/') === false) {
            $newPath = str_replace('uploads/', 'uploads/posters/', $normalized);
            $upd = $pdo->prepare("UPDATE events SET poster = ? WHERE id = ?");
            $upd->execute([$newPath, $ev['id']]);
            echo "Updated {$ev['name']}: $old -> $newPath\n";
            $count++;
        } else {
            echo "Skipping {$ev['name']}: $old (already correct or not matching)\n";
        }
    }
    echo "\nTotal updated: $count\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
