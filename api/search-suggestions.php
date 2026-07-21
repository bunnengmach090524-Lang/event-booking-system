<?php
/**
 * api/search-suggestions.php
 * GET ?q=keyword -> returns up to 6 matching active events for the
 * navbar's live-suggestion dropdown.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // gives us $pdo
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, title, location, event_date, image, price
     FROM events
     WHERE status = 'active'
       AND event_date >= NOW()
       AND (title LIKE ? OR location LIKE ?)
     ORDER BY event_date ASC
     LIMIT 6"
);
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'results' => $results]);