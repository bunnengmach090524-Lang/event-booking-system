<?php
require '../config/database.php';
require '../includes/functions.php';
require '../vendor/autoload.php';
requireLogin();

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['booking_event_id']) || !isset($_SESSION['booking_quantity'])) {
    redirect('/event-booking/customer/events.php');
}

$event_id = $_SESSION['booking_event_id'];
$quantity = $_SESSION['booking_quantity'];

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    redirect('/event-booking/customer/events.php');
}

$remaining = $event['total_tickets'] - $event['tickets_sold'];

if ($quantity > $remaining) {
    unset($_SESSION['booking_event_id'], $_SESSION['booking_quantity']);
    redirect('/event-booking/customer/event-detail.php?id=' . $event_id);
}

$total_price = $event['price'] * $quantity;

$stmt = $pdo->prepare("INSERT INTO bookings (user_id, event_id, quantity, total_price, status) VALUES (?, ?, ?, ?, 'paid')");
$stmt->execute([$_SESSION['user_id'], $event_id, $quantity, $total_price]);
$booking_id = $pdo->lastInsertId();

$stmt = $pdo->prepare("UPDATE events SET tickets_sold = tickets_sold + ? WHERE id = ?");
$stmt->execute([$quantity, $event_id]);

$qrData = "BOOKING-" . $booking_id . "-EVENT-" . $event_id;
$qrCode = new QrCode($qrData);
$writer = new PngWriter();
$result = $writer->write($qrCode);

$qrFileName = 'qr_' . $booking_id . '.png';
$qrPath = '../assets/qrcodes/' . $qrFileName;
$result->saveToFile($qrPath);

$stmt = $pdo->prepare("UPDATE bookings SET qr_code = ? WHERE id = ?");
$stmt->execute([$qrFileName, $booking_id]);

unset($_SESSION['booking_event_id'], $_SESSION['booking_quantity']);

require 'header.php';
?>

<div class="bg-white rounded-lg shadow p-8 max-w-lg mx-auto text-center">
    <div class="text-5xl mb-4">✅</div>
    <h1 class="text-2xl font-bold text-gray-800 mb-2">កក់សំបុត្រជោគជ័យ!</h1>
    <p class="text-gray-500 mb-6">សូមរក្សា QR Code នេះទុកសម្រាប់ Check-in</p>

    <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
        <p class="mb-2"><span class="font-semibold">Event:</span> <?= htmlspecialchars($event['title']) ?></p>
        <p class="mb-2"><span class="font-semibold">ចំនួនសំបុត្រ:</span> <?= $quantity ?></p>
        <p class="mb-2"><span class="font-semibold">តម្លៃសរុប:</span> $<?= number_format($total_price, 2) ?></p>
        <p><span class="font-semibold">លេខកក់:</span> #<?= $booking_id ?></p>
    </div>

    <img src="/event-booking/assets/qrcodes/<?= $qrFileName ?>" class="mx-auto mb-6 w-48 h-48">

    <a href="/event-booking/customer/my-tickets.php" 
       class="block bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
        មើលសំបុត្ររបស់ខ្ញុំ
    </a>
</div>

<?php require 'footer.php'; ?>