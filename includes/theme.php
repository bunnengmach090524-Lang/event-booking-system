<?php
/**
 * includes/theme.php
 * Theme applies instantly client-side via localStorage (see navbar.js).
 * This endpoint just persists the choice server-side for logged-in users
 * so it follows them across devices.
 *
 * Called via fetch() as: POST /includes/theme.php  { theme: "dark" }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // gives us $pdo
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$theme = $input['theme'] ?? '';

if (!in_array($theme, ['light', 'dark'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid theme']);
    exit;
}

$_SESSION['theme'] = $theme;
setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), '/');

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('UPDATE users SET preferred_theme = ? WHERE id = ?');
    $stmt->execute([$theme, $_SESSION['user_id']]);
}

echo json_encode(['success' => true, 'theme' => $theme]);