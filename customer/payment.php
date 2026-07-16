<?php
require '../config/database.php';
require 'header.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardName = trim($_POST['card_name']);
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number']);
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);

    // ការត្រួតពិនិត្យទម្រង់ (Format validation ប៉ុណ្ណោះ - មិនមែន Payment ពិត)
    if (empty($cardName)) {
        $error = "សូមបញ្ចូលឈ្មោះលើកាត";
    } elseif (!preg_match('/^\d{16}$/', $cardNumber)) {
        $error = "លេខកាតត្រូវមាន 16 ខ្ទង់";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $error = "Format ថ្ងៃផុតកំណត់ត្រូវជា MM/YY";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $error = "CVV ត្រូវមាន 3-4 ខ្ទង់";
    } else {
        // ក្លែងធ្វើការទូទាត់ជោគជ័យ
        $_SESSION['payment_verified'] = true;
        redirect('/event-booking/customer/booking.php');
    }
}
?>

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">💳 ទូទាត់ប្រាក់</h1>

    <!-- Order Summary -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">សេចក្តីសង្ខេប</h3>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500">Event:</span>
            <span class="font-medium"><?= htmlspecialchars($event['title']) ?></span>
        </div>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500">ចំនួនសំបុត្រ:</span>
            <span class="font-medium"><?= $quantity ?></span>
        </div>
        <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-500">តម្លៃក្នុងមួយ:</span>
            <span class="font-medium">$<?= number_format($event['price'], 2) ?></span>
        </div>
        <div class="border-t mt-3 pt-3 flex justify-between">
            <span class="font-bold text-gray-800">សរុប:</span>
            <span class="font-bold text-blue-600 text-lg">$<?= number_format($total_price, 2) ?></span>
        </div>
    </div>

    <!-- Payment Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">💳</span>
            <h3 class="font-semibold text-gray-700">ព័ត៌មានកាត</h3>
            <span class="ml-auto text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">Demo Mode</span>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ឈ្មោះលើកាត</label>
                <input type="text" name="card_name" placeholder="SOK DARA" required
                    value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">លេខកាត</label>
                <input type="text" name="card_number" placeholder="4111 1111 1111 1111" maxlength="19" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="this.value = this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ថ្ងៃផុតកំណត់</label>
                    <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="this.value = this.value.replace(/\D/g,'').replace(/(\d{2})(\d)/,'$1/$2').substring(0,5)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                    <input type="text" name="cvv" placeholder="123" maxlength="4" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        oninput="this.value = this.value.replace(/\D/g,'')">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 font-semibold">
                🔒 ទូទាត់ $<?= number_format($total_price, 2) ?>
            </button>

            <p class="text-xs text-gray-400 text-center mt-3">
                ⚠️ នេះជា Demo Payment មិនមែនការទូទាត់ពិតទេ។ សូមប្រើលេខកាតណាមួយ (16 ខ្ទង់)
            </p>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>