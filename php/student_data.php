<?php
/* ================================================================
   EventHub – student_data.php
   Returns logged-in student's live stats + registered events
   ================================================================ */
ini_set('display_errors', 1); error_reporting(E_ALL);
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once 'db.php';
startSession();

try {
    $pdo = getDB();

    // Get user_id from session, or fall back to GET param for demo
    $user_id = $_SESSION['user_id'] ?? null;
    $role    = $_SESSION['user_role'] ?? null;

    if (!$user_id || $role !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    /* ---- User profile ---- */
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, department, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    /* ---- Stats ---- */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $events_registered = (int)$stmt->fetchColumn();

    // Events attended = registrations where event date has passed
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE r.user_id = ? AND e.event_date < NOW()
    ");
    $stmt->execute([$user_id]);
    $events_attended = (int)$stmt->fetchColumn();

    /* ---- My Registered Events ---- */
    $stmt = $pdo->prepare("
        SELECT
            e.id, e.name, e.event_date, e.venue AS location, e.status,
            e.category, e.registered_count, e.max_capacity,
            r.registration_date AS registered_at,
            DATEDIFF(e.event_date, NOW()) AS days_left
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE r.user_id = ?
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$user_id]);
    $my_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Category icons */
    $icons = [
        'technology' => '🤖', 'cultural' => '🎭',
        'sports' => '⚽',    'academic' => '📚',
        'workshop' => '🔧',   'other' => '🎯'
    ];
    $colors = [
        'technology' => 'linear-gradient(135deg,#667eea,#764ba2)',
        'cultural'   => 'linear-gradient(135deg,#f093fb,#f5576c)',
        'sports'     => 'linear-gradient(135deg,#43e97b,#38f9d7)',
        'academic'   => 'linear-gradient(135deg,#4facfe,#00f2fe)',
        'workshop'   => 'linear-gradient(135deg,#fa709a,#fee140)',
        'other'      => 'linear-gradient(135deg,#a18cd1,#fbc2eb)',
    ];

    foreach ($my_events as &$ev) {
        $cat = strtolower($ev['category'] ?? 'other');
        $ev['icon']  = $icons[$cat]  ?? '🎯';
        $ev['color'] = $colors[$cat] ?? $colors['other'];
        $ev['days_left'] = max(0, (int)$ev['days_left']);
        $ev['fill_pct']  = $ev['max_capacity'] > 0
            ? round($ev['registered_count'] / $ev['max_capacity'] * 100)
            : 0;
    }
    unset($ev);

    /* ---- Trending events (not yet registered) ---- */
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, e.event_date, e.venue AS location, e.category,
               e.registered_count, e.max_capacity,
               DATEDIFF(e.event_date, NOW()) AS days_left
        FROM events e
        WHERE e.status = 'active'
          AND e.id NOT IN (SELECT event_id FROM registrations WHERE user_id = ?)
        ORDER BY e.event_date ASC
        LIMIT 4
    ");
    $stmt->execute([$user_id]);
    $trending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trending as &$ev) {
        $cat = strtolower($ev['category'] ?? 'other');
        $ev['icon']  = $icons[$cat]  ?? '🎯';
        $ev['color'] = $colors[$cat] ?? $colors['other'];
        $ev['seats_left'] = max(0, $ev['max_capacity'] - $ev['registered_count']);
    }
    unset($ev);

    echo json_encode([
        'success'           => true,
        'user'              => $user,
        'events_registered' => $events_registered,
        'events_attended'   => $events_attended,
        'my_events'         => $my_events,
        'trending'          => $trending,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
