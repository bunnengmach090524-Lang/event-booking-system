<?php
require '../config/database.php';
require '../includes/functions.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    redirect('/event-booking/customer/events.php');
}

$remaining = $event['total_tickets'] - $event['tickets_sold'];
$error = '';

// Handle the booking form submission BEFORE header.php outputs any HTML,
// since redirect() needs to send a Location header, which only works
// before anything has been printed to the page.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) {
        $error = "សូមជ្រើសរើសចំនួនសំបុត្រយ៉ាងតិច 1";
    } elseif ($quantity > $remaining) {
        $error = "សំបុត្រនៅសល់មិនគ្រប់គ្រាន់! នៅសល់តែ $remaining";
    } else {
        // រក្សាទុកក្នុង Session ដើម្បីទៅកន្លែង booking.php
        $_SESSION['booking_event_id'] = $event['id'];
        $_SESSION['booking_quantity'] = $quantity;
        redirect('/event-booking/customer/payment.php');
    }
}

require 'header.php';
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <?php if ($event['image']): ?>
        <img src="/event-booking/uploads/events/<?= htmlspecialchars($event['image']) ?>" 
             class="w-full h-64 object-cover">
    <?php else: ?>
        <div class="w-full h-64 bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-6xl">
            🎫
        </div>
    <?php endif; ?>

    <div class="p-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4"><?= htmlspecialchars($event['title']) ?></h1>
        
        <div class="flex flex-wrap gap-4 text-gray-500 dark:text-gray-400 mb-6">
            <span>📍 <?= htmlspecialchars($event['location']) ?></span>
            <span>📅 <?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></span>
        </div>

        <p class="text-gray-700 dark:text-gray-300 mb-6"><?= nl2br(htmlspecialchars($event['description'])) ?></p>

        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">$<?= number_format($event['price'], 2) ?> / សំបុត្រ</span>
                <?php if ($remaining > 0): ?>
                    <span class="text-sm text-green-600 dark:text-green-400"><?= $remaining ?> សំបុត្រនៅសល់</span>
                <?php else: ?>
                    <span class="text-sm text-red-600 dark:text-red-400 font-bold">សំបុត្រអស់ហើយ</span>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($remaining > 0): ?>
            <form method="POST" class="flex gap-3">
                <input type="number" name="quantity" min="1" max="<?= $remaining ?>" value="1" required
                    class="w-24 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 font-semibold">
                    កក់សំបុត្រឥឡូវនេះ
                </button>
            </form>
            <?php else: ?>
                <button disabled class="w-full bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 py-2 rounded-md cursor-not-allowed">
                    សំបុត្រអស់ហើយ
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>