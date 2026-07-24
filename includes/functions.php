<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']); 
}

// កែប្រែ: admin OR super_admin ទាំងពីរអាចចូល Admin Panel បាន
function isAdmin() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'], true);
}

// ថ្មី: check ថាតើជា Super Admin ដែរឬអត់ (ប្រើ $_SESSION['role'] ដដែល មិនបង្កើត key ថ្មី)
function isSuperAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}


function redirect($url) {
    header("Location: $url");
    exit();
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/event-booking/auth/login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/event-booking/customer/events.php');
    }
}

// ថ្មី: ហៅនៅដើម edit.php/delete.php/promote.php ណាមួយដែលត្រូវការសិទ្ធិ Super Admin តែប៉ុណ្ណោះ
function requireSuperAdmin(): void {
    if (!isSuperAdmin()) {
        $_SESSION['error'] = 'អ្នកគ្មានសិទ្ធិធ្វើសកម្មភាពនេះទេ — ត្រូវការសិទ្ធិ Super Admin';
        redirect('/event-booking/admin/team/index.php');
    }
}

function logActivity($pdo, $action, $description) {
    if (!isset($_SESSION['user_id'])) return;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, admin_name, action, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['name'] ?? 'Admin',
        $action,
        $description
    ]);
}

// ថ្មី: រាប់ចំនួន Super Admin ដែលនៅសល់ (តារាង users)
function countSuperAdmins(PDO $pdo): int {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
    return (int)$stmt->fetchColumn();
}

// ថ្មី: ទាញយក role របស់ user id ណាមួយ
function getUserRole(PDO $pdo, int $userId): ?string {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();
    return $role !== false ? $role : null;
}

// ថ្មី: CSRF token helpers — ត្រូវការសម្រាប់ delete.php/promote.php (POST-based)
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('❌ Invalid CSRF token — សូម refresh ទំព័រ ហើយព្យាយាមម្តងទៀត');
    }
}


function localized(?string $kmValue, string $defaultValue): string {
    global $currentLang;
    if ($currentLang === 'km' && !empty($kmValue)) {
        return $kmValue;
    }
    return $defaultValue;
}