<?php
require '../config/database.php';
require '../includes/header.php';

$logs = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

$actionIcons = [
    'cancel_event'          => ['icon' => 'circle-slash', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/30'],
    'reactivate_event'      => ['icon' => 'refresh-cw', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/30'],
    'delete_event'          => ['icon' => 'trash-2', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/30'],
    'update_booking_status' => ['icon' => 'receipt', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/30'],
    'add_admin'             => ['icon' => 'user-plus', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/30'],
];
$defaultIcon = ['icon' => 'activity', 'color' => 'text-gray-500', 'bg' => 'bg-gray-50 dark:bg-gray-700'];
?>

<a href="/event-booking/admin/team/index.php" class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-5 transition">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> ត្រឡប់ទៅ Team
</a>

<div class="mb-6 animate-in">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📜 Activity Log</h1>
    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">សកម្មភាព 100 ចុងក្រោយរបស់ Admin ទាំងអស់</p>
</div>

<div class="animate-in delay-1 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <?php if (empty($logs)): ?>
        <div class="text-center py-16">
            <i data-lucide="inbox" class="w-12 h-12 text-gray-200 dark:text-gray-700 mx-auto mb-3"></i>
            <p class="text-gray-400 dark:text-gray-500">មិនទាន់មានសកម្មភាពណាមួយត្រូវបានកត់ត្រាទេ</p>
        </div>
    <?php else: ?>
    <div class="relative space-y-6 before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-px before:bg-gray-100 dark:before:bg-gray-700">
        <?php foreach ($logs as $log):
            $style = $actionIcons[$log['action']] ?? $defaultIcon;
        ?>
        <div class="relative flex gap-4 pl-0">
            <div class="w-10 h-10 rounded-full <?= $style['bg'] ?> flex items-center justify-center flex-shrink-0 z-10 ring-4 ring-white dark:ring-gray-800">
                <i data-lucide="<?= $style['icon'] ?>" class="w-4.5 h-4.5 <?= $style['color'] ?>"></i>
            </div>
            <div class="flex-1 min-w-0 pt-1.5">
                <p class="text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($log['description']) ?></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    <span class="font-medium text-gray-500 dark:text-gray-400"><?= htmlspecialchars($log['admin_name']) ?></span>
                    · <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>