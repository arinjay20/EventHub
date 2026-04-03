<?php
/* ================================================================
   EventHub – get_event_details.php
   Returns full details for a specific event
   ================================================================ */
require_once 'db.php';
startSession();

header('Content-Type: application/json');

$event_id = $_GET['id'] ?? null;
$user_id  = $_SESSION['user_id'] ?? null;

if (!$event_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or Missing ID']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $user_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        echo json_encode(['success' => true, 'event' => $event]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Event not found or unauthorized']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
