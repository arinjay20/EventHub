<?php
/* ================================================================
   EventHub – event_register.php
   Handles AJAX event registration for logged-in students
   ================================================================ */
header('Content-Type: application/json');
require_once 'db.php';
startSession();

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['user_role'] ?? null;

if (!$user_id || $role !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Students only.']);
    exit;
}

$event_id = (int)($_POST['event_id'] ?? 0);

if ($event_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid event ID.']));
}

try {
    $pdo = getDB();

    // 1. Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
    $stmt->execute([$user_id, $event_id]);
    if ($stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'You are already registered for this event.']));
    }

    // 2. Check capacity
    $stmt = $pdo->prepare("SELECT name, registered_count, max_capacity, status FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die(json_encode(['success' => false, 'message' => 'Event not found.']));
    }
    if ($event['status'] !== 'active') {
        die(json_encode(['success' => false, 'message' => 'Registration is closed for this event.']));
    }
    if ($event['max_capacity'] > 0 && $event['registered_count'] >= $event['max_capacity']) {
        die(json_encode(['success' => false, 'message' => 'Sorry, this event is already full.']));
    }

    // 3. Extract and validate additional details
    $full_name  = trim($_POST['full_name']  ?? '');
    $course     = trim($_POST['course']     ?? '');
    $branch     = trim($_POST['branch']     ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $student_id = trim($_POST['student_id'] ?? '');

    if (empty($full_name) || empty($course) || empty($branch) || empty($phone) || empty($student_id)) {
        die(json_encode(['success' => false, 'message' => 'Please fill in all student details.']));
    }

    // 4. Perform registration
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("INSERT INTO registrations (user_id, event_id, full_name, course, branch, phone, student_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $event_id, $full_name, $course, $branch, $phone, $student_id]);
    
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Successfully registered for: ' . $event['name']]);

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>
