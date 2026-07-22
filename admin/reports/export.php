<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/lang.php';
requireAdmin();

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT b.id, e.title as event_title, e.category, u.name as customer_name, u.email,
           b.quantity, b.total_price, b.status, b.created_at
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'paid' AND DATE(b.created_at) BETWEEN ? AND ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$from, $to]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $from . '_to_' . $to . '.csv"');

echo "\xEF\xBB\xBF"; // UTF-8 BOM សម្រាប់ Excel

$output = fopen('php://output', 'w');
fputcsv($output, [
    t('csv_booking_id'),
    t('csv_event'),
    t('csv_category'),
    t('csv_customer'),
    t('csv_email'),
    t('csv_quantity'),
    t('csv_total_price'),
    t('csv_booked_at'),
]);

foreach ($bookings as $b) {
    fputcsv($output, [
        $b['id'], $b['event_title'], $b['category'], $b['customer_name'], $b['email'],
        $b['quantity'], number_format($b['total_price'], 2), $b['created_at'],
    ]);
}

fclose($output);
exit();