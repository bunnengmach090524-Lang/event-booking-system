<?php
/**
 * includes/lang.php
 * Include this near the top of every customer-facing page (after functions.php,
 * since that's what starts the session and connects to the DB).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // gives us $pdo

// --- 1. Handle a language switch request ---
// Called by navbar.js via: /includes/lang.php?set=km&ajax=1
if (isset($_GET['set']) && in_array($_GET['set'], ['en', 'km'], true)) {
    $lang = $_GET['set'];

    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/');

    // If logged in, persist to their account so it follows them across devices
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare('UPDATE users SET preferred_lang = ? WHERE id = ?');
        $stmt->execute([$lang, $_SESSION['user_id']]);
    }

    if (!empty($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'lang' => $lang]);
        exit;
    }

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// --- 2. Resolve current language for this page load ---
// Priority: session > cookie > default 'en'
if (!empty($_SESSION['lang'])) {
    $currentLang = $_SESSION['lang'];
} elseif (!empty($_COOKIE['lang'])) {
    $currentLang = $_COOKIE['lang'];
} else {
    $currentLang = 'en';
}

// --- 3. Load translation strings ---
$langFile = __DIR__ . '/../lang/' . $currentLang . '.php';
$translations = file_exists($langFile) ? require $langFile : [];

function t(string $key): string
{
    global $translations;
    return $translations[$key] ?? $key;
}