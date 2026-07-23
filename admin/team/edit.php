<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/lang.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role IN ('admin','super_admin')");
$stmt->execute([$id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    redirect('/event-booking/admin/team/index.php');
}

$myId = (int)$_SESSION['user_id'];

if (!isSuperAdmin() && $target['id'] !== $myId) {
    $_SESSION['error'] = t('error_no_permission_edit_other');
    redirect('/event-booking/admin/team/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';

    if ($password !== '' && strlen($password) < 6) {
        $_SESSION['error'] = t('error_password_min');
        redirect('/event-booking/admin/team/edit.php?id=' . $target['id']);
    }

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
        $_SESSION['success'] = t('success_admin_updated');
        redirect('/event-booking/admin/team/index.php');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = t('error_email_exists');
        } else {
            error_log('edit_admin error: ' . $e->getMessage());
            $_SESSION['error'] = t('error_edit_admin_generic');
        }
        redirect('/event-booking/admin/team/edit.php?id=' . $target['id']);
    }
}

require_once '../../includes/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<a href="index.php" class="animate-in inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-5 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= t('team_admin_label') ?>
</a>

<div class="animate-in delay-1 flex items-center gap-4 mb-6">
    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xl font-bold flex-shrink-0 overflow-hidden">
        <?= mb_strtoupper(mb_substr($target['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
    </div>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
            <?= t('edit_admin_page_title') ?>
            <?php if ($target['role'] === 'super_admin'): ?>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">⭐ <?= t('super_admin_badge') ?></span>
            <?php else: ?>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400"><?= t('admin_badge') ?></span>
            <?php endif; ?>
        </h1>
        <p class="text-sm text-gray-400 dark:text-gray-500"><?= htmlspecialchars($target['name']) ?> · <?= htmlspecialchars($target['email']) ?></p>
    </div>
</div>

<div class="animate-in delay-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:p-8 w-full">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('name_label') ?>
            </label>
            <input type="text" name="name" required value="<?= htmlspecialchars($target['name']) ?>"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('email_label') ?>
            </label>
            <input type="email" name="email" required value="<?= htmlspecialchars($target['email']) ?>"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('new_password_hint_label') ?>
            </label>
            <input type="password" name="password" minlength="6" autocomplete="new-password"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <?php if (isSuperAdmin()): ?>
        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="shield" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('role_label') ?>
            </label>
            <input type="text" disabled value="<?= $target['role'] === 'super_admin' ? '⭐ ' . t('super_admin_badge') : t('admin_badge') ?>"
                class="w-full border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5"><?= t('role_change_hint') ?></p>
        </div>
        <?php endif; ?>

        <div class="md:col-span-2 flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-700 mt-2">
            <button type="submit" class="mt-4 flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <i data-lucide="check" class="w-4 h-4"></i> <?= t('save_changes_label') ?>
            </button>
            <a href="index.php" class="mt-4 flex items-center gap-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i data-lucide="x" class="w-4 h-4"></i> <?= t('cancel_btn_label') ?>
            </a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>