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
        <h1 class="text-2xl font-bold text-gray-800">📈 Reports ផ្ទាំងអស់</h1>
        <p class="text-gray-400 text-sm mt-1">វិភាគទិន្នន័យលក់លម្អិត តាមរយៈពេលកំណត់</p>
    </div>
    <a href="export.php?from=<?= $from ?>&to=<?= $to ?>"
       class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 hover:shadow-sm transition-all">
        <i data-lucide="download" class="w-4 h-4 text-green-600"></i> Export CSV
    </a>
</div>

<!-- Date Range Filter -->
<form method="GET" class="animate-in delay-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">ពីថ្ងៃ</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">ដល់ថ្ងៃ</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-2">
        <i data-lucide="filter" class="w-4 h-4"></i> ត្រង
    </button>
    <div class="flex gap-2 ml-auto">
        <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 hover:text-blue-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition">ថ្ងៃនេះ</a>
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 hover:text-blue-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition">ខែនេះ</a>
        <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>" class="text-xs text-gray-500 hover:text-blue-600 px-3 py-2 rounded-lg hover:bg-gray-50 transition">ឆ្នាំនេះ</a>
    </div>
</form>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <div class="animate-in delay-2 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
        <div class="w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="dollar-sign" class="w-5 h-5 text-purple-600"></i>
        </div>
        <p class="text-gray-400 text-xs font-medium mb-1">Total Revenue</p>
        <p class="text-2xl font-bold text-gray-800">$<?= number_format($summary['total_revenue'], 2) ?></p>
    </div>
    <div class="animate-in delay-3 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="ticket" class="w-5 h-5 text-blue-600"></i>
        </div>
        <p class="text-gray-400 text-xs font-medium mb-1">Tickets Sold</p>
        <p class="text-2xl font-bold text-gray-800"><?= $summary['total_tickets'] ?></p>
    </div>
    <div class="animate-in delay-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
        <div class="w-11 h-11 bg-green-50 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="receipt" class="w-5 h-5 text-green-600"></i>
        </div>
        <p class="text-gray-400 text-xs font-medium mb-1">Total Bookings</p>
        <p class="text-2xl font-bold text-gray-800"><?= $summary['total_bookings'] ?></p>
    </div>
    <div class="animate-in delay-5 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
        <div class="w-11 h-11 bg-orange-50 rounded-xl flex items-center justify-center mb-3">
            <i data-lucide="trending-up" class="w-5 h-5 text-orange-600"></i>
        </div>
        <p class="text-gray-400 text-xs font-medium mb-1">Average Order</p>
        <p class="text-2xl font-bold text-gray-800">$<?= number_format($avgOrderValue, 2) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <!-- Revenue by Category -->
    <div class="animate-in delay-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">🎯 Revenue តាម Category</h3>
        </div>
        <?php if (empty($byCategory)): ?>
            <p class="text-gray-400 text-sm text-center py-10">មិនទាន់មានទិន្នន័យសម្រាប់រយៈពេលនេះ</p>
        <?php else: ?>
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500">Category</th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 text-right">សំបុត្រ</th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byCategory as $cat): ?>
                <tr class="border-b border-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-700"><?= $categoryIcons[$cat['category']] ?? '📌' ?> <?= htmlspecialchars($cat['category']) ?></td>
                    <td class="px-6 py-3 text-sm text-gray-600 text-right"><?= $cat['tickets_sold'] ?></td>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-800 text-right">$<?= number_format($cat['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Revenue by Event -->
    <div class="animate-in delay-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">🎪 Revenue តាម Event</h3>
        </div>
        <?php if (empty($byEvent)): ?>
            <p class="text-gray-400 text-sm text-center py-10">មិនទាន់មានទិន្នន័យសម្រាប់រយៈពេលនេះ</p>
        <?php else: ?>
        <div class="max-h-96 overflow-y-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b border-gray-100 sticky top-0">
                <tr>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500">Event</th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 text-right">សំបុត្រ</th>
                    <th class="px-6 py-2.5 text-xs font-semibold text-gray-500 text-right">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byEvent as $ev): ?>
                <tr class="border-b border-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-700 line-clamp-1"><?= htmlspecialchars($ev['title']) ?></td>
                    <td class="px-6 py-3 text-sm text-gray-600 text-right"><?= $ev['tickets_sold'] ?></td>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-800 text-right">$<?= number_format($ev['revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require '../../includes/footer.php'; ?>