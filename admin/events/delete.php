<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
requireAdmin();

// ត្រូវប្រើ POST ប៉ុណ្ណោះ (មិនមែន GET) ដើម្បីការពារ CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/event-booking/admin/events/index.php');
    exit();
}

csrfCheck();

$id = (int)($_POST['id'] ?? 0);

if ($id) {
    $stmt = $pdo->prepare("SELECT title, image FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);

        if ($event && $event['image'] && file_exists('../../uploads/events/' . $event['image'])) {
            unlink('../../uploads/events/' . $event['image']);
        }

        logActivity($pdo, 'delete_event', 'បានលុប Event "' . ($event['title'] ?? '#' . $id) . '"');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = 'មិនអាចលុប Event នេះបានទេ ព្រោះមានការកក់សំបុត្រភ្ជាប់ជាមួយរួចហើយ។';
        } else {
            error_log('delete_event error: ' . $e->getMessage());
            $_SESSION['error'] = 'មានបញ្ហាក្នុងការលុប Event';
        }
        redirect('/event-booking/admin/events/index.php');
        exit();
    }
}

redirect('/event-booking/admin/events/index.php');