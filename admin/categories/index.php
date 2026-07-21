<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();
require_once '../../includes/header.php';

// ចំនួន Events ក្នុងមួយ Category (match តាមឈ្មោះ category)
$categories = $pdo->query("
    SELECT c.id, c.name, c.icon,
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
        <h1 class="text-2xl font-bold text-gray-800">🏷️ គ្រប់គ្រង Category</h1>
        <p class="text-gray-400 text-sm mt-1">សរុប <?= count($categories) ?> Category</p>
    </div>
    <a href="create.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
        + បង្កើត Category ថ្មី
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">Icon</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ឈ្មោះ Category</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">ចំនួន Events</th>
                <th class="px-6 py-3 text-sm font-semibold text-gray-600">សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">មិនទាន់មាន Category</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($categories as $cat): ?>
            <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4 text-2xl"><?= htmlspecialchars($cat['icon']) ?></td>
                <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($cat['name']) ?></td>
                <td class="px-6 py-4">
                    <a href="../events/index.php?category=<?= urlencode($cat['name']) ?>"
                       class="text-blue-600 hover:underline text-sm">
                        <?= $cat['events_count'] ?> Event<?= $cat['events_count'] != 1 ? 's' : '' ?>
                    </a>
                </td>
                <td class="px-6 py-4">
                    <a href="edit.php?id=<?= $cat['id'] ?>" class="text-blue-600 hover:underline mr-3">កែប្រែ</a>

                    <?php if ($cat['events_count'] > 0): ?>
                        <span class="text-gray-300 cursor-not-allowed" title="មិនអាចលុបបានទេ ព្រោះមាន Event កំពុងប្រើ Category នេះ">
                            លុប
                        </span>
                    <?php else: ?>
                        <form method="POST" action="delete.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit"
                                onclick="return confirm('តើប្រាកដជាលុប Category នេះ?')"
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