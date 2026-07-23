<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/icons.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    redirect('/event-booking/admin/categories/index.php');
}

$error = '';
$name = $_POST['name'] ?? $category['name'];
$icon = $_POST['icon'] ?? $category['icon'];
if (!array_key_exists($icon, $iconSet)) {
    $icon = 'pin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'pin');

    if ($name === '') {
        $error = t('err_category_name_required');
    } elseif (!array_key_exists($icon, $iconSet)) {
        $icon = 'pin';
    }

    if ($error === '' && $name !== $category['name']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = t('err_category_name_exists');
        }
    }

    if ($error === '') {
        $oldName = $category['name'];

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
        $stmt->execute([$name, $icon, $id]);

        if ($name !== $oldName) {
            $stmt = $pdo->prepare("UPDATE events SET category = ? WHERE category = ?");
            $stmt->execute([$name, $oldName]);
        }

        $_SESSION['success'] = t('success_category_updated');
        redirect('/event-booking/admin/categories/index.php');
    }
}

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
    <?= renderIcon('pin', $iconSet, 'w-6 h-6 text-amber-500') ?> <?= t('edit_category_page_title') ?>
</h1>

<?php if ($error): ?>
    <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-md text-sm">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form (2/3 width) -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="flex gap-4 mb-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('category_name_label') ?></label>
                    <input type="text" name="name" id="nameInput" required maxlength="50"
                        value="<?= htmlspecialchars($name) ?>"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 text-right"><span id="charCount"><?= mb_strlen($name) ?></span>/50</p>
                </div>

                <div class="w-20">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('icon_label') ?></label>
                    <input type="hidden" name="icon" id="iconInput" value="<?= htmlspecialchars($icon) ?>">
                    <div class="w-full h-[42px] flex items-center justify-center border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md text-gray-700 dark:text-gray-200">
                        <span id="iconInputDisplay"><?= renderIcon($icon, $iconSet, 'w-5 h-5') ?></span>
                    </div>
                </div>
            </div>

            <!-- Icon picker -->
            <div class="mb-4">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2"><?= t('icon_picker_hint') ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($iconSet as $key => $svgPath): ?>
                        <button type="button"
                            data-icon="<?= $key ?>"
                            onclick="pickIcon(this)"
                            class="icon-btn w-11 h-11 flex items-center justify-center rounded-lg border-2 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors <?= ($icon === $key) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600' : 'border-gray-200 dark:border-gray-600' ?>">
                            <?= renderIcon($key, $iconSet, 'w-5 h-5') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <p class="text-xs text-amber-600 dark:text-amber-400 mb-6"><?= t('category_rename_warning') ?></p>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    <?= t('save_changes_label') ?>
                </button>
                <a href="index.php" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                    <?= t('cancel_btn_label') ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Live Preview (1/3 width) -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex flex-col items-center justify-center text-center">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4"><?= t('preview_label') ?></p>
        <div class="w-16 h-16 flex items-center justify-center bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-full mb-3">
            <span id="previewIcon"><?= renderIcon($icon, $iconSet, 'w-7 h-7') ?></span>
        </div>
        <p id="previewName" class="font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($name) ?></p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= t('preview_hint_label') ?></p>
    </div>

</div>

<div id="iconTemplates" class="hidden">
    <?php foreach ($iconSet as $key => $svgPath): ?>
        <span data-key="<?= $key ?>"><?= renderIcon($key, $iconSet, 'w-7 h-7') ?></span>
    <?php endforeach; ?>
</div>

<script>
    const nameInput  = document.getElementById('nameInput');
    const iconInput  = document.getElementById('iconInput');
    const previewName = document.getElementById('previewName');
    const previewIcon = document.getElementById('previewIcon');
    const iconDisplay = document.getElementById('iconInputDisplay');
    const charCount   = document.getElementById('charCount');
    const iconTemplates = document.getElementById('iconTemplates');
    const defaultPreviewName = <?= json_encode(t('category_label'), JSON_UNESCAPED_UNICODE) ?>;

    function getIconSvg(key) {
        const el = iconTemplates.querySelector(`[data-key="${key}"]`);
        return el ? el.innerHTML : '';
    }

    nameInput.addEventListener('input', () => {
        previewName.textContent = nameInput.value.trim() || defaultPreviewName;
        charCount.textContent = nameInput.value.length;
    });

    function pickIcon(btn) {
        const key = btn.dataset.icon;
        iconInput.value = key;
        previewIcon.innerHTML = getIconSvg(key);
        iconDisplay.innerHTML = getIconSvg(key);

        document.querySelectorAll('.icon-btn').forEach(b => {
            b.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30', 'text-blue-600');
            b.classList.add('border-gray-200', 'dark:border-gray-600');
        });
        btn.classList.remove('border-gray-200', 'dark:border-gray-600');
        btn.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30', 'text-blue-600');
    }
</script>

<?php require_once '../../includes/footer.php'; ?>