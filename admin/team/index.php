<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/lang.php';
requireAdmin();

$myId = (int)$_SESSION['user_id'];
$iAmSuperAdmin = isSuperAdmin();

// បន្ថែម Admin ថ្មី — មានតែ Super Admin ប៉ុណ្ណោះទេអាចធ្វើបាន
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    csrfCheck();

    if (!$iAmSuperAdmin) {
        $_SESSION['error'] = t('error_no_permission_add_admin');
        redirect('/event-booking/admin/team/index.php');
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $_SESSION['error'] = t('error_password_min');
        redirect('/event-booking/admin/team/index.php');
    }

    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$name, $email, $hashed]);

        logActivity($pdo, 'create_admin', 'បានបន្ថែម Admin ថ្មី "' . $name . '"');
        $_SESSION['success'] = t('success_admin_added');
    } catch (PDOException $e) {
        $_SESSION['error'] = $e->getCode() == 23000
            ? t('error_email_exists')
            : t('error_add_admin_generic');
    }
    redirect('/event-booking/admin/team/index.php');
}

// សំខាន់: Super Admin ឃើញ Admin ទាំងអស់, Admin ធម្មតាឃើញតែគណនីខ្លួនឯង
if ($iAmSuperAdmin) {
    $admins = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role IN ('admin','super_admin') ORDER BY created_at DESC")
                  ->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$myId]);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$superAdminCount = countSuperAdmins($pdo);

require_once '../../includes/header.php';
?>

<?php if (!empty($_SESSION['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['success']) ?>, 'success'));</script>
<?php unset($_SESSION['success']); endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($_SESSION['error']) ?>, 'error'));</script>
<?php unset($_SESSION['error']); endif; ?>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
        <i data-lucide="settings" class="w-6 h-6"></i> <?= t('team_admin_page_title') ?>
    </h1>
    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1"><?= t('team_admin_subtitle') ?></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- បញ្ជី Admin -->
    <div class="animate-in delay-1 <?= $iAmSuperAdmin ? 'lg:col-span-2' : 'lg:col-span-3' ?> bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
            <i data-lucide="users" class="w-4.5 h-4.5 text-gray-400"></i>
            <?= $iAmSuperAdmin ? sprintf(t('all_admins_label'), count($admins)) : t('my_account_label') ?>
        </h3>

        <?php foreach ($admins as $admin): ?>
            <div class="flex items-center justify-between py-3.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                        <?= mb_strtoupper(mb_substr($admin['name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                    </div>
                    <div class="min-w-0">
                        <div class="font-medium text-gray-800 dark:text-gray-100 truncate flex items-center gap-1.5">
                            <?= htmlspecialchars($admin['name']) ?>
                            <?php if ($admin['role'] === 'super_admin'): ?>
                                <span class="flex-shrink-0 inline-flex items-center gap-1 text-[11px] font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 px-2 py-0.5 rounded-full">
                                    ⭐ <?= t('super_admin_badge') ?>
                                </span>
                            <?php else: ?>
                                <span class="flex-shrink-0 text-[11px] font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full"><?= t('admin_badge') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 truncate"><?= htmlspecialchars($admin['email']) ?></div>
                    </div>
                </div>

                <div class="flex items-center gap-1 flex-shrink-0">
                    <span class="text-xs text-gray-400 dark:text-gray-500 mr-2 hidden sm:inline"><?= date('d M Y', strtotime($admin['created_at'])) ?></span>

                    <?php if ($iAmSuperAdmin || $admin['id'] === $myId): ?>
                        <a href="edit.php?id=<?= $admin['id'] ?>" title="<?= htmlspecialchars(t('edit_label')) ?>"
                           class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>

                    <?php if ($iAmSuperAdmin): ?>
                        <form method="POST" action="promote.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                            <?php if ($admin['role'] === 'super_admin'): ?>
                                <?php if ($superAdminCount > 1): ?>
                                    <input type="hidden" name="new_role" value="admin">
                                    <button type="submit" title="<?= htmlspecialchars(t('demote_tooltip')) ?>"
                                        onclick="return confirm('<?= addslashes(sprintf(t('demote_confirm'), htmlspecialchars($admin['name']))) ?>')"
                                        class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-orange-600 dark:hover:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/30 transition">
                                        <i data-lucide="arrow-down" class="w-4 h-4"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="p-1.5 text-gray-200 dark:text-gray-700" title="<?= htmlspecialchars(t('cannot_demote_last_super_tooltip')) ?>">
                                        <i data-lucide="arrow-down" class="w-4 h-4"></i>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="hidden" name="new_role" value="super_admin">
                                <button type="submit" title="<?= htmlspecialchars(t('promote_tooltip')) ?>"
                                    onclick="return confirm('<?= addslashes(sprintf(t('promote_confirm'), htmlspecialchars($admin['name']))) ?>')"
                                    class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 transition">
                                    <i data-lucide="arrow-up" class="w-4 h-4"></i>
                                </button>
                            <?php endif; ?>
                        </form>

                        <?php if ($admin['id'] !== $myId): ?>
                            <form method="POST" action="delete.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                <button type="submit" title="<?= htmlspecialchars(t('delete_label')) ?>"
                                    onclick="return confirm('<?= addslashes(sprintf(t('delete_admin_confirm'), htmlspecialchars($admin['name']))) ?>')"
                                    class="p-1.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ថែម Admin ថ្មី — Super Admin ប៉ុណ្ណោះ -->
    <?php if ($iAmSuperAdmin): ?>
    <div class="animate-in delay-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 h-fit">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4.5 h-4.5 text-gray-400"></i>
            <?= t('add_new_admin_label') ?>
        </h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">

            <div class="mb-4">
                <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('name_label') ?>
                </label>
                <input type="text" name="name" required
                    class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    <i data-lucide="mail" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('email_label') ?>
                </label>
                <input type="email" name="email" required
                    class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-2">
                <label class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i> <?= t('password_label') ?>
                </label>
                <input type="password" name="password" required minlength="6"
                    class="w-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-4"><?= t('password_min_hint') ?></p>

            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                <i data-lucide="user-plus" class="w-4 h-4"></i> <?= t('add_admin_button') ?>
            </button>
        </form>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 flex items-start gap-1.5">
            <i data-lucide="info" class="w-3.5 h-3.5 mt-0.5 flex-shrink-0"></i>
            <span><?= t('add_admin_hint') ?></span>
        </p>
    </div>
    <?php else: ?>
    <div class="animate-in delay-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-2xl p-6 h-fit flex items-start gap-3">
        <i data-lucide="info" class="w-4.5 h-4.5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5"></i>
        <p class="text-sm text-blue-700 dark:text-blue-300"><?= t('super_admin_only_notice') ?></p>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>