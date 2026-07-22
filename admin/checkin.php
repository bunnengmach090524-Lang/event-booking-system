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
            $message = t('msg_booking_not_found');
            $messageType = 'error';
        } elseif ($booking['is_checked_in']) {
            $message = t('msg_already_checked_in');
            $messageType = 'warning';
        } elseif ($booking['status'] !== 'paid') {
            $message = t('msg_not_paid');
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE bookings SET is_checked_in = TRUE WHERE id = ?");
            $stmt->execute([$booking_id]);
            $booking['is_checked_in'] = true;

            $message = t('msg_checkin_success');
            $messageType = 'success';
        }
    } else {
        $message = t('msg_invalid_qr');
        $messageType = 'error';
    }
}

// Strings needed on the JS side (camera scanner messages)
$jsLang = [
    'qrRecognized'      => t('js_qr_recognized'),
    'cameraUnavailable' => t('js_camera_unavailable'),
    'cameraNotFound'    => t('js_camera_not_found'),
    'cameraPermission'  => t('js_camera_permission_denied'),
    'cameraInUse'       => t('js_camera_in_use'),
    'cameraErrorPrefix' => t('js_camera_error_prefix'),
    'switchingToManual' => t('js_switching_to_manual'),
];
?>

<script>
    const jsLang = <?= json_encode($jsLang, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($message): ?>
        showToast(<?= json_encode($message) ?>, <?= json_encode($messageType === 'warning' ? 'error' : $messageType) ?>);
    <?php endif; ?>
});
</script>

<h1 class="text-2xl font-bold text-gray-800 mb-6">📷 <?= t('checkin_page_title') ?></h1>

<div class="bg-white rounded-lg shadow p-6 max-w-xl">

    <!-- Mode Toggle -->
    <div class="flex gap-2 mb-5 bg-gray-100 p-1 rounded-lg w-fit">
        <button type="button" id="tabCamera" onclick="switchMode('camera')"
            class="px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 bg-white shadow text-blue-600 flex items-center gap-2">
            <i data-lucide="camera" class="w-4 h-4"></i> <?= t('scan_camera_label') ?>
        </button>
        <button type="button" id="tabManual" onclick="switchMode('manual')"
            class="px-4 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-500 flex items-center gap-2">
            <i data-lucide="keyboard" class="w-4 h-4"></i> <?= t('manual_input_label') ?>
        </button>
    </div>

    <!-- Camera Scanner -->
    <div id="cameraSection">
        <div id="qr-reader" class="rounded-xl overflow-hidden border-2 border-dashed border-gray-200"></div>
        <p id="scannerHint" class="text-center text-xs text-gray-400 mt-3 flex items-center justify-center gap-1.5">
            <i data-lucide="scan-line" class="w-3.5 h-3.5"></i> <?= t('scanner_hint_label') ?>
        </p>
    </div>

    <!-- Manual Input -->
    <form method="POST" id="manualForm" class="hidden gap-2 flex-col sm:flex-row sm:gap-2">
        <input type="text" name="qr_data" id="qrInput" placeholder="BOOKING-1-EVENT-1" required
            class="flex-1 border border-gray-300 rounded-md px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-md hover:bg-blue-700 transition font-medium">
            <?= t('checkin_button_label') ?>
        </button>
    </form>

    <!-- Hidden form used by camera scanner to auto-submit -->
    <form method="POST" id="scanForm" class="hidden">
        <input type="text" name="qr_data" id="scanInput">
    </form>

    <?php if ($booking): ?>
    <div class="mt-6 border-t pt-6 animate-in">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i data-lucide="ticket" class="w-4 h-4 text-blue-600"></i> <?= t('ticket_info_label') ?>
        </h3>
        <div class="bg-gray-50 rounded-lg p-4 space-y-1.5 text-sm">
            <p><span class="font-medium text-gray-600"><?= t('booked_by_colon_label') ?></span> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><span class="font-medium text-gray-600"><?= t('event_colon_label') ?></span> <?= htmlspecialchars($booking['title']) ?></p>
            <p><span class="font-medium text-gray-600"><?= t('date_colon_label') ?></span> <?= date('d M Y, h:i A', strtotime($booking['event_date'])) ?></p>
            <p><span class="font-medium text-gray-600"><?= t('ticket_qty_colon_label') ?></span> <?= $booking['quantity'] ?></p>
            <p><span class="font-medium text-gray-600"><?= t('booking_number_colon_label') ?></span> #<?= $booking['id'] ?></p>
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
                // Guard against multiple repeated scans
                if (scannerRunning) {
                    scannerRunning = false;
                    document.getElementById('scannerHint').innerHTML =
                        `<span class="text-green-600 font-medium">${jsLang.qrRecognized}</span>`;
                    html5QrCode.stop().then(() => {
                        document.getElementById('scanInput').value = decodedText;
                        document.getElementById('scanForm').submit();
                    }).catch(() => {
                        document.getElementById('scanInput').value = decodedText;
                        document.getElementById('scanForm').submit();
                    });
                }
            },
            () => { /* frame has no QR — not a problem, keep going */ }
        ).then(() => {
            scannerRunning = true;
        }).catch((err) => {
            let reason = jsLang.cameraUnavailable;
            const errStr = String(err);

            if (errStr.includes('NotFoundError') || errStr.includes('requested device not found')) {
                reason = jsLang.cameraNotFound;
            } else if (errStr.includes('NotAllowedError') || errStr.includes('Permission denied')) {
                reason = jsLang.cameraPermission;
            } else if (errStr.includes('NotReadableError')) {
                reason = jsLang.cameraInUse;
            } else {
                reason = jsLang.cameraErrorPrefix + errStr;
            }

            document.getElementById('scannerHint').innerHTML = `<span class="text-red-500">${reason}</span>`;
            showToast(reason + ' ' + jsLang.switchingToManual, 'error');

            // Auto-fallback to Manual mode
            setTimeout(() => switchMode('manual'), 1500);
        });
    }

    function stopScanner() {
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop().catch(() => {});
            scannerRunning = false;
        }
    }

    // Start camera immediately when the page opens
    document.addEventListener('DOMContentLoaded', () => {
        startScanner();
    });

    window.addEventListener('beforeunload', stopScanner);
</script>

<?php require '../includes/footer.php'; ?>