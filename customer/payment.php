<?php
require '../config/database.php';
require '../includes/functions.php';
require '../includes/lang.php'; // loaded early so t() is available before the POST/error handling below

if (!isset($_SESSION['booking_event_id']) || !isset($_SESSION['booking_quantity'])) {
    redirect('/event-booking/customer/events.php');
}

$event_id = $_SESSION['booking_event_id'];
$quantity = $_SESSION['booking_quantity'];

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    redirect('/event-booking/customer/events.php');
}

$total_price = $event['price'] * $quantity;
$error = '';

// Handle payment form submission BEFORE header.php outputs any HTML,
// since redirect() needs to send a Location header before any output starts.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName = trim($_POST['card_name']);
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number']);
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);

    // ការត្រួតពិនិត្យទម្រង់ (Format validation ប៉ុណ្ណោះ - មិនមែន Payment ពិត)
    if (empty($cardName)) {
        $error = t('err_card_name');
    } elseif (!preg_match('/^\d{16}$/', $cardNumber)) {
        $error = t('err_card_number');
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $error = t('err_expiry_format');
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $error = t('err_cvv');
    } else {
        // ក្លែងធ្វើការទូទាត់ជោគជ័យ
        $_SESSION['payment_verified'] = true;
        redirect('/event-booking/customer/booking.php');
    }
}

require 'header.php';
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-6"><?= t('payment_title') ?></h1>

    <!-- Order Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3"><?= t('order_summary') ?></h3>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500 dark:text-gray-400"><?= t('label_event') ?>:</span>
            <span class="font-medium text-gray-800 dark:text-gray-100"><?= htmlspecialchars($event['title']) ?></span>
        </div>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500 dark:text-gray-400"><?= t('label_quantity') ?>:</span>
            <span class="font-medium text-gray-800 dark:text-gray-100"><?= $quantity ?></span>
        </div>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500 dark:text-gray-400"><?= t('label_price_per') ?>:</span>
            <span class="font-medium text-gray-800 dark:text-gray-100">$<?= number_format($event['price'], 2) ?></span>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 mt-3 pt-3 flex justify-between">
            <span class="font-bold text-gray-800 dark:text-white"><?= t('label_subtotal') ?>:</span>
            <span class="font-bold text-blue-600 dark:text-blue-400 text-lg">$<?= number_format($total_price, 2) ?></span>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">💳</span>
            <h3 class="font-semibold text-gray-700 dark:text-gray-200"><?= t('card_info') ?></h3>
            <span class="ml-auto text-xs bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 px-2 py-1 rounded"><?= t('demo_mode') ?></span>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('card_name_label') ?></label>
                <input type="text" name="card_name" placeholder="SOK DARA" required
                    value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('card_number_label') ?></label>
                <input type="text" name="card_number" placeholder="4111 1111 1111 1111" maxlength="19" required
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="this.value = this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('expiry_label') ?></label>
                    <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="this.value = this.value.replace(/\D/g,'').replace(/(\d{2})(\d)/,'$1/$2').substring(0,5)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('cvv_label') ?></label>
                    <input type="text" name="cvv" placeholder="123" maxlength="4" required
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-500 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="this.value = this.value.replace(/\D/g,'')">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-semibold">
                <?= t('pay_button') ?> $<?= number_format($total_price, 2) ?>
            </button>

            <p class="text-xs text-gray-400 dark:text-gray-500 text-center mt-3">
                <?= t('demo_notice') ?>
            </p>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>