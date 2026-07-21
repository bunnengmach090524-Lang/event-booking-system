<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/categories/index.php');
}

csrfCheck();

$id = $_POST['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    redirect('/event-booking/admin/categories/index.php');
}

// ការពារការលុប Category ដែលមាន Event កំពុងប្រើប្រាស់
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE category = ?");
$stmt->execute([$category['name']]);
$eventsCount = $stmt->fetchColumn();

if ($eventsCount > 0) {
    $_SESSION['error'] = 'មិនអាចលុប Category នេះបានទេ ព្រោះមាន Event ' . $eventsCount . ' កំពុងប្រើប្រាស់វា។';
    redirect('/event-booking/admin/categories/index.php');
}

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['success'] = 'Category ត្រូវបានលុបដោយជោគជ័យ។';
redirect('/event-booking/admin/categories/index.php');