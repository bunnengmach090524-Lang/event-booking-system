<?php
require '../../config/database.php';
require '../../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
}

redirect('/event-booking/admin/events/index.php');
?>