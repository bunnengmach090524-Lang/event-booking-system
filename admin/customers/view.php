<?php
require '../../config/database.php';
require '../../includes/header.php';

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    redirect('/event-booking/admin/customers/index.php');
}

$stmt = $pdo->prepare("
    SELECT b.*, e.title as event_title, e.event_date, e.location as event_location
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSpent = array_sum(array_map(fn($b) => $b['status'] === 'paid' ? $b['total_price'] : 0, $bookings));

$statusBadge = [
    'paid'      => 'bg-green-100 text-green-700',
    'pending'   => 'bg-yellow-100 text-yellow-700',
    'refunded'  => 'bg-gray-100 text-gray-600',
    'cancelled' => 'bg-red-100 text-red-700',
];
?>

<a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 mb-5 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> ត្រឡប់ទៅ Customers
</a>

<!-- Profile Card -->
<div class="animate-in bg-white rounded-2xl p-6 border border-gray-100 shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center gap-5">
    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-2xl flex-shrink-0">
        <?= mb_strtoupper(mb_substr($customer['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
    </div>
    <div class="flex-1 min-w-0">
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($customer['name']) ?></h1>
        <p class="text-gray-400 text-sm"><?= htmlspecialchars($customer['email']) ?></p>
        <p class="text-gray-300 text-xs mt-1">ចូលរួមតាំងពី <?= date('d M Y', strtotime($customer['created_at'])) ?></p>
    </div>
    <div class="flex gap-6 sm:border-l sm:pl-6 border-gray-100">
        <div>
            <p class="text-gray-400 text-xs">Bookings</p>
            <p class="text-xl font-bold text-gray-800"><?= count($bookings) ?></p>
        </div>
        <div>
            <p class="text-gray-400 text-xs">Total Spent</p>
            <p class="text-xl font-bold text-blue-600">$<?= number_format($totalSpent, 2) ?></p>
        </div>
    </div>
</div>

<!-- Booking History -->
<div class="animate-in delay-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">📜 ប្រវត្តិ Booking</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">ID</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">Event</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">ទីតាំង</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">ចំនួន</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">តម្លៃ</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-6 py-3 text-xs font-semibold text-gray-500">កាលបរិច្ឆេទកក់</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">មិនទាន់មាន Booking ទេ</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/70 transition">
                    <td class="px-6 py-3.5 text-sm text-gray-500">#<?= $b['id'] ?></td>
                    <td class="px-6 py-3.5">
                        <p class="text-sm text-gray-700"><?= htmlspecialchars($b['event_title']) ?></p>
                        <p class="text-xs text-gray-400"><?= date('d M Y', strtotime($b['event_date'])) ?></p>
                    </td>
                    <td class="px-6 py-3.5 text-sm text-gray-600">
                        <span class="inline-flex items-center gap-1">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400"></i>
                            <?= htmlspecialchars($b['event_location']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-sm text-gray-600"><?= $b['quantity'] ?></td>
                    <td class="px-6 py-3.5 text-sm font-semibold text-gray-800">$<?= number_format($b['total_price'], 2) ?></td>
                    <td class="px-6 py-3.5">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $statusBadge[$b['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                            <?= htmlspecialchars(ucfirst($b['status'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-sm text-gray-500"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../../includes/footer.php'; ?>