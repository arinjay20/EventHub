<?php
/* ================================================================
   EventHub – organizer_data.php
   Returns events created by the logged-in organizer
   ================================================================ */
require_once 'db.php';
startSession();

header('Content-Type: application/json');

// Defensive session check
$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['user_role'] ?? null;

if (!$user_id || $role !== 'organizer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Get Summary Stats
    $stmt = $pdo->prepare("SELECT count(*) as total, 
                                 SUM(CASE WHEN event_date >= NOW() THEN 1 ELSE 0 END) as active,
                                 SUM(CASE WHEN event_date < NOW() THEN 1 ELSE 0 END) as completed,
                                 SUM(registered_count) as total_regs
                          FROM events WHERE organizer_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Get Events List
    $stmt = $pdo->prepare("SELECT id, name, category, event_date, venue, registered_count, max_capacity, status 
                          FROM events 
                          WHERE organizer_id = ? 
                          ORDER BY event_date DESC");
    $stmt->execute([$user_id]);
    $my_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Recent Registrations (for organizer's events)
    $stmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.email, e.name as event_name, r.registration_date
                          FROM registrations r
                          JOIN users u ON r.user_id = u.id
                          JOIN events e ON r.event_id = e.id
                          WHERE e.organizer_id = ?
                          ORDER BY r.registration_date DESC
                          LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_regs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $stats['total'] ?? 0,
            'active' => $stats['active'] ?? 0,
            'completed' => $stats['completed'] ?? 0,
            'total_regs' => $stats['total_regs'] ?? 0
        ],
        'events' => $my_events,
        'recent_regs' => $recent_regs
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
