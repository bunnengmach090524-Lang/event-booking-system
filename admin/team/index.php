<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$myId = (int)$_SESSION['user_id'];
$iAmSuperAdmin = isSuperAdmin();

// បន្ថែម Admin ថ្មី
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    csrfCheck();

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password ត្រូវមានយ៉ាងហោចណាស់ 6 តួអក្សរ';
        redirect('/event-booking/admin/team/index.php');
    }

    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
        $stmt->execute([$name, $email, $hashed]);

        logActivity($pdo, 'create_admin', 'បានបន្ថែម Admin ថ្មី "' . $name . '"');
        $_SESSION['success'] = 'បានបន្ថែម Admin ដោយជោគជ័យ';
    } catch (PDOException $e) {
        $_SESSION['error'] = $e->getCode() == 23000
            ? 'អ៊ីមែលនេះត្រូវបានប្រើប្រាស់រួចហើយ'
            : 'មានបញ្ហាក្នុងការបន្ថែម Admin';
    }
    redirect('/event-booking/admin/team/index.php');
}

// សំខាន់: filter យកតែ role admin/super_admin មិនរួម customer ទេ
$admins = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role IN ('admin','super_admin') ORDER BY created_at DESC")
              ->fetchAll(PDO::FETCH_ASSOC);

// ប្រើសម្រាប់លាក់ប៊ូតុងដេម៉ូត ពេលនេះជា Super Admin ចុងក្រោយ
$superAdminCount = countSuperAdmins($pdo);

require_once '../../includes/header.php';
?>

<h1 class="text-2xl font-bold text-gray-800 mb-1 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-700"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
    Team & Admin
</h1>
<p class="text-gray-500 mb-6">គ្រប់គ្រង Admin ដែលមានសិទ្ធិចូលប្រើប្រាស់ប្រព័ន្ធ</p>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="bg-green-50 text-green-700 border border-green-200 rounded-md px-4 py-2 mb-4">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="bg-red-50 text-red-700 border border-red-200 rounded-md px-4 py-2 mb-4">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- បញ្ជី Admin -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Admin ទាំងអស់ (<?= count($admins) ?>)
        </h3>

        <?php foreach ($admins as $admin): ?>
            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
                        <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-medium text-gray-800">
                            <?= htmlspecialchars($admin['name']) ?>
                            <?php if ($admin['role'] === 'super_admin'): ?>
                                <span class="ml-1 inline-flex items-center gap-1 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    Super Admin
                                </span>
                            <?php else: ?>
                                <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">Admin</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-400"><?= htmlspecialchars($admin['email']) ?></div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400"><?= date('d M Y', strtotime($admin['created_at'])) ?></span>

                    <?php if ($iAmSuperAdmin || $admin['id'] === $myId): ?>
                        <a href="edit.php?id=<?= $admin['id'] ?>" class="p-1.5 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition" title="កែប្រែ">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                    <?php endif; ?>

                    <?php if ($iAmSuperAdmin): ?>
                        <form method="POST" action="promote.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                            <?php if ($admin['role'] === 'super_admin'): ?>
                                <?php if ($superAdminCount > 1): ?>
                                    <input type="hidden" name="new_role" value="admin">
                                    <button type="submit" title="ដេម៉ូតទៅ Admin"
                                        onclick="return confirm('ដេម៉ូត <?= htmlspecialchars($admin['name']) ?> ទៅជា Admin ធម្មតា?')"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                    </button>
                                <?php else: ?>
                                    <span class="p-1.5 text-gray-200" title="មិនអាចដេម៉ូតបានទេ — Super Admin ចុងក្រោយ">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="hidden" name="new_role" value="super_admin">
                                <button type="submit" title="ប្រម៉ូតទៅ Super Admin"
                                    onclick="return confirm('ប្រម៉ូត <?= htmlspecialchars($admin['name']) ?> ទៅជា Super Admin?')"
                                    class="p-1.5 rounded-md text-gray-400 hover:text-green-600 hover:bg-green-50 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                                </button>
                            <?php endif; ?>
                        </form>

                        <?php if ($admin['id'] !== $myId): ?>
                            <form method="POST" action="delete.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                <button type="submit" title="លុប"
                                    onclick="return confirm('លុប <?= htmlspecialchars($admin['name']) ?> ចេញ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ')"
                                    class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ថែម Admin ថ្មី -->
    <div class="bg-white rounded-lg shadow p-6 h-fit">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-500"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            បន្ថែម Admin ថ្មី
        </h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ឈ្មោះ</label>
                <input type="text" name="name" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required minlength="6"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <p class="text-xs text-gray-400 mb-4">យ៉ាងតិច 6 តួអក្សរ</p>

            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                + បន្ថែម Admin
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-3 flex items-start gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 flex-shrink-0"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>
            <span>Admin ថ្មីនឹងចាប់ផ្តើមជា "Admin" ធម្មតា។ ត្រូវប្រម៉ូតទៅ Super Admin ដោយឡែក។</span>
        </p>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>