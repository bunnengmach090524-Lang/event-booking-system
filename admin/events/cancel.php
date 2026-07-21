<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/notify.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/events/index.php');
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare("SELECT title FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);
    $eventTitle = $ev['title'] ?? '#' . $id;

    $stmt = $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);

    // Notify every user who had booked this event
    $userStmt = $pdo->prepare("SELECT DISTINCT user_id FROM bookings WHERE event_id = ?");
    $userStmt->execute([$id]);
    $affectedUserIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($affectedUserIds as $userId) {
        notify(
            $pdo,
            (int) $userId,
            'booking_cancelled',
            'Event ត្រូវបានបោះបង់',
            'Event "' . $eventTitle . '" ដែលអ្នកបានកក់ ត្រូវបានបោះបង់ដោយអ្នកគ្រប់គ្រង។',
            '/event-booking/customer/my-tickets.php'
        );
    }

    logActivity($pdo, 'cancel_event', 'បានបោះបង់ Event "' . $eventTitle . '"');
    $_SESSION['success'] = 'Event ត្រូវបានបោះបង់ (Cancelled) ដោយជោគជ័យ។';
}

redirect('/event-booking/admin/events/index.php');