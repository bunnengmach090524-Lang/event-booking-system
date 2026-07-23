<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/lang.php';
requireAdmin();
requireSuperAdmin();

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $siteName = trim($_POST['site_name'] ?? '');
    $currency = trim($_POST['currency'] ?? '');
    $notifyEmail = trim($_POST['notify_email'] ?? '');
    $enableNotif = isset($_POST['enable_email_notifications']) ? '1' : '0';
    $newLogo = $settings['logo'] ?? '';

    if ($siteName === '') {
        $errors[] = t('error_site_name_required');
    }
    if ($notifyEmail !== '' && !filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('error_notify_email_invalid');
    }

    // ===== Logo upload =====
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        finfo_close($finfo);

        $maxSize = 1 * 1024 * 1024; // 1MB

        if (!isset($allowedTypes[$mime])) {
            $errors[] = t('error_logo_type');
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = t('error_logo_size');
        } else {
            $ext = $allowedTypes[$mime];
            $filename = 'logo_' . time() . '.' . $ext;
            $uploadDir = '../uploads/settings/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                if ($settings['logo'] && file_exists($uploadDir . $settings['logo'])) {
                    unlink($uploadDir . $settings['logo']);
                }
                $newLogo = $filename;
            } else {
                $errors[] = t('error_logo_upload_failed');
            }
        }
    }

    if (empty($errors)) {
        try {
            $updates = [
                'site_name' => $siteName,
                'currency' => $currency,
                'logo' => $newLogo,
                'notify_email' => $notifyEmail,
                'enable_email_notifications' => $enableNotif,
            ];

            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($updates as $key => $value) {
                $stmt->execute([$value, $key]);
            }

            logActivity($pdo, 'update_settings', 'បានកែប្រែការកំណត់ប្រព័ន្ធ');
            $_SESSION['success'] = t('success_settings_updated');
            redirect('/event-booking/admin/settings.php');
        } catch (PDOException $e) {
            error_log('update_settings error: ' . $e->getMessage());
            $errors[] = t('error_settings_update_generic');
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $settings['site_name'] = $siteName;
        $settings['currency'] = $currency;
        $settings['notify_email'] = $notifyEmail;
        $settings['enable_email_notifications'] = $enableNotif;
    }
}

require_once '../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
    <i data-lucide="settings" class="w-6 h-6"></i> <?= t('settings_page_title') ?>
</h1>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- ការកំណត់ទូទៅ -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4"><?= t('general_settings_label') ?></h3>

            <div class="flex items-center gap-4 mb-4">
                <?php if (!empty($settings['logo'])): ?>
                <img src="../uploads/settings/<?= htmlspecialchars($settings['logo']) ?>" class="w-14 h-14 rounded-lg object-contain border border-gray-200 dark:border-gray-600 bg-white">
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('logo_label') ?></label>
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                        class="text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 dark:file:bg-blue-900/30 file:text-blue-700 dark:file:text-blue-300 file:text-sm">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('site_name_label') ?></label>
                <input type="text" name="site_name" required value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('currency_label') ?></label>
                <select name="currency" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2">
                    <?php foreach (['USD' => '$ USD', 'KHR' => '៛ KHR'] as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($settings['currency'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- ការជូនដំណឹង -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4"><?= t('notifications_settings_label') ?></h3>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('notify_email_label') ?></label>
                <input type="email" name="notify_email" value="<?= htmlspecialchars($settings['notify_email'] ?? '') ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-400 mt-1"><?= t('notify_email_hint') ?></p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="enable_email_notifications" id="enable_notif"
                    <?= ($settings['enable_email_notifications'] ?? '1') === '1' ? 'checked' : '' ?>
                    class="w-4 h-4 rounded border-gray-300">
                <label for="enable_notif" class="text-sm text-gray-700 dark:text-gray-300"><?= t('enable_email_notif_label') ?></label>
            </div>
        </div>

    </div>

    <div class="mt-6">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
            <?= t('save_settings_label') ?>
        </button>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>