<?php
require '../../config/database.php';
require '../../includes/header.php';

$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">🎫 គ្រប់គ្រង Events</h1>
    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        + បង្កើត Event ថ្មី
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ចំណងជើង</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ថ្ងៃ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">តម្លៃ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">សំបុត្រ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">មិនទាន់មាន Event</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($events as $event): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($event['title']) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></td>
                <td class="px-6 py-4 text-gray-500">$<?= number_format($event['price'], 2) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= $event['tickets_sold'] ?>/<?= $event['total_tickets'] ?></td>
                <td class="px-6 py-4">
                    <a href="edit.php?id=<?= $event['id'] ?>" class="text-blue-600 hover:underline mr-3">កែប្រែ</a>
                    <a href="delete.php?id=<?= $event['id'] ?>" 
                       onclick="return confirm('តើប្រាកដជាលុប?')"
                       class="text-red-600 hover:underline">លុប</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require '../../includes/footer.php'; ?>