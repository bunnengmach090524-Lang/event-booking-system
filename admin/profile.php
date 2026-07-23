<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/lang.php';
requireAdmin();

$myId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$myId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) {
    redirect('/event-booking/auth/login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $newAvatar = $me['avatar']; // default: keep old one

    if ($name === '' || $email === '') {
        $errors[] = t('error_name_email_required');
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = t('error_password_min');
    }

    // ===== Avatar upload =====
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);

        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!isset($allowedTypes[$mime])) {
            $errors[] = t('error_avatar_type');
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $errors[] = t('error_avatar_size');
        } else {
            $ext = $allowedTypes[$mime];
            $filename = 'avatar_' . $myId . '_' . time() . '.' . $ext;
            $uploadDir = '../uploads/avatars/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                if ($me['avatar'] && file_exists($uploadDir . $me['avatar'])) {
                    unlink($uploadDir . $me['avatar']);
                }
                $newAvatar = $filename;
            } else {
                $errors[] = t('error_avatar_upload_failed');
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, avatar=? WHERE id=?");
                $stmt->execute([$name, $email, $hashed, $newAvatar, $myId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, avatar=? WHERE id=?");
                $stmt->execute([$name, $email, $newAvatar, $myId]);
            }

            $_SESSION['name'] = $name;
            $_SESSION['avatar'] = $newAvatar;
            logActivity($pdo, 'update_profile', 'បានកែប្រែ Profile ខ្លួនឯង');
            $_SESSION['success'] = t('success_profile_updated');
            redirect('/event-booking/admin/profile.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = t('error_email_exists');
            } else {
                error_log('update_profile error: ' . $e->getMessage());
                $errors[] = t('error_profile_update_generic');
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $me['name'] = $name;
        $me['email'] = $email;
    }
}

require_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
    <i data-lucide="user-circle" class="w-6 h-6"></i> <?= t('profile_page_title') ?>
</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Avatar card (1/3 width) -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 flex flex-col items-center text-center">
        <img id="avatarPreview"
             src="<?= $me['avatar'] ? '../uploads/avatars/' . htmlspecialchars($me['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($me['name']) ?>"
             class="w-28 h-28 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600 mb-4">

        <label for="avatarInput" class="cursor-pointer bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
            <?= t('change_avatar_label') ?>
        </label>
        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/webp" form="profileForm"
            onchange="previewAvatar(this)" class="hidden">
        <p class="text-xs text-gray-400 mt-2"><?= t('avatar_hint_label') ?></p>
    </div>

    <!-- Form fields (2/3 width) -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('name_label') ?></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($me['name']) ?>"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('email_label') ?></label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($me['email']) ?>"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mb-6 lg:w-1/2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('new_password_hint_label') ?></label>
                <input type="password" name="password" minlength="6"
                    autocomplete="new-password"
                    placeholder="••••••••"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                <?= t('save_changes_label') ?>
            </button>
        </form>
    </div>

</div>

<script>
    function previewAvatar(input) {
        const preview = document.getElementById('avatarPreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => preview.src = e.target.result;
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>