<?php
require '../config/database.php';
require '../includes/header.php';

// រាប់ស្ថិតិសង្ខេប
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'paid'")->fetchColumn() ?: 0;

// ស្ថិតិលក់សំបុត្រតាមខែ (6 ខែចុងក្រោយ)
$monthlySales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(quantity) as tickets_sold,
           SUM(total_price) as revenue
    FROM bookings
    WHERE status = 'paid'
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = json_encode(array_column($monthlySales, 'month'));
$chartTickets = json_encode(array_column($monthlySales, 'tickets_sold'));
$chartRevenue = json_encode(array_column($monthlySales, 'revenue'));

// Top 5 Events លក់ដាច់បំផុត
$topEvents = $pdo->query("
    SELECT title, tickets_sold, total_tickets
    FROM events
    ORDER BY tickets_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">📊 Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-500 text-sm">សរុប Events</p>
        <p class="text-3xl font-bold text-blue-600"><?= $totalEvents ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-500 text-sm">សរុប Bookings</p>
        <p class="text-3xl font-bold text-green-600"><?= $totalBookings ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <p class="text-gray-500 text-sm">សរុប Revenue</p>
        <p class="text-3xl font-bold text-purple-600">$<?= number_format($totalRevenue, 2) ?></p>
    </div>
</div>

<a href="/event-booking/admin/events/create.php" 
   class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 inline-block mb-8">
    + បង្កើត Event ថ្មី
</a>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Bar Chart: ការលក់តាមខែ -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="font-semibold text-gray-700 mb-4">📈 ការលក់សំបុត្រតាមខែ</h3>
        <canvas id="salesChart"></canvas>
    </div>

    <!-- Top Events -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="font-semibold text-gray-700 mb-4">🏆 Event លក់ដាច់បំផុត</h3>
        <?php if (empty($topEvents)): ?>
            <p class="text-gray-400 text-sm">មិនទាន់មានទិន្នន័យ</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($topEvents as $e): 
                    $percent = $e['total_tickets'] > 0 ? ($e['tickets_sold'] / $e['total_tickets']) * 100 : 0;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700"><?= htmlspecialchars($e['title']) ?></span>
                        <span class="text-gray-500"><?= $e['tickets_sold'] ?>/<?= $e['total_tickets'] ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percent, 100) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            {
                label: 'ចំនួនសំបុត្រលក់',
                data: <?= $chartTickets ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                yAxisID: 'y',
            },
            {
                label: 'Revenue ($)',
                data: <?= $chartRevenue ?>,
                backgroundColor: 'rgba(168, 85, 247, 0.7)',
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                beginAtZero: true,
            },
            y1: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false }
            }
        }
    }
});
</script>

<?php require '../includes/footer.php'; ?>