<?php
require '../../config/database.php';
require '../../includes/functions.php';
require '../../includes/icons.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    redirect('/event-booking/admin/events/index.php');
}

$error = '';

$eventCategories = $pdo->query("SELECT name, icon FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

const ALLOWED_IMAGE_TYPES = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

function validateUploadedImage(array $file): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('err_upload_generic'), 'ext' => null];
    }

    if ($file['size'] <= 0 || $file['size'] > MAX_IMAGE_SIZE) {
        return ['ok' => false, 'error' => t('err_image_too_large'), 'ext' => null];
    }

    $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($originalExt, ALLOWED_IMAGE_TYPES)) {
        return ['ok' => false, 'error' => t('err_image_type_not_allowed'), 'ext' => null];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($realMime !== ALLOWED_IMAGE_TYPES[$originalExt]) {
        return ['ok' => false, 'error' => t('err_image_mime_mismatch'), 'ext' => null];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => t('err_image_invalid'), 'ext' => null];
    }

    return ['ok' => true, 'error' => null, 'ext' => $originalExt];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_date = $_POST['event_date'];
    $price = $_POST['price'];
    $total_tickets = $_POST['total_tickets'];

    $imageName = $event['image'];
    $newImageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $validation = validateUploadedImage($_FILES['image']);

        if (!$validation['ok']) {
            $error = $validation['error'];
        } else {
            $newImageName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $validation['ext'];
            $targetPath = '../../uploads/events/' . $newImageName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                if ($event['image'] && file_exists('../../uploads/events/' . $event['image'])) {
                    unlink('../../uploads/events/' . $event['image']);
                }
                $imageName = $newImageName;
            } else {
                $error = t('err_image_upload_failed');
            }
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare("UPDATE events SET title=?, category=?, description=?, image=?, location=?, event_date=?, price=?, total_tickets=? WHERE id=?");
        $stmt->execute([$title, $category, $description, $imageName, $location, $event_date, $price, $total_tickets, $id]);

        redirect('/event-booking/admin/events/index.php');
    }

    $event['title'] = $title;
    $event['category'] = $category;
    $event['description'] = $description;
    $event['location'] = $location;
    $event['event_date'] = $event_date;
    $event['price'] = $price;
    $event['total_tickets'] = $total_tickets;
}

require '../../includes/header.php';

$currentIcon = 'pin';
foreach ($eventCategories as $cat) {
    if ($cat['name'] === $event['category']) {
        $currentIcon = $cat['icon'];
        break;
    }
}
?>

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6"><?= t('edit_event_page_title') ?></h1>

<div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
    <?php if ($error): ?>
        <div class="mb-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-md text-sm">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('event_title_label') ?></label>
            <input type="text" name="title" required value="<?= htmlspecialchars($event['title']) ?>"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <!-- Custom category dropdown (supports SVG icons) -->
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('event_category_label') ?></label>
                <input type="hidden" name="category" id="categoryInput" value="<?= htmlspecialchars($event['category']) ?>">

                <button type="button" id="categoryDropdownBtn"
                    class="w-full flex items-center justify-between border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span id="categoryDropdownLabel" class="flex items-center gap-2">
                        <?= renderIcon($currentIcon, $iconSet, 'w-4 h-4') ?>
                        <?= htmlspecialchars($event['category']) ?>
                    </span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div id="categoryDropdownList"
                    class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    <?php foreach ($eventCategories as $cat): ?>
                        <button type="button"
                            data-name="<?= htmlspecialchars($cat['name']) ?>"
                            onclick="pickCategory(this)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-left text-gray-800 dark:text-gray-100 hover:bg-blue-50 dark:hover:bg-gray-600 <?= $cat['name'] === $event['category'] ? 'bg-blue-50 dark:bg-blue-900/30' : '' ?>">
                            <?= renderIcon($cat['icon'], $iconSet, 'w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0') ?>
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= t('no_category_found_prefix') ?> <a href="../categories/create.php" class="text-blue-600 dark:text-blue-400 hover:underline"><?= t('create_new_category_label') ?></a></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('location_label') ?></label>
                <input type="text" name="location" required value="<?= htmlspecialchars($event['location']) ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php if (!empty($event['location'])): ?>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($event['location']) ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1.5">
                    <i data-lucide="map-pin" class="w-3 h-3"></i> <?= t('view_on_map_label') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('description_label') ?></label>
            <textarea name="description" rows="4"
                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('date_time_label') ?></label>
                <input type="datetime-local" name="event_date" required 
                    value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('price_dollar_label') ?></label>
                <input type="number" step="0.01" name="price" required value="<?= $event['price'] ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('total_tickets_label') ?></label>
                <input type="number" name="total_tickets" required value="<?= $event['total_tickets'] ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2"><?= t('current_image_label') ?></p>
                <img id="imagePreview"
                    src="<?= $event['image'] ? '/event-booking/uploads/events/' . htmlspecialchars($event['image']) : '' ?>"
                    class="w-full max-w-xs rounded border border-gray-200 dark:border-gray-600 <?= $event['image'] ? '' : 'hidden' ?>"
                    onerror="this.classList.add('hidden')">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('change_image_label') ?></label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1"><?= t('image_allowed_types_label') ?></p>
            </div>
        </div>

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

<script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    const categoryBtn   = document.getElementById('categoryDropdownBtn');
    const categoryList  = document.getElementById('categoryDropdownList');
    const categoryLabel = document.getElementById('categoryDropdownLabel');
    const categoryInput = document.getElementById('categoryInput');

    categoryBtn.addEventListener('click', () => {
        categoryList.classList.toggle('hidden');
    });

    function pickCategory(btn) {
        categoryInput.value = btn.dataset.name;
        categoryLabel.innerHTML = btn.innerHTML;
        categoryList.classList.add('hidden');
    }

    document.addEventListener('click', (e) => {
        if (!categoryBtn.contains(e.target) && !categoryList.contains(e.target)) {
            categoryList.classList.add('hidden');
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>