<?php
require '../config/database.php';
require '../includes/header.php';

$message = '';
$messageType = '';
$booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrInput = trim($_POST['qr_data']);

    // Format QR: BOOKING-{id}-EVENT-{event_id}
    if (preg_match('/BOOKING-(\d+)-EVENT-(\d+)/', $qrInput, $matches)) {
        $booking_id = $matches[1];

        $stmt = $pdo->prepare("
            SELECT b.*, e.title, e.event_date, u.name as customer_name 
            FROM bookings b 
            JOIN events e ON b.event_id = e.id 
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $message = "❌ រកមិនឃើញ Booking នេះទេ!";
            $messageType = 'error';
        } elseif ($booking['is_checked_in']) {
            $message = "⚠️ សំបុត្រនេះបាន Check-in រួចហើយ!";
            $messageType = 'warning';
        } elseif ($booking['status'] !== 'paid') {
            $message = "❌ សំបុត្រនេះមិនទាន់ទូទាត់ប្រាក់!";
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET is_checked_in = TRUE WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking['is_checked_in'] = true;

            $message = "✅ Check-in ជោគជ័យ!";
            $messageType = 'success';
        }
    } else {
        $message = "❌ QR Code មិនត្រឹមត្រូវ ឬ Format ខុស!";
        $messageType = 'error';
    }
}
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">📷 Check-in អ្នកចូលរួម</h1>

<div class="bg-white rounded-lg shadow p-6 max-w-xl">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        បញ្ចូល Booking Code (Scan ពី QR Code ឬវាយផ្ទាល់)
    </label>
    <form method="POST" class="flex gap-2">
        <input type="text" name="qr_data" placeholder="BOOKING-1-EVENT-1" required autofocus
            class="flex-1 border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
            Check-in
        </button>
    </form>

    <?php if ($message): ?>
        <?php
        $bgColor = match($messageType) {
            'success' => 'bg-green-100 text-green-700',
            'warning' => 'bg-yellow-100 text-yellow-700',
            'error' => 'bg-red-100 text-red-700',
        };
        ?>
        <div class="mt-4 p-4 rounded-md <?= $bgColor ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($booking): ?>
    <div class="mt-6 border-t pt-6">
        <h3 class="font-semibold text-gray-700 mb-3">ព័ត៌មានសំបុត្រ</h3>
        <div class="bg-gray-50 rounded-lg p-4 space-y-1 text-sm">
            <p><span class="font-medium">អ្នកកក់:</span> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><span class="font-medium">Event:</span> <?= htmlspecialchars($booking['title']) ?></p>
            <p><span class="font-medium">ថ្ងៃ:</span> <?= date('d M Y, h:i A', strtotime($booking['event_date'])) ?></p>
            <p><span class="font-medium">ចំនួនសំបុត្រ:</span> <?= $booking['quantity'] ?></p>
            <p><span class="font-medium">លេខកក់:</span> #<?= $booking['id'] ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>