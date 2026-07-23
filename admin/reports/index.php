<?php
require '../../config/database.php';
require '../../includes/header.php';

// ===== Date Range (Default: ខែនេះ) =====
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// ===== Summary Cards =====
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_bookings,
           COALESCE(SUM(quantity), 0) as total_tickets,
           COALESCE(SUM(total_price), 0) as total_revenue
    FROM bookings
    WHERE status = 'paid' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$avgOrderValue = $summary['total_bookings'] > 0 ? $summary['total_revenue'] / $summary['total_bookings'] : 0;

// ===== Revenue by Category =====
$stmt = $pdo->prepare("
    SELECT e.category,
           COUNT(b.id) as bookings_count,
           COALESCE(SUM(b.quantity), 0) as tickets_sold,
           COALESCE(SUM(b.total_price), 0) as revenue
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.status = 'paid' AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY e.category
    ORDER BY revenue DESC
");
$stmt->execute([$from, $to]);
$byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Revenue by Event =====
$stmt = $pdo->prepare("
    SELECT e.title, e.category,
           COUNT(b.id) as bookings_count,
           COALESCE(SUM(b.quantity), 0) as tickets_sold,
           COALESCE(SUM(b.total_price), 0) as revenue
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.status = 'paid' AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY e.id
    ORDER BY revenue DESC
");
$stmt->execute([$from, $to]);
$byEvent = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryIcons = [
    'Concert' => '🎵', 'Conference' => '💼', 'Workshop' => '🛠️',
    'Sports' => '⚽', 'Exhibition' => '🎨', 'General' => '📌'
];
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 animate-in">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📈 <?= t('report_title') ?></h1>
        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1"><?= t('report_subtitle') ?></p>
    </div>
    <a href="export.php?from=<?= $from ?>&to=<?= $to ?>"
       class="flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 hover:shadow-sm transition-all">
        <i data-lucide="download" class="w-4 h-4 text-green-600 dark:text-green-400"></i> <?= t('btn_export_csv') ?>
    </a>
</div>

<!-- Date Range Filter -->
<form method="GET" class="animate-in delay-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= t('label_from_date') ?></label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
            class="border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= t('label_to_date') ?></label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
            class="border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="filter" class="w-4 h-4"></i> <?= t('btn_filter') ?>
    </button>
    <div class="flex gap-2 ml-auto">
        <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?= t('quick_today') ?></a>
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?= t('quick_this_month') ?></a>
        <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition"><?= t('quick_this_year') ?></a>
    </div>
</form>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <div class="animate-in delay-2 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
        <div class="w-11 h-11 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="dollar-sign" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
        </div>
        <p class="text-gray-400 dark:text-gray-500 text-xs font-medium mb-1"><?= t('stat_total_revenue') ?></p>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">$<?= number_format($summary['total_revenue'], 2) ?></p>
    </div>
    <div class="animate-in delay-3 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
        <div class="w-11 h-11 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="ticket" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
        </div>
        <p class="text-gray-400 dark:text-gray-500 text-xs font-medium mb-1"><?= t('stat_tickets_sold') ?></p>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $summary['total_tickets'] ?></p>
    </div>
    <div class="animate-in delay-4 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
        <div class="w-11 h-11 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="receipt" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
        </div>
        <p class="text-gray-400 dark:text-gray-500 text-xs font-medium mb-1"><?= t('stat_total_bookings') ?></p>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $summary['total_bookings'] ?></p>
    </div>
    <div class="animate-in delay-5 bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
        <div class="w-11 h-11 bg-orange-50 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="trending-up" class="w-5 h-5 text-orange-600 dark:text-orange-400"></i>
        </div>
        <p class="text-gray-400 dark:text-gray-500 text-xs font-medium mb-1"><?= t('stat_avg_order') ?></p>
        <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">$<?= number_format($avgOrderValue, 2) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <!-- Revenue by Category -->
    <div class="animate-in delay-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">🎯 <?= t('revenue_by_category_title') ?></h3>
        </div>
        <?php if (empty($byCategory)): ?>
            <p class="text-gray-400 dark:text-gray-500 text-sm text-center py-10"><?= t('msg_no_data_range') ?></p>
        <?php else: ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400"><?= t('th_category') ?></th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 text-right"><?= t('th_tickets') ?></th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 text-right"><?= t('th_revenue') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byCategory as $cat): ?>
                <tr class="border-b border-gray-50 dark:border-gray-700/50">
                    <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300"><?= $categoryIcons[$cat['category']] ?? '📌' ?> <?= htmlspecialchars($cat['category']) ?></td>
                    <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400 text-right"><?= $cat['tickets_sold'] ?></td>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100 text-right">$<?= number_format($cat['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Revenue by Event -->
    <div class="animate-in delay-4 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">🎪 <?= t('revenue_by_event_title') ?></h3>
        </div>
        <?php if (empty($byEvent)): ?>
            <p class="text-gray-400 dark:text-gray-500 text-sm text-center py-10"><?= t('msg_no_data_range') ?></p>
        <?php else: ?>
        <div class="max-h-96 overflow-y-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700 sticky top-0">
                <tr>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400"><?= t('th_event') ?></th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 text-right"><?= t('th_tickets') ?></th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400 text-right"><?= t('th_revenue') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byEvent as $ev): ?>
                <tr class="border-b border-gray-50 dark:border-gray-700/50">
                    <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300 line-clamp-1"><?= htmlspecialchars($ev['title']) ?></td>
                    <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400 text-right"><?= $ev['tickets_sold'] ?></td>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100 text-right">$<?= number_format($ev['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require '../../includes/footer.php'; ?>