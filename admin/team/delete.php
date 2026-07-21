<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();
requireSuperAdmin(); // មានតែ Super Admin ទេលុប Admin បាន

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/team/index.php');
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);
$myId = (int)$_SESSION['user_id'];

if (!$id) {
    redirect('/event-booking/admin/team/index.php');
}

// ការពារ: មិនអនុញ្ញាតឲ្យលុបខ្លួនឯង
if ($id === $myId) {
    $_SESSION['error'] = 'អ្នកមិនអាចលុបគណនីខ្លួនឯងបានទេ';
    redirect('/event-booking/admin/team/index.php');
}

// សំខាន់: ត្រូវប្រាកដថា target ជា admin/super_admin មិនមែន customer (ការពារការហៅ id ខុសដោយចេតនា)
$stmt = $pdo->prepare("SELECT name, role FROM users WHERE id = ? AND role IN ('admin','super_admin')");
$stmt->execute([$id]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    redirect('/event-booking/admin/team/index.php');
}

// ការពារ: មិនអនុញ្ញាតឲ្យលុប Super Admin ចុងក្រោយ
if ($target['role'] === 'super_admin' && countSuperAdmins($pdo) <= 1) {
    $_SESSION['error'] = 'មិនអាចលុបបានទេ — នេះជា Super Admin ចុងក្រោយក្នុងប្រព័ន្ធ';
    redirect('/event-booking/admin/team/index.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    logActivity($pdo, 'delete_admin', 'បានលុប Admin "' . $target['name'] . '"');
    $_SESSION['success'] = 'បានលុប Admin ដោយជោគជ័យ';
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = 'មិនអាចលុប Admin នេះបានទេ ព្រោះមានទិន្នន័យផ្សេងទៀតភ្ជាប់ជាមួយ (ឧ. bookings/activity log)';
    } else {
        error_log('delete_admin error: ' . $e->getMessage());
        $_SESSION['error'] = 'មានបញ្ហាក្នុងការលុប Admin';
    }
}

redirect('/event-booking/admin/team/index.php');