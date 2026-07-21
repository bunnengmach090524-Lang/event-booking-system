<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// ===== Location + Category Filter =====
$locationFilter = trim($_GET['location'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$locations = $pdo->query("SELECT DISTINCT location FROM events ORDER BY location ASC")->fetchAll(PDO::FETCH_COLUMN);
$allCategories = $pdo->query("SELECT name, icon FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Map category name => icon (used for badges in the table)
$categoryIcons = array_column($allCategories, 'icon', 'name');

$where = [];
$params = [];
if ($locationFilter !== '') {
    $where[] = "location = ?";
    $params[] = $locationFilter;
}
if ($categoryFilter !== '') {
    $where[] = "category = ?";
    $params[] = $categoryFilter;
}

$sql = "SELECT * FROM events";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY event_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">🎫 គ្រប់គ្រង Events</h1>
    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        + បង្កើត Event ថ្មី
    </a>
</div>

<!-- Location + Category Filter -->
<form method="GET" class="bg-white rounded-lg shadow p-4 mb-5 flex flex-wrap items-center gap-3">
    <label class="text-sm font-semibold text-gray-600 flex items-center gap-1.5">
        📍 ត្រងតាមទីតាំង:
    </label>
    <select name="location" onchange="this.form.submit()"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">-- គ្រប់ទីតាំង --</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $locationFilter === $loc ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="text-sm font-semibold text-gray-600 flex items-center gap-1.5">
        🏷️ ត្រងតាមប្រភេទ:
    </label>
    <select name="category" onchange="this.form.submit()"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">-- គ្រប់ប្រភេទ --</option>
        <?php foreach ($allCategories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $categoryFilter === $cat['name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($locationFilter !== '' || $categoryFilter !== ''): ?>
        <a href="index.php" class="text-sm text-gray-400 hover:text-gray-600">សម្អាត Filter</a>
    <?php endif; ?>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ចំណងជើង</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ប្រភេទ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ទីតាំង</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ថ្ងៃ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">តម្លៃ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">សំបុត្រ</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ស្ថានភាព</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">មិនទាន់មាន Event</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($events as $event): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($event['title']) ?></td>
                <td class="px-6 py-4 text-gray-500">
                    <span class="inline-flex items-center gap-1.5 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded-full text-xs font-medium text-gray-600">
                        <?= $categoryIcons[$event['category']] ?? '📌' ?> <?= htmlspecialchars($event['category']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400"></i>
                        <?= htmlspecialchars($event['location']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-500"><?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></td>
                <td class="px-6 py-4 text-gray-500">$<?= number_format($event['price'], 2) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= $event['tickets_sold'] ?>/<?= $event['total_tickets'] ?></td>
                <td class="px-6 py-4">
                    <?php if (($event['status'] ?? 'active') === 'cancelled'): ?>
                        <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2.5 py-1 rounded-full">បោះបង់</span>
                    <?php else: ?>
                        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full">សកម្ម</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <a href="edit.php?id=<?= $event['id'] ?>" class="text-blue-600 hover:underline mr-3">កែប្រែ</a>

                    <?php if ($event['tickets_sold'] > 0): ?>
                        <?php if (($event['status'] ?? 'active') !== 'cancelled'): ?>
                            <form method="POST" action="cancel.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit"
                                    onclick="return confirm('Event នេះមានការកក់សំបុត្ររួចហើយ។ តើប្រាកដជាបោះបង់ (Cancel)?')"
                                    class="text-orange-600 hover:underline">បោះបង់</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="reactivate.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit"
                                    onclick="return confirm('តើប្រាកដជាធ្វើឲ្យ Event នេះសកម្មឡើងវិញ?')"
                                    class="text-green-600 hover:underline">ធ្វើសកម្មឡើងវិញ</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST" action="delete.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit"
                                onclick="return confirm('តើប្រាកដជាលុប?')"
                                class="text-red-600 hover:underline">លុប</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>