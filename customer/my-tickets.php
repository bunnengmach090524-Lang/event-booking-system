<?php
require '../config/database.php';
require 'header.php';

$stmt = $pdo->prepare("
    SELECT b.*, e.title, e.location, e.event_date, e.image 
    FROM bookings b 
    JOIN events e ON b.event_id = e.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">🎟️ សំបុត្ររបស់ខ្ញុំ</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php if (empty($bookings)): ?>
        <div class="col-span-2 text-center text-gray-400 py-12">
            អ្នកមិនទាន់មានសំបុត្រណាទេ
        </div>
    <?php endif; ?>

    <?php foreach ($bookings as $booking): ?>
    <div class="bg-white rounded-lg shadow p-6 flex gap-4">
        <div class="flex-1">
            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($booking['title']) ?></h3>
            <p class="text-sm text-gray-500">📍 <?= htmlspecialchars($booking['location']) ?></p>
            <p class="text-sm text-gray-500">📅 <?= date('d M Y, h:i A', strtotime($booking['event_date'])) ?></p>
            <p class="text-sm text-gray-500 mt-2">ចំនួន: <?= $booking['quantity'] ?> សំបុត្រ</p>
            <p class="text-sm font-semibold text-blue-600">$<?= number_format($booking['total_price'], 2) ?></p>
            
            <?php if ($booking['is_checked_in']): ?>
                <span class="inline-block mt-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">✅ Checked In</span>
            <?php else: ?>
                <span class="inline-block mt-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">⏳ មិនទាន់ Check-in</span>
            <?php endif; ?>
        </div>

        <?php if ($booking['qr_code']): ?>
        <div class="flex-shrink-0">
            <img src="/event-booking/assets/qrcodes/<?= htmlspecialchars($booking['qr_code']) ?>" class="w-24 h-24">
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php require 'footer.php'; ?>