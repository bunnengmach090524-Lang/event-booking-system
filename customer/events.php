<?php
require '../config/database.php';
require 'header.php';

// Search + Filter
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM events WHERE event_date >= NOW()";
$params = [];

if ($search) {
    $sql .= " AND (title LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">🎉 Events កំពុងលក់សំបុត្រ</h1>

<!-- Search Bar -->
<form method="GET" class="mb-8">
    <div class="flex gap-2">
        <input type="text" name="search" placeholder="ស្វែងរក Event ឬទីតាំង..." value="<?= htmlspecialchars($search) ?>"
            class="flex-1 border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
            ស្វែងរក
        </button>
    </div>
</form>

<!-- Event Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($events)): ?>
        <div class="col-span-3 text-center text-gray-400 py-12">
            មិនទាន់មាន Event ណាទេពេលនេះ
        </div>
    <?php endif; ?>

    <?php foreach ($events as $event): ?>
    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
        <?php if ($event['image']): ?>
            <img src="/event-booking/uploads/events/<?= htmlspecialchars($event['image']) ?>" 
                 class="w-full h-48 object-cover">
        <?php else: ?>
            <div class="w-full h-48 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-4xl">
                🎫
            </div>
        <?php endif; ?>

        <div class="p-5">
            <h3 class="font-bold text-lg text-gray-800 mb-2"><?= htmlspecialchars($event['title']) ?></h3>
            <p class="text-sm text-gray-500 mb-1">📍 <?= htmlspecialchars($event['location']) ?></p>
            <p class="text-sm text-gray-500 mb-3">📅 <?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></p>
            
            <div class="flex justify-between items-center">
                <span class="text-xl font-bold text-blue-600">$<?= number_format($event['price'], 2) ?></span>
                <?php 
                $remaining = $event['total_tickets'] - $event['tickets_sold'];
                if ($remaining > 0): 
                ?>
                    <span class="text-xs text-green-600"><?= $remaining ?> សំបុត្រនៅសល់</span>
                <?php else: ?>
                    <span class="text-xs text-red-600 font-bold">សំបុត្រអស់ហើយ</span>
                <?php endif; ?>
            </div>

            <a href="event-detail.php?id=<?= $event['id'] ?>" 
               class="mt-4 block text-center bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                មើលលម្អិត
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require 'footer.php'; ?>