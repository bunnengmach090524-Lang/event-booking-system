<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$error = '';

$iconSet = [
    'music'    => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
    'briefcase'=> '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
    'tool'     => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
    'globe'    => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
    'palette'  => '<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
    'pin'      => '<path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16h14v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/>',
    'film'     => '<rect x="2" y="3" width="20" height="18" rx="2"/><path d="M7 3v18M17 3v18M2 8h5M2 16h5M17 8h5M17 16h5"/>',
    'burger'   => '<path d="M3 11h18M3 15h18M4 11a8 4 0 0 1 16 0M4 15a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1"/>',
    'book'     => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
    'gamepad'  => '<line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><line x1="15" y1="13" x2="15.01" y2="13"/><line x1="18" y1="11" x2="18.01" y2="11"/><rect x="2" y="6" width="20" height="12" rx="4"/>',
    'plane'    => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-1 .1-1.3.5l-.4.6c-.4.4-.2 1.1.3 1.4L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.6 5.6c.3.5 1 .7 1.4.3l.6-.4c.4-.3.6-.8.5-1.3z"/>',
    'laptop'   => '<rect x="3" y="4" width="18" height="12" rx="2"/><line x1="2" y1="20" x2="22" y2="20"/>',
];

function renderIcon($key, $iconSet, $class = 'w-5 h-5') {
    $path = $iconSet[$key] ?? $iconSet['pin'];
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . $class . '">' . $path . '</svg>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name = trim($_POST['name'] ?? '');
    $name_km = trim($_POST['name_km'] ?? '');
    $icon = trim($_POST['icon'] ?? 'pin');

    if ($name === '') {
        $error = t('err_category_name_required');
    } elseif ($icon === '' || !array_key_exists($icon, $iconSet)) {
        $icon = 'pin';
    }

    if ($error === '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $error = t('err_category_name_exists');
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, name_km, icon) VALUES (?, ?, ?)");
        $stmt->execute([$name, $name_km !== '' ? $name_km : null, $icon]);

        $_SESSION['success'] = t('success_category_created');
        redirect('/event-booking/admin/categories/index.php');
    }
}

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
    <?= renderIcon('pin', $iconSet, 'w-6 h-6 text-blue-600') ?> <?= t('create_category_page_title') ?>
</h1>

<?php if ($error): ?>
    <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-md text-sm">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="flex gap-4 mb-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('category_name_label') ?></label>
                    <input type="text" name="name" id="nameInput" required maxlength="50"
                        value="<?= isset($name) ? htmlspecialchars($name) : '' ?>"
                        placeholder="<?= htmlspecialchars(t('category_name_placeholder')) ?>"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 text-right"><span id="charCount">0</span>/50</p>
                </div>

                <div class="w-20">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('icon_label') ?></label>
                    <input type="hidden" name="icon" id="iconInput" value="<?= isset($icon) ? htmlspecialchars($icon) : 'pin' ?>">
                    <div class="w-full h-[42px] flex items-center justify-center border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md text-gray-700 dark:text-gray-200">
                        <span id="iconInputDisplay"><?= renderIcon($icon ?? 'pin', $iconSet, 'w-5 h-5') ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('category_name_km_label') ?></label>
                <input type="text" name="name_km" id="nameKmInput" maxlength="100"
                    value="<?= isset($name_km) ? htmlspecialchars($name_km) : '' ?>"
                    placeholder="<?= htmlspecialchars(t('category_name_km_placeholder')) ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= t('optional_translation_hint') ?></p>
            </div>

            <!-- Icon picker -->
            <div class="mb-6">
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2"><?= t('icon_picker_hint') ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($iconSet as $key => $svgPath): ?>
                        <button type="button"
                            data-icon="<?= $key ?>"
                            onclick="pickIcon(this)"
                            class="icon-btn w-11 h-11 flex items-center justify-center rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-gray-600 transition-colors <?= (isset($icon) && $icon === $key) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 text-blue-600' : '' ?>">
                            <?= renderIcon($key, $iconSet, 'w-5 h-5') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" id="submitBtn" <?= empty($name) ? 'disabled' : '' ?>
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition-opacity">
                    <?= t('save_label') ?>
                </button>
                <a href="index.php" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 px-6 py-2 rounded-md hover:bg-gray-300 dark:hover:bg-gray-500">
                    <?= t('cancel_btn_label') ?>
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow flex flex-col items-center justify-center text-center">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4"><?= t('preview_label') ?></p>
        <div id="previewCircle" class="w-16 h-16 flex items-center justify-center bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-full mb-3 transition-transform hover:scale-105">
            <span id="previewIcon"><?= renderIcon($icon ?? 'pin', $iconSet, 'w-7 h-7') ?></span>
        </div>
        <p id="previewName" class="font-semibold text-gray-800 dark:text-gray-100"><?= t('category_label') ?></p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= t('preview_hint_label') ?></p>
    </div>

</div>

<div id="iconTemplates" class="hidden">
    <?php foreach ($iconSet as $key => $svgPath): ?>
        <span data-key="<?= $key ?>"><?= renderIcon($key, $iconSet, 'w-7 h-7') ?></span>
    <?php endforeach; ?>
</div>

<script>
    const nameInput    = document.getElementById('nameInput');
    const iconInput    = document.getElementById('iconInput');
    const previewName  = document.getElementById('previewName');
    const previewIcon  = document.getElementById('previewIcon');
    const iconDisplay  = document.getElementById('iconInputDisplay');
    const charCount    = document.getElementById('charCount');
    const submitBtn    = document.getElementById('submitBtn');
    const iconTemplates = document.getElementById('iconTemplates');
    const defaultPreviewName = <?= json_encode(t('category_label'), JSON_UNESCAPED_UNICODE) ?>;

    function getIconSvg(key) {
        const el = iconTemplates.querySelector(`[data-key="${key}"]`);
        return el ? el.innerHTML : '';
    }

    function updatePreview() {
        previewName.textContent = nameInput.value.trim() || defaultPreviewName;
        charCount.textContent   = nameInput.value.length;
        submitBtn.disabled      = nameInput.value.trim() === '';
    }

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

    nameInput.addEventListener('input', updatePreview);
    updatePreview();
</script>

<?php require_once '../../includes/footer.php'; ?>