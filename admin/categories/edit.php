<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    redirect('/event-booking/admin/categories/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '📌');

    if ($name === '') {
        $error = 'សូមបញ្ចូលឈ្មោះ Category។';
    } elseif ($icon === '') {
        $icon = '📌';
    }

    if ($error === '' && $name !== $category['name']) {
        // ត្រួតពិនិត្យថាឈ្មោះថ្មីមិនស្ទួនជាមួយ category ផ្សេងទៀត
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Category ឈ្មោះនេះមានរួចហើយ។';
        }
    }

    if ($error === '') {
        $oldName = $category['name'];

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $icon, $id]);

        // Cascade: ប្តូរឈ្មោះ category នៅលើ Events ទាំងអស់ដែលកំពុងប្រើឈ្មោះចាស់
        if ($name !== $oldName) {
            $stmt = $pdo->prepare("UPDATE events SET category = ? WHERE category = ?");
            $stmt->execute([$name, $oldName]);
        }

        $_SESSION['success'] = 'Category ត្រូវបានកែប្រែដោយជោគជ័យ។';
        redirect('/event-booking/admin/categories/index.php');
    }
}

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">✏️ កែប្រែ Category</h1>

<div class="bg-white p-6 rounded-lg shadow max-w-md">
    <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ឈ្មោះ Category</label>
            <input type="text" name="name" required maxlength="50"
                value="<?= htmlspecialchars($_POST['name'] ?? $category['name']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Emoji)</label>
            <input type="text" name="icon" maxlength="10"
                value="<?= htmlspecialchars($_POST['icon'] ?? $category['icon']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <p class="text-xs text-amber-600 mb-6">⚠️ បើប្តូរឈ្មោះ Category, Events ទាំងអស់ដែលកំពុងប្រើ Category នេះ នឹងផ្លាស់ប្តូរឈ្មោះតាមស្វ័យប្រវត្តិ។</p>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                រក្សាទុកការកែប្រែ
            </button>
            <a href="index.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-300">
                បោះបង់
            </a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>