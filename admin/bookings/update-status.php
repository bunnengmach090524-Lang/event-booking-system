<?php
require '../../config/database.php';
require '../../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/bookings/index.php');
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? null;

$allowedStatuses = ['paid', 'pending', 'refunded', 'cancelled'];

if ($id && in_array($status, $allowedStatuses, true)) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    logActivity($pdo, 'update_booking_status', "បានប្តូរ Booking #$id ទៅជា " . ucfirst($status));
    $_SESSION['success'] = "Booking #$id ត្រូវបានប្តូរទៅជា " . ucfirst($status) . " ដោយជោគជ័យ។";
} else {
    $_SESSION['error'] = "ស្នើសុំមិនត្រឹមត្រូវ។";
}

redirect('/event-booking/admin/bookings/index.php');