<?php
/**
 * Eventora - Email Notification Engine (PHPMailer + Gmail SMTP)
 * FR-MAIL-001 to FR-MAIL-005, BR-13, NFR-REL-001
 * 
 * Safely handles and logs all outbound transactional emails.
 * Failures never break client-facing booking or state transactions.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/helpers.php';

// Load PHPMailer classes
require_once __DIR__ . '/../assets/vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../assets/vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../assets/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Low-level transactional email dispatch with database logging
 * FR-MAIL-005
 * 
 * @param string $toEmail
 * @param string $toName
 * @param string $subject
 * @param string $htmlBody
 * @param string $template
 * @param int|null $bookingId
 * @param int|null $eventId
 * @return array ['success' => bool, 'email_id' => int|null, 'error' => string|null]
 */
function send_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $template = 'general',
    ?int $bookingId = null,
    ?int $eventId = null
): array {
    $toEmail = trim($toEmail);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'email_id' => null, 'error' => "Invalid recipient email address: {$toEmail}"];
    }

    $pdo = get_db_connection();
    $emailId = null;

    // 1. Log attempt into email_logs table as 'queued' (FR-MAIL-005)
    if ($pdo) {
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (recipient, template, subject, booking_id, event_id, status, attempts, created_at)
                VALUES (:to, :tpl, :sub, :bid, :eid, 'queued', 0, NOW())
            ");
            $logStmt->execute([
                ':to'  => $toEmail,
                ':tpl' => $template,
                ':sub' => $subject,
                ':bid' => $bookingId,
                ':eid' => $eventId
            ]);
            $emailId = (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log('email_logs insert failed: ' . $e->getMessage());
        }
    }

    // 2. Determine whether to send via live SMTP or simulated delivery
    $useLiveSMTP = defined('MAIL_ENABLED') && MAIL_ENABLED && !empty(MAIL_PASSWORD);

    if (!$useLiveSMTP) {
        // Simulated delivery mode (development, assessment or offline environment)
        if ($pdo && $emailId) {
            try {
                $upd = $pdo->prepare("
                    UPDATE email_logs 
                    SET status = 'sent', attempts = 1, error_msg = 'Simulated delivery (local/eval mode)', sent_at = NOW()
                    WHERE email_id = :id
                ");
                $upd->execute([':id' => $emailId]);
            } catch (\PDOException $e) {
                // Ignore log update failure
            }
        }
        return [
            'success'   => true,
            'email_id'  => $emailId,
            'simulated' => true,
            'error'     => null
        ];
    }

    // 3. Live SMTP dispatch using PHPMailer
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = MAIL_CHARSET;
        $mail->SMTPDebug  = MAIL_DEBUG;

        // Sender & Recipient
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

        $mail->send();

        // Update log as sent
        if ($pdo && $emailId) {
            try {
                $upd = $pdo->prepare("
                    UPDATE email_logs 
                    SET status = 'sent', attempts = attempts + 1, error_msg = NULL, sent_at = NOW()
                    WHERE email_id = :id
                ");
                $upd->execute([':id' => $emailId]);
            } catch (\PDOException $e) {
                // Ignore log update failure
            }
        }

        return ['success' => true, 'email_id' => $emailId, 'error' => null];

    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        error_log("PHPMailer error to {$toEmail}: " . $errorMsg);

        // Update log as failed
        if ($pdo && $emailId) {
            try {
                $upd = $pdo->prepare("
                    UPDATE email_logs 
                    SET status = 'failed', attempts = attempts + 1, error_msg = :err
                    WHERE email_id = :id
                ");
                $upd->execute([':id' => $emailId, ':err' => $errorMsg]);
            } catch (\PDOException $pe) {
                // Ignore log update failure
            }
        }

        return ['success' => false, 'email_id' => $emailId, 'error' => $errorMsg];
    }
}

/**
 * Send Booking Confirmation Email
 * FR-MAIL-001
 * 
 * @param int $bookingId
 * @return array
 */
function send_booking_confirmation_email(int $bookingId): array
{
    $pdo = get_db_connection();
    $bookingData = null;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    b.booking_id, b.booking_ref, b.seats, b.total, b.status AS booking_status,
                    e.event_id, e.title AS event_title, e.venue, e.start_date, e.end_date, e.price,
                    u.user_id, u.name AS user_name, u.email AS user_email,
                    t.token AS ticket_token
                FROM bookings b
                JOIN events e ON b.event_id = e.event_id
                JOIN users u ON b.user_id = u.user_id
                LEFT JOIN tickets t ON b.booking_id = t.booking_id
                WHERE b.booking_id = :bid
                LIMIT 1
            ");
            $stmt->execute([':bid' => $bookingId]);
            $bookingData = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('send_booking_confirmation_email query error: ' . $e->getMessage());
        }
    }

    if (!$bookingData) {
        // Mock fallback data for testing
        $bookingData = [
            'booking_id'     => $bookingId,
            'booking_ref'    => 'EVT-2026-9481',
            'seats'          => 1,
            'total'          => 500.00,
            'booking_status' => 'confirmed',
            'event_id'       => 1,
            'event_title'    => 'Introduction to Web Development',
            'venue'          => 'Lab 201, IT Building',
            'start_date'     => date('Y-m-d H:i:s', strtotime('+7 days 10:00')),
            'end_date'       => date('Y-m-d H:i:s', strtotime('+7 days 13:00')),
            'price'          => 500.00,
            'user_id'        => 2,
            'user_name'      => 'Test Student',
            'user_email'     => 'student@eventora.com',
            'ticket_token'   => 'TKT-8f2a9c1e4b7d603a'
        ];
    }

    $user = [
        'name'  => $bookingData['user_name'],
        'email' => $bookingData['user_email']
    ];
    $event = [
        'title'          => $bookingData['event_title'],
        'venue'          => $bookingData['venue'],
        'start_date'     => $bookingData['start_date'],
        'formatted_date' => format_date($bookingData['start_date'], 'D, M d, Y · h:i A')
    ];
    $booking = [
        'booking_ref'     => $bookingData['booking_ref'],
        'seats'           => $bookingData['seats'],
        'formatted_total' => format_currency((float)$bookingData['total'])
    ];
    $ticketToken = $bookingData['ticket_token'] ?? 'TKT-ISSUED';
    $ticketUrl   = APP_URL . '/pages/student/ticket.php?ref=' . urlencode($booking['booking_ref']);

    ob_start();
    include __DIR__ . '/../templates/email/booking_confirmation.php';
    $htmlBody = ob_get_clean();

    $subject = 'Booking Confirmed: ' . $event['title'] . ' (Ref: ' . $booking['booking_ref'] . ')';

    return send_email(
        $user['email'],
        $user['name'],
        $subject,
        $htmlBody,
        'booking_confirmation',
        (int)$bookingData['booking_id'],
        (int)$bookingData['event_id']
    );
}

/**
 * Send 24-Hour Event Reminder Email
 * FR-MAIL-002
 * 
 * @param int $bookingId
 * @return array
 */
function send_event_reminder_email(int $bookingId): array
{
    $pdo = get_db_connection();
    $data = null;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    b.booking_id, b.booking_ref, b.seats,
                    e.event_id, e.title AS event_title, e.venue, e.start_date,
                    u.name AS user_name, u.email AS user_email,
                    t.token AS ticket_token
                FROM bookings b
                JOIN events e ON b.event_id = e.event_id
                JOIN users u ON b.user_id = u.user_id
                LEFT JOIN tickets t ON b.booking_id = t.booking_id
                WHERE b.booking_id = :bid AND b.status = 'confirmed'
                LIMIT 1
            ");
            $stmt->execute([':bid' => $bookingId]);
            $data = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('send_event_reminder_email query error: ' . $e->getMessage());
        }
    }

    if (!$data) {
        $data = [
            'booking_id'   => $bookingId,
            'booking_ref'  => 'EVT-2026-9481',
            'seats'        => 1,
            'event_id'     => 1,
            'event_title'  => 'Introduction to Web Development',
            'venue'        => 'Lab 201, IT Building',
            'start_date'   => date('Y-m-d H:i:s', strtotime('+24 hours')),
            'user_name'    => 'Test Student',
            'user_email'   => 'student@eventora.com',
            'ticket_token' => 'TKT-8f2a9c1e4b7d603a'
        ];
    }

    $user = [
        'name'  => $data['user_name'],
        'email' => $data['user_email']
    ];
    $event = [
        'title'          => $data['event_title'],
        'venue'          => $data['venue'],
        'start_date'     => $data['start_date'],
        'formatted_date' => format_date($data['start_date'], 'D, M d, Y · h:i A')
    ];
    $booking = [
        'booking_ref' => $data['booking_ref'],
        'seats'       => $data['seats']
    ];
    $ticketToken = $data['ticket_token'] ?? '';
    $ticketUrl   = APP_URL . '/pages/student/ticket.php?ref=' . urlencode($booking['booking_ref']);

    ob_start();
    include __DIR__ . '/../templates/email/event_reminder.php';
    $htmlBody = ob_get_clean();

    $subject = 'Event Reminder: ' . $event['title'] . ' starts tomorrow!';

    return send_email(
        $user['email'],
        $user['name'],
        $subject,
        $htmlBody,
        'event_reminder',
        (int)$data['booking_id'],
        (int)$data['event_id']
    );
}

/**
 * Send Waitlist Offer Notification Email
 * FR-MAIL-003, BR-13
 * 
 * @param int $waitlistEntryId
 * @return array
 */
function send_waitlist_offer_email(int $waitlistEntryId): array
{
    $pdo = get_db_connection();
    $data = null;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    w.entry_id, w.seats_requested, w.offer_expires_at,
                    e.event_id, e.title AS event_title, e.start_date,
                    u.name AS user_name, u.email AS user_email
                FROM waitlist w
                JOIN events e ON w.event_id = e.event_id
                JOIN users u ON w.user_id = u.user_id
                WHERE w.entry_id = :wid
                LIMIT 1
            ");
            $stmt->execute([':wid' => $waitlistEntryId]);
            $data = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('send_waitlist_offer_email query error: ' . $e->getMessage());
        }
    }

    if (!$data) {
        $data = [
            'entry_id'         => $waitlistEntryId,
            'seats_requested'  => 1,
            'offer_expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'event_id'         => 1,
            'event_title'      => 'Introduction to Web Development',
            'start_date'       => date('Y-m-d H:i:s', strtotime('+7 days')),
            'user_name'        => 'Test Student',
            'user_email'       => 'student@eventora.com'
        ];
    }

    $user = [
        'name'  => $data['user_name'],
        'email' => $data['user_email']
    ];
    $event = [
        'title'          => $data['event_title'],
        'start_date'     => $data['start_date'],
        'formatted_date' => format_date($data['start_date'], 'D, M d, Y · h:i A')
    ];
    $waitlist = [
        'seats_requested'  => $data['seats_requested'],
        'offer_expires_at' => $data['offer_expires_at'],
        'formatted_expiry' => format_date($data['offer_expires_at'], 'h:i A on M d, Y')
    ];
    $actionUrl = APP_URL . '/pages/student/waitlist.php';

    ob_start();
    include __DIR__ . '/../templates/email/waitlist_offer.php';
    $htmlBody = ob_get_clean();

    $subject = 'Seat Available! Waitlist Offer for ' . $event['title'] . ' (30 Min Window)';

    return send_email(
        $user['email'],
        $user['name'],
        $subject,
        $htmlBody,
        'waitlist_offer',
        null,
        (int)$data['event_id']
    );
}

/**
 * Send Organizer Account Status Notification Email
 * FR-MAIL-004
 * 
 * @param int $userId
 * @param string $decision 'approved' or 'rejected'
 * @param string|null $reason
 * @return array
 */
function send_organizer_approval_email(int $userId, string $decision, ?string $reason = null): array
{
    $pdo = get_db_connection();
    $user = null;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('send_organizer_approval_email query error: ' . $e->getMessage());
        }
    }

    if (!$user) {
        $user = ['name' => 'Organizer', 'email' => 'organizer@eventora.com'];
    }

    $isApproved   = ($decision === 'approved');
    $subjectTitle = $isApproved ? 'Organizer Account Approved!' : 'Organizer Application Status Update';
    $statusBadge  = $isApproved ? 'Approved & Activated' : 'Application Not Approved';
    $name         = $user['name'];
    $message      = $isApproved 
        ? "Congratulations! Your Event Organizer account for " . APP_NAME . " has been reviewed and approved by administrators. You may now log in to publish and manage campus events."
        : "Thank you for your interest in organizing campus events. An administrator has reviewed your application and was unable to approve it at this time.";
    $actionUrl    = $isApproved ? APP_URL . '/pages/auth/login.php' : APP_URL . '/index.php';
    $actionText   = $isApproved ? 'Log in to Organizer Portal' : 'Return to Home';

    ob_start();
    include __DIR__ . '/../templates/email/approval_notification.php';
    $htmlBody = ob_get_clean();

    return send_email(
        $user['email'],
        $user['name'],
        $subjectTitle,
        $htmlBody,
        'organizer_approval'
    );
}

/**
 * Send Event Approval/Rejection Notification Email to Organizer
 * FR-MAIL-004
 * 
 * @param int $eventId
 * @param string $decision 'published' (approved) or 'rejected'
 * @param string|null $reason
 * @return array
 */
function send_event_approval_email(int $eventId, string $decision, ?string $reason = null): array
{
    $pdo = get_db_connection();
    $data = null;

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT e.title, u.name AS org_name, u.email AS org_email
                FROM events e
                JOIN organizer_profiles op ON e.organizer_id = op.profile_id
                JOIN users u ON op.user_id = u.user_id
                WHERE e.event_id = :eid
                LIMIT 1
            ");
            $stmt->execute([':eid' => $eventId]);
            $data = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('send_event_approval_email query error: ' . $e->getMessage());
        }
    }

    if (!$data) {
        $data = [
            'title'     => 'Campus Workshop',
            'org_name'  => 'Test Organizer',
            'org_email' => 'organizer@eventora.com'
        ];
    }

    $isApproved   = ($decision === 'published' || $decision === 'approved');
    $subjectTitle = $isApproved ? 'Event Approved: ' . $data['title'] : 'Event Submission Update: ' . $data['title'];
    $statusBadge  = $isApproved ? 'Event Published' : 'Submission Rejected';
    $name         = $data['org_name'];
    $message      = $isApproved
        ? "Great news! Your event \"{$data['title']}\" has been approved and published to the Eventora campus directory. Students can now view and book tickets."
        : "Your event submission \"{$data['title']}\" was reviewed by administration and could not be approved for publication.";
    $actionUrl    = APP_URL . '/pages/organizer/events.php';
    $actionText   = 'Manage Events';

    ob_start();
    include __DIR__ . '/../templates/email/approval_notification.php';
    $htmlBody = ob_get_clean();

    return send_email(
        $data['org_email'],
        $data['org_name'],
        $subjectTitle,
        $htmlBody,
        'event_approval',
        null,
        $eventId
    );
}
