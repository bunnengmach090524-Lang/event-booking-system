<?php
require '../config/database.php';
require '../includes/header.php';

$message = '';
$messageType = '';
$booking = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrInput = trim($_POST['qr_data']);

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

$jsLang = [
    'qrRecognized'      => t('js_qr_recognized'),
    'cameraUnavailable' => t('js_camera_unavailable'),
    'cameraNotFound'    => t('js_camera_not_found'),
    'cameraPermission'  => t('js_camera_permission_denied'),
    'cameraInUse'       => t('js_camera_in_use'),
    'cameraErrorPrefix' => t('js_camera_error_prefix'),
    'switchingToManual' => t('js_switching_to_manual'),
    'uploadDecodeFail'  => t('js_upload_decode_fail') ?: 'រកមិនឃើញ QR code ក្នុងរូបភាពនេះទេ សូមសាកល្បងរូបផ្សេង ឬបញ្ចូល Booking ID ដោយផ្ទាល់',
    'uploadDecoding'    => t('js_upload_decoding') ?: 'កំពុងអានរូបភាព...',
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

<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">📷 <?= t('checkin_page_title') ?></h1>

<div class="bg-gray-250 dark:bg-gray-800 rounded-lg shadow p-6 max-w-6xl">

    <!-- Mode Toggle -->
    <div class="flex flex-wrap item-center gap-10 mb-5 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg w-fit">
        <button type="button" id="tabCamera" onclick="switchMode('camera')"
            class="px-6 py-2 rounded-md text-sm font-semibold transition-all duration-200 bg-white dark:bg-gray-600 shadow text-blue-600 dark:text-blue-400 flex items-center gap-2">
            <i data-lucide="camera" class="w-4 h-4"></i> <?= t('scan_camera_label') ?>
        </button>
        <button type="button" id="tabUpload" onclick="switchMode('upload')"
            class="px-6 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <i data-lucide="image-up" class="w-4 h-4"></i> <?= t('upload_qr_label') ?? 'Upload រូបភាព QR' ?>
        </button>
        <button type="button" id="tabManual" onclick="switchMode('manual')"
            class="px-6 py-2 rounded-md text-sm font-semibold transition-all duration-200 text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <i data-lucide="keyboard" class="w-4 h-4"></i> <?= t('manual_input_label') ?>
        </button>
    </div>

    <!-- Camera Scanner -->
    <div id="cameraSection">
        <div id="qr-reader" class="rounded-xl overflow-hidden border-2 border-dashed border-gray-200 dark:border-gray-600 max-w-md mx-auto"></div>
        <p id="scannerHint" class="text-center text-xs text-gray-400 dark:text-gray-500 mt-3 flex items-center justify-center gap-1.5">
            <i data-lucide="scan-line" class="w-3.5 h-3.5"></i> <?= t('scanner_hint_label') ?>
        </p>
    </div>

    <!-- Upload QR Image -->
    <div id="uploadSection" class="hidden">
        <label for="qrFileInput"
            class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl py-10 px-4 max-w-md mx-auto cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition">
            <i data-lucide="image-up" class="w-8 h-8 text-gray-400 dark:text-gray-500"></i>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300"><?= t('upload_qr_choose_label') ?? 'ចុចដើម្បីជ្រើសរើសរូបភាព QR' ?></span>
            <span class="text-xs text-gray-400 dark:text-gray-500">JPG, PNG, WEBP</span>
            <input type="file" id="qrFileInput" accept="image/jpeg,image/png,image/webp" class="hidden">
        </label>
        <div id="uploadPreviewWrap" class="hidden mt-4 flex flex-col items-center gap-3">
            <img id="uploadPreviewImg" class="max-h-56 rounded-lg border border-gray-200 dark:border-gray-600">
        </div>
        <p id="uploadHint" class="text-center text-xs text-gray-400 dark:text-gray-500 mt-3"></p>
    </div>

    <!-- Manual Input -->
    <form method="POST" id="manualForm" class="hidden gap-2 flex-col sm:flex-row sm:gap-2">
        <input type="text" name="qr_data" id="qrInput" placeholder="BOOKING-1-EVENT-1" required
            class="flex-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-md px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-md hover:bg-blue-700 transition font-medium">
            <?= t('checkin_button_label') ?>
        </button>
    </form>

    <!-- Hidden form used by camera/upload scanner to auto-submit -->
    <form method="POST" id="scanForm" class="hidden">
        <input type="text" name="qr_data" id="scanInput">
    </form>

    <?php if ($booking): ?>
    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6 animate-in">
        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
            <i data-lucide="ticket" class="w-4 h-4 text-blue-600 dark:text-blue-400"></i> <?= t('ticket_info_label') ?>
        </h3>
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-1.5 text-sm text-gray-700 dark:text-gray-300">
            <p><span class="font-medium text-gray-600 dark:text-gray-400"><?= t('booked_by_colon_label') ?></span> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><span class="font-medium text-gray-600 dark:text-gray-400"><?= t('event_colon_label') ?></span> <?= htmlspecialchars($booking['title']) ?></p>
            <p><span class="font-medium text-gray-600 dark:text-gray-400"><?= t('date_colon_label') ?></span> <?= date('d M Y, h:i A', strtotime($booking['event_date'])) ?></p>
            <p><span class="font-medium text-gray-600 dark:text-gray-400"><?= t('ticket_qty_colon_label') ?></span> <?= $booking['quantity'] ?></p>
            <p><span class="font-medium text-gray-600 dark:text-gray-400"><?= t('booking_number_colon_label') ?></span> #<?= $booking['id'] ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode = null;
    let scannerRunning = false;

    function resetTabStyles() {
        ['tabCamera', 'tabUpload', 'tabManual'].forEach((id) => {
            const el = document.getElementById(id);
            el.classList.remove('bg-white', 'dark:bg-gray-600', 'shadow', 'text-blue-600', 'dark:text-blue-400');
            el.classList.add('text-gray-500', 'dark:text-gray-400');
        });
    }

    function switchMode(mode) {
        resetTabStyles();

        const cameraSection = document.getElementById('cameraSection');
        const uploadSection = document.getElementById('uploadSection');
        const manualForm = document.getElementById('manualForm');

        cameraSection.classList.add('hidden');
        uploadSection.classList.add('hidden');
        manualForm.classList.remove('flex');
        manualForm.classList.add('hidden');
        stopScanner();

        const activeTab = document.getElementById(
            mode === 'camera' ? 'tabCamera' : mode === 'upload' ? 'tabUpload' : 'tabManual'
        );
        activeTab.classList.add('bg-white', 'dark:bg-gray-600', 'shadow', 'text-blue-600', 'dark:text-blue-400');
        activeTab.classList.remove('text-gray-500', 'dark:text-gray-400');

        if (mode === 'camera') {
            cameraSection.classList.remove('hidden');
            startScanner();
        } else if (mode === 'upload') {
            uploadSection.classList.remove('hidden');
        } else {
            manualForm.classList.remove('hidden');
            manualForm.classList.add('flex');
            document.getElementById('qrInput').focus();
        }
    }

    function startScanner() {
        if (scannerRunning) return;
        html5QrCode = new Html5Qrcode("qr-reader");

        html5QrCode.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                if (scannerRunning) {
                    scannerRunning = false;
                    document.getElementById('scannerHint').innerHTML =
                        `<span class="text-green-600 dark:text-green-400 font-medium">${jsLang.qrRecognized}</span>`;
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

            document.getElementById('scannerHint').innerHTML = `<span class="text-red-500 dark:text-red-400">${reason}</span>`;
            showToast(reason + ' ' + jsLang.switchingToManual, 'error');

            setTimeout(() => switchMode('manual'), 1500);
        });
    }

    function stopScanner() {
        if (html5QrCode && scannerRunning) {
            html5QrCode.stop().catch(() => {});
            scannerRunning = false;
        }
    }

    // ---------- Upload QR Image ----------
    const qrFileInput = document.getElementById('qrFileInput');
    const uploadPreviewWrap = document.getElementById('uploadPreviewWrap');
    const uploadPreviewImg = document.getElementById('uploadPreviewImg');
    const uploadHint = document.getElementById('uploadHint');

    qrFileInput.addEventListener('change', () => {
        const file = qrFileInput.files[0];
        if (!file) return;

        const objectUrl = URL.createObjectURL(file);
        uploadPreviewImg.src = objectUrl;
        uploadPreviewWrap.classList.remove('hidden');
        uploadHint.textContent = jsLang.uploadDecoding;
        uploadHint.className = 'text-center text-xs text-gray-400 dark:text-gray-500 mt-3';

        // Reuses the SAME html5-qrcode library already loaded — no server upload needed,
        // decoding happens entirely client-side from the local file.
        // const fileScanner = new Html5Qrcode("qr-reader-upload-hidden", /* verbose= */ false);

        // Html5Qrcode.getCameras().catch(() => {}); // no-op, keeps lib warm on some browsers

        const tempDiv = document.createElement('div');
        tempDiv.id = 'qr-reader-upload-hidden-' + Date.now();
        tempDiv.style.display = 'none';
        document.body.appendChild(tempDiv);

        const scanner = new Html5Qrcode(tempDiv.id, false);
        scanner.scanFile(file, false)
            .then((decodedText) => {
                document.getElementById('scanInput').value = decodedText;
                uploadHint.innerHTML = `<span class="text-green-600 dark:text-green-400 font-medium">${jsLang.qrRecognized}</span>`;
                setTimeout(() => document.getElementById('scanForm').submit(), 400);
            })
            .catch(() => {
                uploadHint.innerHTML = `<span class="text-red-500 dark:text-red-400">${jsLang.uploadDecodeFail}</span>`;
                showToast(jsLang.uploadDecodeFail, 'error');
            })
            .finally(() => {
                scanner.clear().catch(() => {});
                tempDiv.remove();
                URL.revokeObjectURL(objectUrl);
            });
    });

    document.addEventListener('DOMContentLoaded', () => {
        startScanner();
    });

    window.addEventListener('beforeunload', stopScanner);
</script>

<?php require '../includes/footer.php'; ?>