<?php
/* ================================================================
   EventHub – get_events.php
   Returns events as JSON for AJAX / Fetch API
   Supports: ?category=&search=&sort=date
   ================================================================ */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db.php';

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');
$sort     = in_array($_GET['sort'] ?? 'date', ['date','name','capacity']) ? ($_GET['sort'] ?? 'date') : 'date';

$params    = [];
$whereParts = [];

if (!empty($category) && $category !== 'all') {
    $whereParts[] = 'category = ?';
    $params[] = $category;
}

if (!empty($search)) {
    $whereParts[] = '(name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where = count($whereParts) > 0 ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$orderMap = [
    'date'     => 'event_date ASC',
    'name'     => 'name ASC',
    'capacity' => 'max_capacity DESC',
];
$orderBy = $orderMap[$sort];

try {
    $pdo  = getDB();
    
    // Using JOIN to get organizer name
    $sql = "SELECT e.id, e.name, e.category, e.event_date, e.venue, e.description,
                   e.max_capacity, e.registered_count, e.status, e.poster,
                   CONCAT(u.first_name, ' ', u.last_name) as organizer_name
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            $where
            ORDER BY $orderBy
            LIMIT 50";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count'   => count($events),
        'events'  => $events,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch events.']);
}
