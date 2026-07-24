<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/icons.php';
requireAdmin();
require_once '../../includes/header.php';

$categories = $pdo->query("
    SELECT c.id, c.name, c.name_km, c.icon,
           (SELECT COUNT(*) FROM events e WHERE e.category = c.name) as events_count
    FROM categories c
    ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <?= renderIcon('pin', $iconSet, 'w-6 h-6 text-blue-600') ?> <?= t('manage_categories_label') ?>
        </h1>
        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1"><?= sprintf(t('total_categories_label'), count($categories)) ?></p>
    </div>
    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        <?= t('create_new_category_label') ?>
    </a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('icon_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('category_name_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('events_count_label') ?></th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300"><?= t('action_label') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500"><?= t('category_empty_label') ?></td>
                </tr>
            <?php endif; ?>

            <?php foreach ($categories as $cat): ?>
            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4">
                    <div class="w-9 h-9 flex items-center justify-center bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-full">
                        <?= renderIcon($cat['icon'], $iconSet, 'w-5 h-5') ?>
                    </div>
                </td>
                <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100">
                    <?= htmlspecialchars(localized($cat['name_km'] ?? null, $cat['name'])) ?>
                    <?php if (!empty($cat['name_km'])): ?>
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">(<?= htmlspecialchars($cat['name']) ?>)</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4">
                    <a href="../events/index.php?category=<?= urlencode($cat['name']) ?>"
                       class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                        <?= $cat['events_count'] ?> <?= t('events_label') ?>
                    </a>
                </td>
                <td class="px-6 py-4">
                    <a href="edit.php?id=<?= $cat['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline mr-3"><?= t('edit_label') ?></a>

                    <?php if ($cat['events_count'] > 0): ?>
                        <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed" title="<?= htmlspecialchars(t('cannot_delete_category_tooltip')) ?>">
                            <?= t('delete_label') ?>
                        </span>
                    <?php else: ?>
                        <form method="POST" action="delete.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit"
                                onclick="return confirm('<?= addslashes(t('confirm_delete_category')) ?>')"
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