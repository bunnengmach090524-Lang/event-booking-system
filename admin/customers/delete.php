<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/customers/index.php');
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($pdo, 'delete_customer', 'បានលុប Customer "' . $customer['name'] . '"');
            $_SESSION['success'] = t('success_customer_deleted');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = t('error_delete_customer_fk');
            } else {
                error_log('delete_customer error: ' . $e->getMessage());
                $_SESSION['error'] = t('error_delete_customer_generic');
            }
        }
    }
}

redirect('/event-booking/admin/customers/index.php');