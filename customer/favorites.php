<?php
require '../config/database.php';
require 'header.php';

$stmt = $pdo->prepare(
    "SELECT e.* FROM favorites f
     JOIN events e ON e.id = f.event_id
     WHERE f.user_id = ?
     ORDER BY f.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">❤️ ចំណូលចិត្តរបស់ខ្ញុំ</h1>
        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Event ដែលអ្នកបានរក្សាទុក</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="favoritesGrid">
    <?php if (empty($events)): ?>
        <div class="col-span-3 text-center py-20">
            <i data-lucide="heart-off" class="w-16 h-16 text-gray-300 dark:text-gray-700 mx-auto mb-4"></i>
            <p class="text-gray-400 dark:text-gray-500 mb-4">អ្នកមិនទាន់មាន Event ចំណូលចិត្តទេ</p>
            <a href="events.php" class="inline-flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-100 dark:hover:bg-blue-900 transition">
                មើល Events ទាំងអស់ <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
    <?php endif; ?>

    <?php foreach ($events as $event):
        $remaining = $event['total_tickets'] - $event['tickets_sold'];
        $soldOut = $remaining <= 0;
    ?>
    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300" data-fav-card="<?= $event['id'] ?>">
        <div class="relative overflow-hidden">
            <?php if ($event['image']): ?>
                <img src="/event-booking/uploads/events/<?= htmlspecialchars($event['image']) ?>" 
                     class="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
                <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="ticket" class="w-12 h-12 text-white/80"></i>
                </div>
            <?php endif; ?>

            <button onclick="toggleFavorite(<?= $event['id'] ?>, this, true)"
                    class="absolute top-3 left-3 w-8 h-8 rounded-full bg-white/95 dark:bg-gray-900/90 backdrop-blur flex items-center justify-center hover:scale-110 transition-transform text-red-500"
                    aria-label="Remove from favorites">
                <i data-lucide="heart" class="w-4 h-4" fill="currentColor"></i>
            </button>

            <?php if ($soldOut): ?>
                <div class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                    សំបុត្រអស់
                </div>
            <?php else: ?>
                <div class="absolute top-3 right-3 bg-white/95 dark:bg-gray-900/90 backdrop-blur text-green-600 dark:text-green-400 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1">
                    <i data-lucide="ticket" class="w-3 h-3"></i> <?= $remaining ?> នៅសល់
                </div>
            <?php endif; ?>
        </div>

        <div class="p-5">
            <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-2 line-clamp-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                <?= htmlspecialchars($event['title']) ?>
            </h3>
            <div class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 mb-1">
                <i data-lucide="map-pin" class="w-3.5 h-3.5 flex-shrink-0"></i>
                <span class="line-clamp-1"><?= htmlspecialchars($event['location']) ?></span>
            </div>
            <div class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-500 mb-4">
                <i data-lucide="calendar" class="w-3.5 h-3.5 flex-shrink-0"></i>
                <?= date('d M Y, h:i A', strtotime($event['event_date'])) ?>
            </div>
            
            <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">$<?= number_format($event['price'], 2) ?></span>
                <a href="event-detail.php?id=<?= $event['id'] ?>" 
                   class="flex items-center gap-1.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 group-hover:bg-blue-600 group-hover:text-white px-4 py-2 rounded-lg text-sm font-semibold transition-all">
                    មើលលម្អិត <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require 'footer.php'; ?>