<?php
require '../config/database.php';
require '../includes/functions.php';
require '../includes/notify.php';
require '../vendor/autoload.php';
require '../includes/send_email.php';
require '../includes/lang.php';
requireLogin();

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * This page follows the redirect-after-POST pattern to prevent duplicate
 * bookings on refresh:
 *   1. First visit (session has pending booking data) -> INSERT once,
 *      then redirect to booking.php?id=<booking_id>.
 *   2. Confirmation view (?id=<booking_id> in the URL) -> just SELECT and
 *      display the existing booking. Refreshing this URL never inserts
 *      again or resends the email.
 */

if (isset($_GET['id'])) {
    // --- Confirmation view: display an already-created booking ---
    $booking_id = (int) $_GET['id'];

    $stmt = $pdo->prepare(
        "SELECT b.*, e.title AS event_title
         FROM bookings b
         JOIN events e ON e.id = b.event_id
         WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        redirect('/event-booking/customer/my-tickets.php');
    }

    $quantity = $booking['quantity'];
    $total_price = $booking['total_price'];
    $qrFileName = $booking['qr_code'];
    $event = ['title' => $booking['event_title']];

} elseif (isset($_SESSION['booking_event_id']) && isset($_SESSION['booking_quantity']) && isset($_SESSION['payment_verified'])) {
    // --- First visit: create the booking, then redirect ---
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

    $userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

    sendBookingConfirmation(
        $userInfo['email'],
        $userInfo['name'],
        $event['title'],
        $quantity,
        number_format($total_price, 2),
        $booking_id,
        $qrFileName
    );

    notify(
        $pdo,
        (int) $_SESSION['user_id'],
        'booking_confirmed',
        t('notify_booking_title'),
        sprintf(t('notify_booking_message'), $quantity, $event['title']),
        '/event-booking/customer/my-tickets.php'
    );

    unset($_SESSION['booking_event_id'], $_SESSION['booking_quantity'], $_SESSION['payment_verified']);

    // Redirect so a page refresh re-runs this GET branch (view-only) instead
    // of re-submitting the insert above.
    redirect('/event-booking/customer/booking.php?id=' . $booking_id);

} else {
    redirect('/event-booking/customer/events.php');
}

require 'header.php';
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 max-w-lg mx-auto text-center">
    <div class="text-5xl mb-4">✅</div>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-2"><?= t('booking_success_title') ?></h1>
    <p class="text-gray-500 dark:text-gray-400 mb-6"><?= t('booking_success_subtitle') ?></p>

    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6 text-left text-gray-700 dark:text-gray-200">
        <p class="mb-2"><span class="font-semibold text-gray-800 dark:text-white"><?= t('label_event') ?>:</span> <?= htmlspecialchars($event['title']) ?></p>
        <p class="mb-2"><span class="font-semibold text-gray-800 dark:text-white"><?= t('label_quantity') ?>:</span> <?= $quantity ?></p>
        <p class="mb-2"><span class="font-semibold text-gray-800 dark:text-white"><?= t('label_total') ?>:</span> $<?= number_format($total_price, 2) ?></p>
        <p><span class="font-semibold text-gray-800 dark:text-white"><?= t('label_booking_number') ?>:</span> #<?= $booking_id ?></p>
    </div>

    <img src="/event-booking/assets/qrcodes/<?= $qrFileName ?>" class="mx-auto mb-6 w-48 h-48">

    <a href="/event-booking/customer/my-tickets.php" 
       class="block bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
        <?= t('view_my_tickets') ?>
    </a>
</div>

<?php require 'footer.php'; ?>