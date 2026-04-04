<?php
require_once 'php/db.php';
try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, poster FROM events WHERE poster IS NOT NULL AND poster != ''");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($events as $ev) {
        if (strpos($ev['poster'], 'uploads/') === 0 && strpos($ev['poster'], 'uploads/posters/') === false) {
            $newPath = str_replace('uploads/', 'uploads/posters/', $ev['poster']);
            $upd = $pdo->prepare("UPDATE events SET poster = ? WHERE id = ?");
            $upd->execute([$newPath, $ev['id']]);
            $count++;
        }
    }
    echo "Updated $count events with correct poster paths.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
