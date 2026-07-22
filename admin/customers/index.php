<?php
require '../../config/database.php';
require '../../includes/header.php';

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.id, u.name, u.email, u.created_at,
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
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800">👥 <?= t('manage_customers_label') ?></h1>
    <p class="text-gray-400 text-sm mt-1"><?= sprintf(t('total_customers_matching_label'), count($customers)) ?></p>
</div>

<!-- Search -->
<form method="GET" class="animate-in delay-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex gap-3">
    <div class="flex-1">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(t('customer_search_placeholder')) ?>"
            class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="search" class="w-4 h-4"></i> <?= t('search_btn') ?>
    </button>
    <?php if ($search): ?>
        <a href="index.php" class="text-gray-400 hover:text-gray-600 text-sm px-2 self-center"><?= t('clear_label') ?></a>
    <?php endif; ?>
</form>

<!-- Customer Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (empty($customers)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-gray-100">
            <i data-lucide="users" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
            <p class="text-gray-400"><?= t('no_customers_match_label') ?></p>
        </div>
    <?php endif; ?>

    <?php foreach ($customers as $i => $c): ?>
    <div class="animate-in delay-<?= min($i + 1, 5) ?> group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                <?= mb_strtoupper(mb_substr($c['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($c['name']) ?></p>
                <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($c['email']) ?></p>
            </div>
        </div>
        <div class="flex justify-between items-center pt-3 border-t border-gray-100 text-sm">
            <div>
                <p class="text-gray-400 text-xs"><?= t('bookings_label') ?></p>
                <p class="font-bold text-gray-800"><?= $c['total_bookings'] ?></p>
            </div>
            <div class="text-right">
                <p class="text-gray-400 text-xs"><?= t('total_spent_label') ?></p>
                <p class="font-bold text-blue-600">$<?= number_format($c['total_spent'], 2) ?></p>
            </div>
        </div>
        <a href="view.php?id=<?= $c['id'] ?>"
           class="mt-4 w-full flex items-center justify-center gap-1.5 bg-gray-50 group-hover:bg-blue-50 text-gray-600 group-hover:text-blue-600 text-xs font-semibold py-2 rounded-lg transition-colors">
            <?= t('view_booking_history_label') ?> <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php require '../../includes/footer.php'; ?>