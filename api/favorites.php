<?php
/**
 * api/favorites.php
 * GET  ?action=list  -> returns the logged-in user's favorite event IDs
 * POST { event_id }  -> toggles favorite on/off for that event
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // gives us $pdo
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// --- List all favorited event IDs for this user ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $stmt = $pdo->prepare('SELECT event_id FROM favorites WHERE user_id = ?');
    $stmt->execute([$userId]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'favorites' => $ids]);
    exit;
}

// --- Toggle a favorite on/off ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = (int) ($input['event_id'] ?? 0);

    if ($eventId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid event_id']);
        exit;
    }

    $check = $pdo->prepare('SELECT id FROM favorites WHERE user_id = ? AND event_id = ?');
    $check->execute([$userId, $eventId]);

    if ($check->fetch()) {
        $del = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND event_id = ?');
        $del->execute([$userId, $eventId]);
        echo json_encode(['success' => true, 'favorited' => false]);
    } else {
        $ins = $pdo->prepare('INSERT INTO favorites (user_id, event_id) VALUES (?, ?)');
        $ins->execute([$userId, $eventId]);
        echo json_encode(['success' => true, 'favorited' => true]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);