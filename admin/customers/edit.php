<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/lang.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    redirect('/event-booking/admin/customers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = mb_substr(trim($_POST['phone'] ?? ''), 0, 30);
    $status = $_POST['status'] ?? 'active';
    $notes = trim($_POST['notes'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password !== '' && strlen($password) < 6) {
        $_SESSION['error'] = t('error_password_min');
        redirect('/event-booking/admin/customers/edit.php?id=' . $customer['id']);
    }

    if ($password !== '' && $password !== $passwordConfirm) {
        $_SESSION['error'] = t('error_password_mismatch');
        redirect('/event-booking/admin/customers/edit.php?id=' . $customer['id']);
    }

    try {
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=?, notes=?, password=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $status, $notes, $hashed, $customer['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, status=?, notes=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $status, $notes, $customer['id']]);
        }

        logActivity($pdo, 'edit_customer', 'បានកែប្រែព័ត៌មាន Customer "' . $name . '"');
        $_SESSION['success'] = t('success_customer_updated');
        redirect('/event-booking/admin/customers/index.php');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = t('error_email_exists');
        } else {
            error_log('edit_customer error: ' . $e->getMessage());
            $_SESSION['error'] = t('error_edit_admin_generic');
        }
        redirect('/event-booking/admin/customers/edit.php?id=' . $customer['id']);
    }
}

require_once '../../includes/header.php';
?>

<?php if (isset($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<a href="index.php" class="animate-in inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-5 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= t('manage_customers_label') ?>
</a>

<div class="animate-in delay-1 flex items-center gap-4 mb-6">
    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xl font-bold flex-shrink-0 overflow-hidden">
        <?= mb_strtoupper(mb_substr($customer['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
    </div>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= t('edit_customer_page_title') ?></h1>
        <p class="text-sm text-gray-400 dark:text-gray-500"><?= htmlspecialchars($customer['name']) ?> · <?= htmlspecialchars($customer['email']) ?></p>
    </div>
</div>

<div class="animate-in delay-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:p-8 w-full">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('name_label') ?>
            </label>
            <input type="text" name="name" required value="<?= htmlspecialchars($customer['name']) ?>"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('email_label') ?>
            </label>
            <input type="email" name="email" required value="<?= htmlspecialchars($customer['email']) ?>"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('phone_label') ?>
            </label>
            <input type="text" name="phone" maxlength="30" autocomplete="off" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
    class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="shield" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('status_label') ?>
            </label>
            <select name="status"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="active" <?= ($customer['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= t('status_active') ?></option>
                <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>><?= t('status_inactive') ?></option>
                <option value="banned" <?= ($customer['status'] ?? '') === 'banned' ? 'selected' : '' ?>><?= t('status_banned') ?></option>
            </select>
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('new_password_hint_label') ?>
            </label>
            <input type="password" name="password" id="password" minlength="6"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('confirm_password_label') ?>
            </label>
            <input type="password" name="password_confirm" id="password_confirm" minlength="6"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                <i data-lucide="sticky-note" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('admin_notes_label') ?>
            </label>
            <textarea name="notes" rows="3"
                class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($customer['notes'] ?? '') ?></textarea>
        </div>

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

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    const pw = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;
    if (pw !== '' && pw !== confirm) {
        e.preventDefault();
        showToast(<?= json_encode(t('error_password_mismatch')) ?>, 'error');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>