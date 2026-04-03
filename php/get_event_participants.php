<?php
/* ================================================================
   EventHub – get_event_participants.php
   Returns a list of students registered for a specific event
   ================================================================ */
require_once 'db.php';
startSession();

header('Content-Type: application/json');

// Session check
$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['user_role'] ?? null;

if (!$user_id || $role !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

try {
    $pdo = getDB();
    
    // Verify the event belongs to this organizer (Security Check)
    $check = $pdo->prepare("SELECT id, name FROM events WHERE id = ? AND organizer_id = ?");
    $check->execute([$event_id, $user_id]);
    $event = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Access denied or event not found']);
        exit;
    }

    // Get the participants
    $stmt = $pdo->prepare("SELECT full_name, student_id, course, branch, phone, IFNULL(email, 'N/A') as email, registration_date 
                          FROM registrations 
                          WHERE event_id = ? 
                          ORDER BY registration_date DESC");
    $stmt->execute([$event_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'event_name' => $event['name'],
        'participants' => $participants
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
