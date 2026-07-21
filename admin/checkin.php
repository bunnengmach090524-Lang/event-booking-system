<?php
require '../config/database.php';
require '../includes/header.php';

$message = '';
$messageType = '';
$booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrInput = trim($_POST['qr_data']);

    // Format QR: BOOKING-{id}-EVENT-{event_id}
    if (preg_match('/BOOKING-(\d+)-EVENT-(\d+)/', $qrInput, $matches)) {
        $booking_id = $matches[1];

        $stmt = $pdo->prepare("
            SELECT b.*, e.title, e.event_date, u.name as customer_name 
            FROM bookings b 
            JOIN events e ON b.event_id = e.id 
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $message = "❌ រកមិនឃើញ Booking នេះទេ!";
            $messageType = 'error';
        } elseif ($booking['is_checked_in']) {
            $message = "⚠️ សំបុត្រនេះបាន Check-in រួចហើយ!";
            $messageType = 'warning';
        } elseif ($booking['status'] !== 'paid') {
            $message = "❌ សំបុត្រនេះមិនទាន់ទូទាត់ប្រាក់!";
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET is_checked_in = TRUE WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking['is_checked_in'] = true;

            $message = "✅ Check-in ជោគជ័យ!";
            $messageType = 'success';
        }
    } else {
        $message = "❌ QR Code មិនត្រឹមត្រូវ ឬ Format ខុស!";
        $messageType = 'error';
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($message): ?>
        showToast(<?= json_encode($message) ?>, <?= json_encode($messageType === 'warning' ? 'error' : $messageType) ?>);
    <?php endif; ?>
});
</script>

<h1 class="text-2xl font-bold text-gray-800 mb-6">📷 Check-in អ្នកចូលរួម</h1>

<div class="bg-white rounded-lg shadow p-6 max-w-xl">

    <!-- Mode Toggle -->
    <div class="flex gap-2 mb-5 bg-gray-100 p-1 rounded-lg w-fit">
        <button type="button" id="tabCamera" onclick="switchMode('camera')"
            class="px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 bg-white shadow text-blue-600 flex items-center gap-2">
            <i data-lucide="camera" class="w-4 h-4"></i> Scan ដោយ Camera
        </button>
        <button type="button" id="tabManual" onclick="switchMode('manual')"
            class="px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-500 flex items-center gap-2">
            <i data-lucide="keyboard" class="w-4 h-4"></i> វាយបញ្ចូល
        </button>
    </div>

    <!-- Camera Scanner -->
    <div id="cameraSection">
        <div id="qr-reader" class="rounded-xl overflow-hidden border-2 border-dashed border-gray-200"></div>
        <p id="scannerHint" class="text-center text-xs text-gray-400 mt-3 flex items-center justify-center gap-1.5">
            <i data-lucide="scan-line" class="w-3.5 h-3.5"></i> តម្រង់ QR Code ចូលក្នុងស៊ុម
        </p>
    </div>

    <!-- Manual Input -->
    <form method="POST" id="manualForm" class="hidden gap-2 flex-col sm:flex-row sm:gap-2">
        <input type="text" name="qr_data" id="qrInput" placeholder="BOOKING-1-EVENT-1" required
            class="flex-1 border border-gray-300 rounded-md px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-md hover:bg-blue-700 transition font-medium">
            Check-in
        </button>
    </form>

    <!-- Hidden form used by camera scanner to auto-submit -->
    <form method="POST" id="scanForm" class="hidden">
        <input type="text" name="qr_data" id="scanInput">
    </form>

    <?php if ($booking): ?>
    <div class="mt-6 border-t pt-6 animate-in">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i data-lucide="ticket" class="w-4 h-4 text-blue-600"></i> ព័ត៌មានសំបុត្រ
        </h3>
        <div class="bg-gray-50 rounded-lg p-4 space-y-1.5 text-sm">
            <p><span class="font-medium text-gray-600">អ្នកកក់:</span> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><span class="font-medium text-gray-600">Event:</span> <?= htmlspecialchars($booking['title']) ?></p>
            <p><span class="font-medium text-gray-600">ថ្ងៃ:</span> <?= date('d M Y, h:i A', strtotime($booking['event_date'])) ?></p>
            <p><span class="font-medium text-gray-600">ចំនួនសំបុត្រ:</span> <?= $booking['quantity'] ?></p>
            <p><span class="font-medium text-gray-600">លេខកក់:</span> #<?= $booking['id'] ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;
    let scannerRunning = false;

    function switchMode(mode) {
        const tabCamera = document.getElementById('tabCamera');
        const tabManual = document.getElementById('tabManual');
        const cameraSection = document.getElementById('cameraSection');
        const manualForm = document.getElementById('manualForm');

        if (mode === 'camera') {
            tabCamera.classList.add('bg-white', 'shadow', 'text-blue-600');
            tabCamera.classList.remove('text-gray-500');
            tabManual.classList.remove('bg-white', 'shadow', 'text-blue-600');
            tabManual.classList.add('text-gray-500');

            cameraSection.classList.remove('hidden');
            manualForm.classList.remove('flex');
            manualForm.classList.add('hidden');

            startScanner();
        } else {
            tabManual.classList.add('bg-white', 'shadow', 'text-blue-600');
            tabManual.classList.remove('text-gray-500');
            tabCamera.classList.remove('bg-white', 'shadow', 'text-blue-600');
            tabCamera.classList.add('text-gray-500');

            cameraSection.classList.add('hidden');
            manualForm.classList.remove('hidden');
            manualForm.classList.add('flex');
            document.getElementById('qrInput').focus();

            stopScanner();
        }
    }

    function startScanner() {
        if (scannerRunning) return;
        html5QrCode = new Html5Qrcode("qr-reader");

        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                // ការពារ Scan ដដែលៗច្រើនដង
                if (scannerRunning) {
                    scannerRunning = false;
                    document.getElementById('scannerHint').innerHTML =
                        '<span class="text-green-600 font-medium">✅ បានស្គាល់ QR Code! កំពុងផ្ទៀងផ្ទាត់...</span>';
                    html5QrCode.stop().then(() => {
                        document.getElementById('scanInput').value = decodedText;
                        document.getElementById('scanForm').submit();
                    }).catch(() => {
                        document.getElementById('scanInput').value = decodedText;
                        document.getElementById('scanForm').submit();
                    });
                }
            },
            () => { /* frame មិនមាន QR — មិនបញ្ហា ធ្វើកន្លងទៅ */ }
        ).then(() => {
            scannerRunning = true;
        }).catch((err) => {
            let reason = 'មិនអាចបើក Camera បានទេ';
            const errStr = String(err);

            if (errStr.includes('NotFoundError') || errStr.includes('requested device not found')) {
                reason = '⚠️ រកមិនឃើញ Camera ក្នុងឧបករណ៍នេះទេ (Desktop ភាគច្រើនគ្មាន Webcam)';
            } else if (errStr.includes('NotAllowedError') || errStr.includes('Permission denied')) {
                reason = '⚠️ Camera Permission ត្រូវបានបដិសេធ — សូមអនុញ្ញាតនៅ Browser Settings';
            } else if (errStr.includes('NotReadableError')) {
                reason = '⚠️ Camera កំពុងប្រើដោយ App ផ្សេង (បិទ Zoom/OBS ។ល។ រួចសាកម្តងទៀត)';
            } else {
                reason = '⚠️ មិនអាចបើក Camera បានទេ: ' + errStr;
            }

            document.getElementById('scannerHint').innerHTML = `<span class="text-red-500">${reason}</span>`;
            showToast(reason + ' — កំពុងប្តូរទៅ "វាយបញ្ចូល"', 'error');

            // Auto-fallback ទៅ Manual mode
            setTimeout(() => switchMode('manual'), 1500);
        });
    }

    function stopScanner() {
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop().catch(() => {});
            scannerRunning = false;
        }
    }

    // ចាប់ផ្តើម Camera ភ្លាមៗពេលបើកទំព័រ
    document.addEventListener('DOMContentLoaded', () => {
        startScanner();
    });

    window.addEventListener('beforeunload', stopScanner);
</script>

<?php require '../includes/footer.php'; ?>