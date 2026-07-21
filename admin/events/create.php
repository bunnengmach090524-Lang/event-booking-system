<?php
require '../../config/database.php';
require '../../includes/header.php';

$error = '';

$eventCategories = $pdo->query("SELECT name, icon FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ប្រភេទរូបភាពដែលអនុញ្ញាត (extension => real mime type)
const ALLOWED_IMAGE_TYPES = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB

/**
 * Validate an uploaded image file.
 * Returns ['ok' => bool, 'error' => string|null, 'ext' => string|null]
 */
function validateUploadedImage(array $file): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'ការ Upload រូបភាពមានបញ្ហា (upload error)។', 'ext' => null];
    }

    if ($file['size'] <= 0 || $file['size'] > MAX_IMAGE_SIZE) {
        return ['ok' => false, 'error' => 'ទំហំរូបភាពត្រូវតែតិចជាង 5MB។', 'ext' => null];
    }

    // ត្រួតពិនិត្យ extension ពី filename
    $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($originalExt, ALLOWED_IMAGE_TYPES)) {
        return ['ok' => false, 'error' => 'ប្រភេទឯកសារមិនត្រូវបានអនុញ្ញាតទេ។ សូមប្រើ JPG, PNG, GIF ឬ WEBP ។', 'ext' => null];
    }

    // ត្រួតពិនិត្យ MIME type ពិតប្រាកដពីខ្លឹមសារឯកសារ (មិនប្រើ browser-supplied MIME ព្រោះអាចក្លែងបន្លំបាន)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($realMime !== ALLOWED_IMAGE_TYPES[$originalExt]) {
        return ['ok' => false, 'error' => 'ខ្លឹមសារឯកសារមិនត្រូវនឹងប្រភេទរូបភាពដែលអនុញ្ញាតទេ។', 'ext' => null];
    }

    // ត្រួតពិនិត្យបន្ថែមថាឯកសារពិតជារូបភាព (ការពារ polyglot files)
    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'ឯកសារនេះមិនមែនជារូបភាពត្រឹមត្រូវទេ។', 'ext' => null];
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

    $imageName = null;

    // Upload រូបភាព
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $validation = validateUploadedImage($_FILES['image']);

        if (!$validation['ok']) {
            $error = $validation['error'];
        } else {
            // ឈ្មោះឯកសារថ្មី - មិនប្រើ original filename ដើម្បីការពារ path traversal / double extension
            $imageName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $validation['ext'];
            $targetPath = '../../uploads/events/' . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $error = 'មិនអាច Upload រូបភាពបានទេ។ សូមព្យាយាមម្តងទៀត។';
                $imageName = null;
            }
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare("INSERT INTO events (title, category, description, image, location, event_date, price, total_tickets, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $description, $imageName, $location, $event_date, $price, $total_tickets, $_SESSION['user_id']]);

        redirect('/event-booking/admin/events/index.php');
    }
}
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6">➕ បង្កើត Event ថ្មី</h1>

<div class="bg-white p-6 rounded-lg shadow max-w-2xl">
    <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំណងជើង Event</label>
            <input type="text" name="title" required value="<?= isset($title) ? htmlspecialchars($title) : '' ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ប្រភេទ Event</label>
            <select name="category" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php foreach ($eventCategories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>">
                        <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-gray-400 mt-1">មិនឃើញ Category ដែលអ្នកចង់បាន? <a href="../categories/create.php" class="text-blue-600 hover:underline">បង្កើត Category ថ្មី</a></p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ការពិពណ៌នា</label>
            <textarea name="description" rows="4"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ទីតាំង</label>
            <input type="text" name="location" required value="<?= isset($location) ? htmlspecialchars($location) : '' ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ថ្ងៃ + ម៉ោង</label>
                <input type="datetime-local" name="event_date" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">តម្លៃ ($)</label>
                <input type="number" step="0.01" name="price" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ចំនួនសំបុត្រសរុប</label>
            <input type="number" name="total_tickets" required
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">រូបភាព</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"
                class="w-full border border-gray-300 rounded-md px-3 py-2">
            <p class="text-xs text-gray-400 mt-1">អនុញ្ញាតតែ JPG, PNG, GIF, WEBP និងទំហំមិនលើសពី 5MB</p>
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

<?php require '../../includes/footer.php'; ?>