<?php
require '../../config/database.php';
require '../../includes/header.php';

$search = trim($_GET['search'] ?? '');
$eventFilter = $_GET['event_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT b.*, e.title as event_title, e.event_date, u.name as customer_name, u.email
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR b.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = is_numeric($search) ? $search : 0;
}
if ($eventFilter !== '') {
    $sql .= " AND b.event_id = ?";
    $params[] = $eventFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// សម្រាប់ Dropdown filter
$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

$statusBadge = [
    'paid'      => 'bg-green-100 text-green-700',
    'pending'   => 'bg-yellow-100 text-yellow-700',
    'refunded'  => 'bg-gray-100 text-gray-600',
    'cancelled' => 'bg-red-100 text-red-700',
];
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800">🎫 គ្រប់គ្រង Bookings</h1>
    <p class="text-gray-400 text-sm mt-1">សរុប <?= count($bookings) ?> Booking ត្រូវនឹងលក្ខខណ្ឌស្វែងរក</p>
</div>

<!-- Filters -->
<form method="GET" class="animate-in delay-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">ស្វែងរក (ឈ្មោះ/Email/ID)</label>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ឧ. Sok Dara"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="min-w-[180px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
        <select name="event_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Event ទាំងអស់</option>
            <?php foreach ($eventsList as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $eventFilter == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="min-w-[150px]">
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">ទាំងអស់</option>
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4"></i> ស្វែងរក
    </button>
    <?php if ($search || $eventFilter || $statusFilter): ?>
        <a href="index.php" class="text-gray-400 hover:text-gray-600 text-sm px-2">សម្អាត</a>
    <?php endif; ?>
</form>

<!-- Table -->
<div class="animate-in delay-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">ID</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">អ្នកកក់</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">Event</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">ចំនួន</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">តម្លៃ</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">Status</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">Check-in</th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500">សកម្មភាព</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-400">មិនមាន Booking ត្រូវនឹងលក្ខខណ្ឌទេ</td></tr>
                <?php endif; ?>

                <?php foreach ($bookings as $b): ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50/70 transition">
                    <td class="px-5 py-3.5 text-sm text-gray-500">#<?= $b['id'] ?></td>
                    <td class="px-5 py-3.5">
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($b['customer_name']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($b['email']) ?></p>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="text-sm text-gray-700 line-clamp-1"><?= htmlspecialchars($b['event_title']) ?></p>
                        <p class="text-xs text-gray-400"><?= date('d M Y', strtotime($b['event_date'])) ?></p>
                    </td>
                    <td class="px-5 py-3.5 text-sm text-gray-600"><?= $b['quantity'] ?></td>
                    <td class="px-5 py-3.5 text-sm font-semibold text-gray-800">$<?= number_format($b['total_price'], 2) ?></td>
                    <td class="px-5 py-3.5">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $statusBadge[$b['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                            <?= htmlspecialchars(ucfirst($b['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <?php if ($b['is_checked_in']): ?>
                            <span class="text-green-600 flex items-center gap-1 text-xs font-medium"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> បាន</span>
                        <?php else: ?>
                            <span class="text-gray-300 flex items-center gap-1 text-xs"><i data-lucide="circle" class="w-3.5 h-3.5"></i> មិនទាន់</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <?php if ($b['status'] === 'pending'): ?>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=paid"
                                   onclick="return confirm('បញ្ជាក់ថាបានទូទាត់ប្រាក់?')"
                                   class="text-green-600 hover:underline">ទូទាត់រួច</a>
                            <?php endif; ?>
                            <?php if ($b['status'] === 'paid'): ?>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=refunded"
                                   onclick="return confirm('បញ្ជាក់ថា Refund ការកក់នេះ?')"
                                   class="text-orange-600 hover:underline">Refund</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require '../../includes/footer.php'; ?>