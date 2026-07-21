<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();
requireSuperAdmin(); // មានតែ Super Admin ទេប្រម៉ូត/ដេម៉ូតបាន

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/team/index.php');
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);
$newRole = $_POST['new_role'] ?? '';

if (!$id || !in_array($newRole, ['admin', 'super_admin'], true)) {
    $_SESSION['error'] = 'ទិន្នន័យមិនត្រឹមត្រូវ';
    redirect('/event-booking/admin/team/index.php');
}

$stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ? AND role IN ('admin','super_admin')");
$stmt->execute([$id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    redirect('/event-booking/admin/team/index.php');
}

// ការពារ: មិនអនុញ្ញាតឲ្យដេម៉ូត Super Admin ចុងក្រោយ
if ($target['role'] === 'super_admin' && $newRole === 'admin' && countSuperAdmins($pdo) <= 1) {
    $_SESSION['error'] = 'មិនអាចដេម៉ូតបានទេ — នេះជា Super Admin ចុងក្រោយក្នុងប្រព័ន្ធ';
    redirect('/event-booking/admin/team/index.php');
}

try {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $id]);

    $action = $newRole === 'super_admin' ? 'ប្រម៉ូតទៅ Super Admin' : 'ដេម៉ូតទៅ Admin';
    logActivity($pdo, 'change_admin_role', $action . ' សម្រាប់ "' . $target['name'] . '"');
    $_SESSION['success'] = 'បានផ្លាស់ប្តូរតួនាទីដោយជោគជ័យ';
} catch (PDOException $e) {
    error_log('promote_admin error: ' . $e->getMessage());
    $_SESSION['error'] = 'មានបញ្ហាក្នុងការផ្លាស់ប្តូរតួនាទី';
}

redirect('/event-booking/admin/team/index.php');