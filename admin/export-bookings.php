<?php
require '../config/database.php';
require '../includes/functions.php';
requireAdmin();

$stmt = $pdo->query("
    SELECT b.id, e.title as event_title, u.name as customer_name, u.email,
           b.quantity, b.total_price, b.status, b.is_checked_in, b.created_at
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// បង្កើត CSV Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bookings_' . date('Y-m-d') . '.csv"');

// UTF-8 BOM ដើម្បីឲ្យ Excel បង្ហាញអក្សរខ្មែរបានត្រឹមត្រូវ
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'Booking ID', 'Event', 'Customer Name', 'Email',
    'Quantity', 'Total Price ($)', 'Status', 'Checked In', 'Booked At'
]);

foreach ($bookings as $b) {
    fputcsv($output, [
        $b['id'],
        $b['event_title'],
        $b['customer_name'],
        $b['email'],
        $b['quantity'],
        number_format($b['total_price'], 2),
        $b['status'],
        $b['is_checked_in'] ? 'Yes' : 'No',
        $b['created_at'],
    ]);
}

fclose($output);
exit();