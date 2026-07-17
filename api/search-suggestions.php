<?php
session_start();
require '../config/database.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, title, category, location, price, image 
    FROM events 
    WHERE event_date >= NOW() AND (title LIKE ? OR location LIKE ?)
    ORDER BY event_date ASC
    LIMIT 5
");
$searchTerm = "%$query%";
$stmt->execute([$searchTerm, $searchTerm]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);