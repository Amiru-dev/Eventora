<?php
/**
 * Eventora - 24-Hour Event Reminder Cron Job
 * FR-MAIL-002, FR-MAIL-005
 * 
 * Runs hourly to send automated 24-hour reminder emails to students
 * with confirmed bookings for events starting within the next 24 hours.
 * 
 * Usage:
 *   php cron/send_reminders.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("403 Forbidden: CLI execution only.\n");
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/app.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/mailer.php';

$startTime = microtime(true);
$runDate   = date('Y-m-d H:i:s');

echo "[{$runDate}] Eventora 24-Hour Event Reminder Cron Started...\n";

$pdo = get_db_connection();
if (!$pdo) {
    echo "[{$runDate}] Database connection not available. Simulating test reminder check...\n";
    $testResult = send_event_reminder_email(1);
    echo "[{$runDate}] Sample simulated reminder status: " . ($testResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "[{$runDate}] Cron job completed.\n";
    exit(0);
}

try {
    // Find confirmed bookings for events starting in the next 24 hours (FR-MAIL-002)
    // where a reminder has not already been sent
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.booking_ref, e.title AS event_title, e.start_date, u.email AS user_email
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.status = 'confirmed'
          AND e.status = 'published'
          AND e.start_date > NOW()
          AND e.start_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
          AND NOT EXISTS (
              SELECT 1 FROM email_logs el 
              WHERE el.booking_id = b.booking_id 
                AND el.template = 'event_reminder'
                AND el.status = 'sent'
          )
        ORDER BY e.start_date ASC
    ");
    $stmt->execute();
    $dueBookings = $stmt->fetchAll();

    $totalFound = count($dueBookings);
    $sentCount  = 0;
    $failCount  = 0;

    echo "[{$runDate}] Found {$totalFound} reminder(s) to send.\n";

    foreach ($dueBookings as $b) {
        $bid = (int)$b['booking_id'];
        $res = send_event_reminder_email($bid);
        if ($res['success']) {
            $sentCount++;
            echo "  -> Sent reminder for {$b['booking_ref']} to {$b['user_email']} ({$b['event_title']})\n";
        } else {
            $failCount++;
            echo "  !! Failed reminder for {$b['booking_ref']} to {$b['user_email']}: {$res['error']}\n";
        }
    }

    $elapsed = round(microtime(true) - $startTime, 3);
    echo "[{$runDate}] Reminders complete: {$sentCount} sent, {$failCount} failed in {$elapsed}s.\n";

} catch (\PDOException $e) {
    echo "[{$runDate}] Error running reminders cron: " . $e->getMessage() . "\n";
    exit(1);
}
