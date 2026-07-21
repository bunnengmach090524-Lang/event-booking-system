<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role IN ('admin','super_admin')");
$stmt->execute([$id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    redirect('/event-booking/admin/team/index.php');
}

$myId = (int)$_SESSION['user_id'];

// ក្បួនសិទ្ធិ: Super Admin កែបានទាំងអស់ / Admin ធម្មតាកែបានតែខ្លួនឯង
if (!isSuperAdmin() && $target['id'] !== $myId) {
    $_SESSION['error'] = 'អ្នកគ្មានសិទ្ធិកែប្រែគណនីអ្នកដទៃទេ';
    redirect('/event-booking/admin/team/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';

    try {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
            $stmt->execute([$name, $email, $hashed, $target['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
            $stmt->execute([$name, $email, $target['id']]);
        }

        logActivity($pdo, 'edit_admin', 'បានកែប្រែព័ត៌មាន Admin "' . $name . '"');
        redirect('/event-booking/admin/team/index.php');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = 'អ៊ីមែលនេះត្រូវបានប្រើប្រាស់រួចហើយ';
        } else {
            error_log('edit_admin error: ' . $e->getMessage());
            $_SESSION['error'] = 'មានបញ្ហាក្នុងការកែប្រែ Admin សូមព្យាយាមម្តងទៀត';
        }
        redirect('/event-booking/admin/team/edit.php?id=' . $target['id']);
    }
}

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-600"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    កែប្រែ Admin
</h1>

<div class="bg-white p-6 rounded-lg shadow max-w-lg">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ឈ្មោះ</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($target['name']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($target['email']) ?>"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Password ថ្មី (ទុកទទេ បើមិនប្តូរ)</label>
            <input type="password" name="password" minlength="6"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <?php if (isSuperAdmin()): ?>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">តួនាទី</label>
            <input type="text" disabled value="<?= $target['role'] === 'super_admin' ? '⭐ Super Admin' : 'Admin' ?>"
                class="w-full border border-gray-200 bg-gray-50 rounded-md px-3 py-2 text-gray-500">
            <p class="text-xs text-gray-400 mt-1">ត្រូវប្តូរតួនាទីនៅ Team & Admin page (ប៊ូតុងប្រម៉ូត/ដេម៉ូត)</p>
        </div>
        <?php endif; ?>

        <div class="flex gap-3 mt-6">
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