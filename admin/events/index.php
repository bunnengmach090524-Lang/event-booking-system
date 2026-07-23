<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

$locationFilter = trim($_GET['location'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$locations = $pdo->query("SELECT DISTINCT location FROM events ORDER BY location ASC")->fetchAll(PDO::FETCH_COLUMN);
$allCategories = $pdo->query("SELECT name, icon FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">🎫 <?= t('manage_events_label') ?></h1>
    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <?= t('create_new_event_label') ?>
    </a>
</div>

<!-- Location + Category Filter -->
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-5 flex flex-wrap items-center gap-3">
    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
        📍 <?= t('filter_by_location_label') ?>
    </label>
    <select name="location" onchange="this.form.submit()"
        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value=""><?= t('all_locations_option') ?></option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $locationFilter === $loc ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
        🏷️ <?= t('filter_by_category_label') ?>
    </label>
    <select name="category" onchange="this.form.submit()"
        class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value=""><?= t('all_categories_option') ?></option>
        <?php foreach ($allCategories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $categoryFilter === $cat['name'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($locationFilter !== '' || $categoryFilter !== ''): ?>
        <a href="index.php" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"><?= t('clear_filter_label') ?></a>
    <?php endif; ?>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('title_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('category_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('location_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('date_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('price_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('tickets_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('status_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('action_label') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500"><?= t('event_empty_label') ?></td>
                </tr>
            <?php endif; ?>

            <?php foreach ($events as $event): ?>
            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars($event['title']) ?></td>
                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1.5 bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 px-2.5 py-1 rounded-full text-xs font-medium text-gray-600 dark:text-gray-300">
                        <?= $categoryIcons[$event['category']] ?? '📌' ?> <?= htmlspecialchars($event['category']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500"></i>
                        <?= htmlspecialchars($event['location']) ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-500 dark:text-gray-400"><?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></td>
                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">$<?= number_format($event['price'], 2) ?></td>
                <td class="px-6 py-4 text-gray-500 dark:text-gray-400"><?= $event['tickets_sold'] ?>/<?= $event['total_tickets'] ?></td>
                <td class="px-6 py-4">
                    <?php if (($event['status'] ?? 'active') === 'cancelled'): ?>
                        <span class="bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-xs font-semibold px-2.5 py-1 rounded-full"><?= t('status_cancelled_label') ?></span>
                    <?php else: ?>
                        <span class="bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 text-xs font-semibold px-2.5 py-1 rounded-full"><?= t('status_active_label') ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <a href="edit.php?id=<?= $event['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline mr-3"><?= t('edit_label') ?></a>

                    <?php if ($event['tickets_sold'] > 0): ?>
                        <?php if (($event['status'] ?? 'active') !== 'cancelled'): ?>
                            <form method="POST" action="cancel.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit"
                                    onclick="return confirm('<?= addslashes(t('confirm_cancel_event')) ?>')"
                                    class="text-orange-600 dark:text-orange-400 hover:underline"><?= t('cancel_action_label') ?></button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="reactivate.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit"
                                    onclick="return confirm('<?= addslashes(t('confirm_reactivate_event')) ?>')"
                                    class="text-green-600 dark:text-green-400 hover:underline"><?= t('reactivate_label') ?></button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST" action="delete.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                            <button type="submit"
                                onclick="return confirm('<?= addslashes(t('confirm_delete_generic')) ?>')"
                                class="text-red-600 dark:text-red-400 hover:underline"><?= t('delete_label') ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>