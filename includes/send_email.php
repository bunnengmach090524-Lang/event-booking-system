<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendBookingConfirmation($toEmail, $toName, $eventTitle, $quantity, $totalPrice, $bookingId, $qrFileName) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $qrPath = __DIR__ . '/../assets/qrcodes/' . $qrFileName;
        if (file_exists($qrPath)) {
            $mail->addAttachment($qrPath, 'ticket-qr-' . $bookingId . '.png');
        }

        $mail->isHTML(true);
        $mail->Subject = "✅ ការកក់សំបុត្រជោគជ័យ - " . $eventTitle;
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;'>
                <h2 style='color: #2563eb;'>🎫 ការកក់សំបុត្រជោគជ័យ!</h2>
                <p>សួស្តី <strong>{$toName}</strong>,</p>
                <p>អ្នកបានកក់សំបុត្រសម្រាប់ Event ខាងក្រោមដោយជោគជ័យ:</p>
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px;'>
                    <p><strong>Event:</strong> {$eventTitle}</p>
                    <p><strong>ចំនួនសំបុត្រ:</strong> {$quantity}</p>
                    <p><strong>តម្លៃសរុប:</strong> \${$totalPrice}</p>
                    <p><strong>លេខកក់:</strong> #{$bookingId}</p>
                </div>
                <p>QR Code ត្រូវបានភ្ជាប់មកជាមួយ Email នេះ។ សូមបង្ហាញ QR Code នៅច្រកចូល Event។</p>
                <p style='color: #6b7280; font-size: 12px;'>អរគុណដែលបានប្រើប្រាស់សេវាកម្មរបស់យើង!</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}