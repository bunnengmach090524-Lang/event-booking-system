<?php
require '../../config/database.php';
require '../../includes/header.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.id, u.name, u.email, u.created_at, u.status,
           COUNT(b.id) as total_bookings,
           COALESCE(SUM(CASE WHEN b.status = 'paid' THEN b.total_price ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    WHERE u.role = 'customer'
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusBadge = [
    'active'   => ['bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400', t('status_active')],
    'inactive' => ['bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400', t('status_inactive')],
    'banned'   => ['bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400', t('status_banned')],
];
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">👥 <?= t('manage_customers_label') ?></h1>
    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1"><?= sprintf(t('total_customers_matching_label'), count($customers)) ?></p>
</div>

<!-- Search -->
<form method="GET" class="animate-in delay-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 flex gap-3">
    <div class="flex-1">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(t('customer_search_placeholder')) ?>"
            class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4"></i> <?= t('search_btn') ?>
    </button>
    <?php if ($search): ?>
        <a href="index.php" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 text-sm px-2 self-center"><?= t('clear_label') ?></a>
    <?php endif; ?>
</form>

<!-- Customer Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (empty($customers)): ?>
        <div class="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <i data-lucide="users" class="w-12 h-12 text-gray-200 dark:text-gray-700 mx-auto mb-3"></i>
            <p class="text-gray-400 dark:text-gray-500"><?= t('no_customers_match_label') ?></p>
        </div>
    <?php endif; ?>

    <?php foreach ($customers as $i => $c): ?>
    <?php $st = $c['status'] ?? 'active'; [$badgeClass, $badgeLabel] = $statusBadge[$st] ?? $statusBadge['active']; ?>
    <div class="animate-in delay-<?= min($i + 1, 5) ?> group bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                <?= mb_strtoupper(mb_substr($c['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <p class="font-semibold text-gray-800 dark:text-gray-100 truncate"><?= htmlspecialchars($c['name']) ?></p>
                    <span class="flex-shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate"><?= htmlspecialchars($c['email']) ?></p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
                <a href="edit.php?id=<?= $c['id'] ?>" title="<?= htmlspecialchars(t('edit_label')) ?>"
                   class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
                <form method="POST" action="delete.php" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" title="<?= htmlspecialchars(t('delete_label')) ?>"
                        onclick="return confirm('<?= addslashes(t('confirm_delete_customer')) ?>')"
                        class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    </button>
                </form>
            </div>
        </div>
        <div class="flex justify-between items-center pt-3 border-t border-gray-100 dark:border-gray-700 text-sm">
            <div>
                <p class="text-gray-400 dark:text-gray-500 text-xs"><?= t('bookings_label') ?></p>
                <p class="font-bold text-gray-800 dark:text-gray-100"><?= $c['total_bookings'] ?></p>
            </div>
            <div class="text-right">
                <p class="text-gray-400 dark:text-gray-500 text-xs"><?= t('total_spent_label') ?></p>
                <p class="font-bold text-blue-600 dark:text-blue-400">$<?= number_format($c['total_spent'], 2) ?></p>
            </div>
        </div>
        <a href="view.php?id=<?= $c['id'] ?>"
           class="mt-4 w-full flex items-center justify-center gap-1.5 bg-gray-50 dark:bg-gray-700 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/30 text-gray-600 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 text-xs font-semibold py-2 rounded-lg transition-colors">
            <?= t('view_booking_history_label') ?> <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php require '../../includes/footer.php'; ?>