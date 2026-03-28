<?php
/* ================================================================
   EventHub – delete_event.php
   Deletes an event created by the logged-in organizer
   ================================================================ */
require_once 'db.php';
startSession();

header('Content-Type: application/json');

// Defensive session check
$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['user_role'] ?? null;

if (!$user_id || $role !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please login as an organizer.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$event_id = (int)($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
    exit;
}

try {
    $pdo = getDB();
    
    // 1. Verify that this organizer OWNS the event
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: You do not own this event.']);
        exit;
    }

    // 2. Delete registrations first (foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);

    // 3. Delete the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => '🗑️ Event deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete event or event not found.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
