<?php
require '../config/database.php';
require '../includes/header.php';

// ===== Time Range Filter =====
$range = $_GET['range'] ?? 'monthly'; // daily | monthly | yearly
if (!in_array($range, ['daily', 'monthly', 'yearly'])) $range = 'monthly';

// ===== ស្ថិតិសង្ខេប =====
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'paid'")->fetchColumn() ?: 0;
$cancelledEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'cancelled'")->fetchColumn();

// ===== Check-in Rate =====
$paidBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid'")->fetchColumn();
$checkedInBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'paid' AND is_checked_in = TRUE")->fetchColumn();
$checkinRate = $paidBookings > 0 ? round(($checkedInBookings / $paidBookings) * 100) : 0;

// ===== Event ជិតដល់បំផុត =====
$nextEvent = $pdo->query("
    SELECT title, event_date, location
    FROM events
    WHERE event_date >= NOW() AND status = 'active'
    ORDER BY event_date ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// ===== ការលក់សំបុត្រ តាម Time Range =====
if ($range === 'daily') {
    $groupFormat = '%Y-%m-%d';
    $sinceClause = "created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
} elseif ($range === 'yearly') {
    $groupFormat = '%Y';
    $sinceClause = "created_at >= DATE_SUB(NOW(), INTERVAL 5 YEAR)";
} else {
    $groupFormat = '%Y-%m';
    $sinceClause = "created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
}

$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '$groupFormat') as period, 
           SUM(quantity) as tickets_sold,
           SUM(total_price) as revenue
    FROM bookings
    WHERE status = 'paid' AND $sinceClause
    GROUP BY period
    ORDER BY period ASC
");
$stmt->execute();
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = json_encode(array_column($salesData, 'period'));
$chartTickets = json_encode(array_column($salesData, 'tickets_sold'));
$chartRevenue = json_encode(array_column($salesData, 'revenue'));

// ===== Top 5 Events លក់ដាច់បំផុត =====
$topEvents = $pdo->query("
    SELECT title, tickets_sold, total_tickets
    FROM events
    ORDER BY tickets_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Top 5 ទីតាំង Events ច្រើនបំផុត =====
$topLocations = $pdo->query("
    SELECT location, COUNT(*) as total_events, SUM(tickets_sold) as total_tickets_sold
    FROM events
    GROUP BY location
    ORDER BY total_events DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Events by Category =====
$categoryData = $pdo->query("
    SELECT category, COUNT(*) as total
    FROM events
    GROUP BY category
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$categoryLabels = json_encode(array_column($categoryData, 'category'));
$categoryValues = json_encode(array_column($categoryData, 'total'));

$stats = [
    ['label' => 'សរុប Events', 'value' => $totalEvents, 'icon' => 'calendar-days', 'color' => 'blue'],
    ['label' => 'សរុប Bookings', 'value' => $totalBookings, 'icon' => 'ticket', 'color' => 'green'],
    ['label' => 'សរុប Revenue', 'value' => '$' . number_format($totalRevenue, 2), 'icon' => 'dollar-sign', 'color' => 'purple'],
    ['label' => 'Events បោះបង់', 'value' => $cancelledEvents, 'icon' => 'circle-slash', 'color' => 'red'],
];
$colorMap = [
    'blue'   => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'ring' => 'group-hover:ring-blue-100'],
    'green'  => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'ring' => 'group-hover:ring-green-100'],
    'purple' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'ring' => 'group-hover:ring-purple-100'],
    'red'    => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'ring' => 'group-hover:ring-red-100'],
];
$medals = ['🥇', '🥈', '🥉'];
$categoryColors = ['#3b82f6', '#a855f7', '#f97316', '#22c55e', '#ec4899', '#14b8a6'];
$maxLocationCount = !empty($topLocations) ? max(array_column($topLocations, 'total_events')) : 0;
?>

<div class="mb-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 animate-in">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">📊 Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">ស្វាគមន៍មកវិញ, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?> 👋</p>
    </div>
    <a href="export-bookings.php" 
       class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 hover:shadow-sm transition-all">
        <i data-lucide="download" class="w-4 h-4 text-green-600"></i> Download CSV
    </a>
</div>

<!-- Stat Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <?php foreach ($stats as $i => $s): $c = $colorMap[$s['color']]; ?>
    <div class="animate-in delay-<?= $i + 1 ?> group bg-white p-5 rounded-2xl shadow-sm border border-gray-100 ring-1 ring-transparent <?= $c['ring'] ?> hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 <?= $c['bg'] ?> rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="<?= $s['icon'] ?>" class="w-5 h-5 <?= $c['text'] ?>"></i>
            </div>
        </div>
        <p class="text-gray-400 text-xs font-medium mb-1"><?= $s['label'] ?></p>
        <p class="text-2xl font-bold text-gray-800"><?= $s['value'] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Next Event Banner + Check-in Rate -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    <div class="lg:col-span-2 animate-in delay-2 relative overflow-hidden bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="absolute right-16 bottom-0 w-16 h-16 bg-white/10 rounded-full"></div>
        <?php if ($nextEvent): ?>
            <p class="text-blue-100 text-xs font-semibold uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Event ជិតដល់បំផុត
            </p>
            <h3 class="text-xl font-bold mb-3 relative z-10"><?= htmlspecialchars($nextEvent['title']) ?></h3>
            <div class="flex flex-wrap gap-4 text-sm text-blue-100 relative z-10">
                <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-4 h-4"></i> <?= date('d M Y, h:i A', strtotime($nextEvent['event_date'])) ?></span>
                <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-4 h-4"></i> <?= htmlspecialchars($nextEvent['location']) ?></span>
            </div>
        <?php else: ?>
            <p class="text-blue-100 text-sm relative z-10">មិនទាន់មាន Event ជិតដល់ទេពេលនេះ</p>
        <?php endif; ?>
    </div>

    <!-- Check-in Rate Ring -->
    <div class="animate-in delay-3 bg-white rounded-2xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300 flex flex-col items-center justify-center text-center">
        <div class="relative w-24 h-24 mb-3">
            <svg class="w-24 h-24 -rotate-90">
                <circle cx="48" cy="48" r="40" stroke="#f1f5f9" stroke-width="10" fill="none" />
                <circle cx="48" cy="48" r="40" stroke="url(#ringGradient)" stroke-width="10" fill="none"
                        stroke-linecap="round"
                        stroke-dasharray="<?= 251.2 ?>"
                        stroke-dashoffset="<?= 251.2 - (251.2 * $checkinRate / 100) ?>"
                        style="transition: stroke-dashoffset 1s ease-out;" />
                <defs>
                    <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#3b82f6" />
                        <stop offset="100%" stop-color="#a855f7" />
                    </linearGradient>
                </defs>
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-xl font-bold text-gray-800"><?= $checkinRate ?>%</span>
            </div>
        </div>
        <p class="font-semibold text-gray-700 text-sm">Check-in Rate</p>
        <p class="text-gray-400 text-xs mt-0.5"><?= $checkedInBookings ?>/<?= $paidBookings ?> សំបុត្រ</p>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    <!-- Bar Chart: ការលក់តាមរយៈពេល -->
    <div class="lg:col-span-2 animate-in delay-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                <i data-lucide="trending-up" class="w-4.5 h-4.5 text-blue-600"></i> ការលក់សំបុត្រ
            </h3>
            <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit">
                <a href="?range=daily" class="px-3 py-1.5 rounded-md text-xs font-semibold transition <?= $range === 'daily' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">Daily</a>
                <a href="?range=monthly" class="px-3 py-1.5 rounded-md text-xs font-semibold transition <?= $range === 'monthly' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">Monthly</a>
                <a href="?range=yearly" class="px-3 py-1.5 rounded-md text-xs font-semibold transition <?= $range === 'yearly' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">Yearly</a>
            </div>
        </div>
        <?php if (empty($salesData)): ?>
            <p class="text-gray-400 text-sm text-center py-12">មិនទាន់មានទិន្នន័យលក់សម្រាប់រយៈពេលនេះ</p>
        <?php else: ?>
            <canvas id="salesChart"></canvas>
        <?php endif; ?>
    </div>

    <!-- Category Donut -->
    <div class="animate-in delay-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-4.5 h-4.5 text-purple-600"></i> Events តាម Category
        </h3>
        <?php if (empty($categoryData)): ?>
            <p class="text-gray-400 text-sm">មិនទាន់មានទិន្នន័យ</p>
        <?php else: ?>
            <canvas id="categoryChart"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Top Events + Top Locations -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
    <div class="lg:col-span-2 animate-in delay-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i data-lucide="trophy" class="w-4.5 h-4.5 text-amber-500"></i> Event លក់ដាច់បំផុត
        </h3>
        <?php if (empty($topEvents)): ?>
            <p class="text-gray-400 text-sm">មិនទាន់មានទិន្នន័យ</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                <?php foreach ($topEvents as $i => $e):
                    $percent = $e['total_tickets'] > 0 ? ($e['tickets_sold'] / $e['total_tickets']) * 100 : 0;
                ?>
                <div class="group">
                    <div class="flex justify-between text-sm mb-1.5 items-center">
                        <span class="font-medium text-gray-700 flex items-center gap-2">
                            <span class="text-base w-5 text-center"><?= $medals[$i] ?? ($i + 1) . '.' ?></span>
                            <span class="line-clamp-1"><?= htmlspecialchars($e['title']) ?></span>
                        </span>
                        <span class="text-gray-400 text-xs flex-shrink-0"><?= $e['tickets_sold'] ?>/<?= $e['total_tickets'] ?></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-700 ease-out"
                             style="width: <?= min($percent, 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top Locations -->
    <div class="animate-in delay-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
        <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4.5 h-4.5 text-rose-500"></i> ទីតាំងពេញនិយម
        </h3>
        <?php if (empty($topLocations)): ?>
            <p class="text-gray-400 text-sm">មិនទាន់មានទិន្នន័យ</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($topLocations as $i => $loc):
                    $locPercent = $maxLocationCount > 0 ? ($loc['total_events'] / $maxLocationCount) * 100 : 0;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1.5 items-center gap-2">
                        <span class="font-medium text-gray-700 flex items-center gap-2 min-w-0">
                            <span class="text-base w-5 text-center flex-shrink-0"><?= $medals[$i] ?? ($i + 1) . '.' ?></span>
                            <span class="line-clamp-1"><?= htmlspecialchars($loc['location']) ?></span>
                        </span>
                        <span class="text-gray-400 text-xs flex-shrink-0"><?= $loc['total_events'] ?> event<?= $loc['total_events'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-rose-500 to-orange-400 h-2 rounded-full transition-all duration-700 ease-out"
                             style="width: <?= min($locPercent, 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
<?php if (!empty($salesData)): ?>
new Chart(document.getElementById('salesChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            {
                label: 'ចំនួនសំបុត្រលក់',
                data: <?= $chartTickets ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.75)',
                borderRadius: 6,
                yAxisID: 'y',
            },
            {
                label: 'Revenue ($)',
                data: <?= $chartRevenue ?>,
                backgroundColor: 'rgba(168, 85, 247, 0.75)',
                borderRadius: 6,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        animation: { duration: 800, easing: 'easeOutQuart' },
        plugins: { legend: { labels: { font: { family: 'Poppins' } } } },
        scales: {
            y: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: '#f1f5f9' } },
            y1: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

<?php if (!empty($categoryData)): ?>
new Chart(document.getElementById('categoryChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= $categoryLabels ?>,
        datasets: [{
            data: <?= $categoryValues ?>,
            backgroundColor: <?= json_encode($categoryColors) ?>,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        animation: { duration: 800, easing: 'easeOutQuart' },
        plugins: {
            legend: { position: 'bottom', labels: { font: { family: 'Poppins', size: 11 }, boxWidth: 12, padding: 12 } }
        },
        cutout: '65%'
    }
});
<?php endif; ?>
</script>

<?php require '../includes/footer.php'; ?>