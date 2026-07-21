<?php
/**
 * includes/notify.php
 * Drop-in helper so any admin action can push a notification to a user.
 *
 * Example usage inside admin/events/cancel.php after a successful cancellation:
 *
 *   require_once __DIR__ . '/../../includes/notify.php';
 *   notify($pdo, $userId, 'booking_cancelled',
 *       'Booking cancelled',
 *       'Your booking for "' . $eventTitle . '" was cancelled.',
 *       '/event-booking/customer/my-tickets.php'
 *   );
 */

function notify(PDO $pdo, int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message, $link]);
}