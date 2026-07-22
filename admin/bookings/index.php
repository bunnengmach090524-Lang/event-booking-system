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

$statusLabels = [
    'paid'      => t('status_paid_label'),
    'pending'   => t('status_pending_label'),
    'refunded'  => t('status_refunded_label'),
    'cancelled' => t('status_cancelled_label'),
];
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800">🎫 <?= t('manage_bookings_label') ?></h1>
    <p class="text-gray-400 text-sm mt-1"><?= sprintf(t('total_bookings_matching_label'), count($bookings)) ?></p>
</div>

<!-- Filters -->
<form method="GET" class="animate-in delay-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-medium text-gray-500 mb-1"><?= t('booking_search_label') ?></label>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(t('booking_search_placeholder')) ?>"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="min-w-[180px]">
        <label class="block text-xs font-medium text-gray-500 mb-1"><?= t('event_label') ?></label>
        <select name="event_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value=""><?= t('all_events_option') ?></option>
            <?php foreach ($eventsList as $ev): ?>
                <option value="<?= $ev['id'] ?>" <?= $eventFilter == $ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="min-w-[150px]">
        <label class="block text-xs font-medium text-gray-500 mb-1"><?= t('status_label') ?></label>
        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value=""><?= t('all_label') ?></option>
            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>><?= t('status_paid_label') ?></option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>><?= t('status_pending_label') ?></option>
            <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>><?= t('status_refunded_label') ?></option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>><?= t('status_cancelled_label') ?></option>
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4"></i> <?= t('search_btn') ?>
    </button>
    <?php if ($search || $eventFilter || $statusFilter): ?>
        <a href="index.php" class="text-gray-400 hover:text-gray-600 text-sm px-2"><?= t('clear_label') ?></a>
    <?php endif; ?>
</form>

<!-- Table -->
<div class="animate-in delay-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('id_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('booker_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('event_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('qty_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('price_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('status_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('checkin_label') ?></th>
                    <th class="px-5 py-3 text-xs font-semibold text-gray-500"><?= t('action_label') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-400"><?= t('no_bookings_match_label') ?></td></tr>
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
                            <?= htmlspecialchars($statusLabels[$b['status']] ?? ucfirst($b['status'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <?php if ($b['is_checked_in']): ?>
                            <span class="text-green-600 flex items-center gap-1 text-xs font-medium"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> <?= t('checked_in_yes_label') ?></span>
                        <?php else: ?>
                            <span class="text-gray-300 flex items-center gap-1 text-xs"><i data-lucide="circle" class="w-3.5 h-3.5"></i> <?= t('checked_in_no_label') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3 text-xs font-medium">
                            <?php if ($b['status'] === 'pending'): ?>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=paid"
                                   onclick="return confirm('<?= addslashes(t('confirm_mark_paid')) ?>')"
                                   class="text-green-600 hover:underline"><?= t('mark_paid_label') ?></a>
                            <?php endif; ?>
                            <?php if ($b['status'] === 'paid'): ?>
                                <a href="update-status.php?id=<?= $b['id'] ?>&status=refunded"
                                   onclick="return confirm('<?= addslashes(t('confirm_refund')) ?>')"
                                   class="text-orange-600 hover:underline"><?= t('refund_label') ?></a>
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