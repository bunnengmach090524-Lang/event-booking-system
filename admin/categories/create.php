<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

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

    if ($error === '') {
        // ត្រួតពិនិត្យថាឈ្មោះនេះមានរួចហើយឬអត់ (ធ្វើឲ្យប្រាកដសារ error ច្បាស់ជាង DB unique constraint)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Category ឈ្មោះនេះមានរួចហើយ។';
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
        $stmt->execute([$name, $icon]);

        $_SESSION['success'] = 'Category ត្រូវបានបង្កើតដោយជោគជ័យ។';
        redirect('/event-booking/admin/categories/index.php');
    }
}

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">➕ បង្កើត Category ថ្មី</h1>

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
                value="<?= isset($name) ? htmlspecialchars($name) : '' ?>"
                placeholder="ឧ. Concert, Workshop..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Icon (Emoji)</label>
            <input type="text" name="icon" maxlength="10"
                value="<?= isset($icon) ? htmlspecialchars($icon) : '📌' ?>"
                placeholder="🎵"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-gray-400 mt-1">Copy-paste emoji ណាមួយ (ឧ. 🎵 💼 🛠️ ⚽ 🎨 📌 🎬 🍔)</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                រក្សាទុក
            </button>
            <a href="index.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-300">
                បោះបង់
            </a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>