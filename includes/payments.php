<?php
/**
 * Eventora - PayHere Sandbox Payment Processing Engine
 * 
 * Implements:
 * - FR-PAY-001: Initiate checkout using PayHere's sandbox environment only
 * - FR-PAY-002: Transmit merchant, order, amount, currency and callback parameters
 * - FR-PAY-003: Associate unique order reference with booking and payment attempt
 * - FR-PAY-004: Cryptographic hash signature verification using merchant secret
 * - FR-PAY-005: Update status strictly from verified server-side notification (not browser alone)
 * - FR-PAY-006: Idempotent payment callback processing (safe against duplicate posts)
 * - FR-PAY-007: Record transaction ref, order ref, amount, currency, status, response code, timestamp
 * - FR-PAY-008: Release seat hold if payment is failed, cancelled or expired
 * - FR-PAY-009: Clear sandbox mode indicator (no card credential storage)
 * - FR-PAY-010: Display clear pending state when verification is in-flight
 * - BR-07, BR-19
 * - NFR-SEC-006 & NFR-SEC-008
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/payhere.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

/**
 * Generate PayHere Checkout Security Hash
 * Formula: strtoupper(md5(merchant_id + order_id + amountFormatted + currency + strtoupper(md5(merchant_secret))))
 * FR-PAY-004, NFR-SEC-006
 * 
 * @param string $orderId
 * @param float $amount
 * @param string $currency
 * @return string
 */
function generate_payhere_hash(string $orderId, float $amount, string $currency = 'LKR'): string
{
    $formattedAmount = number_format($amount, 2, '.', '');
    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));
    $rawString = PAYHERE_MERCHANT_ID . $orderId . $formattedAmount . $currency . $secretHash;
    return strtoupper(md5($rawString));
}

/**
 * Verify PayHere Server Callback Signature
 * Formula: strtoupper(md5(merchant_id + order_id + payhere_amount + payhere_currency + status_code + strtoupper(md5(merchant_secret))))
 * FR-PAY-004, NFR-SEC-006
 * 
 * @param array $postData
 * @return bool
 */
function verify_payhere_signature(array $postData): bool
{
    $merchantId      = $postData['merchant_id'] ?? '';
    $orderId         = $postData['order_id'] ?? '';
    $payhereAmount   = $postData['payhere_amount'] ?? '';
    $payhereCurrency = $postData['payhere_currency'] ?? '';
    $statusCode      = $postData['status_code'] ?? '';
    $md5sig          = $postData['md5sig'] ?? '';

    if (empty($md5sig) || empty($orderId)) {
        return false;
    }

    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));
    $computedSig = strtoupper(md5($merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . $secretHash));

    return hash_equals($computedSig, $md5sig);
}

/**
 * Get or create a unique order payment record for a booking
 * FR-PAY-003, FR-PAY-007
 * 
 * @param int $bookingId
 * @param float $amount
 * @return array ['order_ref' => string, 'payment_id' => int]
 */
function get_or_create_payment_order(int $bookingId, float $amount): array
{
    try {
        $pdo = getDBConnection();

        // Check if an existing pending payment attempt exists
        $stmt = $pdo->prepare("
            SELECT payment_id, order_ref, status FROM payments
            WHERE booking_id = :bid AND status = 'pending'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([':bid' => $bookingId]);
        $existing = $stmt->fetch();

        if ($existing) {
            return [
                'payment_id' => (int)$existing['payment_id'],
                'order_ref'  => $existing['order_ref']
            ];
        }

        // Generate unique order reference (FR-PAY-003)
        $orderRef = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        $ins = $pdo->prepare("
            INSERT INTO payments (order_ref, booking_id, amount, currency, status, created_at)
            VALUES (:oref, :bid, :amt, 'LKR', 'pending', NOW())
        ");
        $ins->execute([
            ':oref' => $orderRef,
            ':bid'  => $bookingId,
            ':amt'  => round($amount, 2)
        ]);

        return [
            'payment_id' => (int)$pdo->lastInsertId(),
            'order_ref'  => $orderRef
        ];
    } catch (PDOException $e) {
        error_log("Payment order creation error: " . $e->getMessage());
        return [
            'payment_id' => 0,
            'order_ref'  => 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)))
        ];
    }
}

/**
 * Idempotent PayHere Server Notification Processing
 * FR-PAY-004, FR-PAY-005, FR-PAY-006, FR-PAY-007, FR-PAY-008, BR-07, BR-19
 * 
 * @param array $postData
 * @return array ['success' => bool, 'message' => string, 'http_code' => int]
 */
function process_payhere_notification(array $postData): array
{
    // 1. Verify Cryptographic Signature (FR-PAY-004, NFR-SEC-006)
    if (!verify_payhere_signature($postData)) {
        error_log("PayHere notification signature verification failed for order: " . ($postData['order_id'] ?? 'unknown'));
        return ['success' => false, 'message' => 'Invalid signature verification.', 'http_code' => 400];
    }

    $orderRef        = $postData['order_id'];
    $payhereTxRef    = $postData['payment_id'] ?? '';
    $statusCode      = (int)($postData['status_code'] ?? -99);
    $paidAmount      = (float)($postData['payhere_amount'] ?? 0.0);
    $currency        = $postData['payhere_currency'] ?? 'LKR';

    try {
        $pdo = getDBConnection();

        // 2. Lookup existing payment record
        $stmt = $pdo->prepare("
            SELECT p.*, b.booking_id, b.user_id, b.event_id, b.seats, b.status as booking_status, b.total as booking_total
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            WHERE p.order_ref = :oref
            LIMIT 1
        ");
        $stmt->execute([':oref' => $orderRef]);
        $payment = $stmt->fetch();

        if (!$payment) {
            return ['success' => false, 'message' => 'Order reference not found.', 'http_code' => 404];
        }

        // 3. IDEMPOTENCY CHECK (FR-PAY-006): If already successfully confirmed, do nothing further!
        if ($payment['status'] === 'success' && $payment['booking_status'] === 'confirmed') {
            return ['success' => true, 'message' => 'Duplicate notification ignored (idempotent confirmation).', 'http_code' => 200];
        }

        $bookingId = (int)$payment['booking_id'];
        $eventId   = (int)$payment['event_id'];
        $seats     = (int)$payment['seats'];

        $pdo->beginTransaction();

        if ($statusCode === 2) {
            // =========================================================
            // SUCCESSFUL PAYMENT (status_code = 2)
            // =========================================================
            // Update payment record (FR-PAY-007)
            $updPay = $pdo->prepare("
                UPDATE payments SET
                    status = 'success',
                    payhere_tx_ref = :txref,
                    response_code = :rcode,
                    hash_verified = 1,
                    paid_at = NOW(),
                    updated_at = NOW()
                WHERE order_ref = :oref
            ");
            $updPay->execute([
                ':txref' => $payhereTxRef,
                ':rcode' => $statusCode,
                ':oref'  => $orderRef
            ]);

            // Confirm booking and clear seat hold (FR-BKG-005, BR-07)
            $updBkg = $pdo->prepare("
                UPDATE bookings SET
                    status = 'confirmed',
                    hold_expires_at = NULL
                WHERE booking_id = :bid
            ");
            $updBkg->execute([':bid' => $bookingId]);

            // Issue unique opaque QR ticket token if not already issued (FR-TKT-001, NFR-SEC-007)
            $tktCheck = $pdo->prepare("SELECT ticket_id FROM tickets WHERE booking_id = :bid LIMIT 1");
            $tktCheck->execute([':bid' => $bookingId]);
            if (!$tktCheck->fetch()) {
                $ticketToken = 'TKT-' . bin2hex(random_bytes(16));
                $insTkt = $pdo->prepare("
                    INSERT INTO tickets (token, booking_id, event_id, status, created_at)
                    VALUES (:token, :bid, :eid, 'active', NOW())
                ");
                $insTkt->execute([
                    ':token' => $ticketToken,
                    ':bid'   => $bookingId,
                    ':eid'   => $eventId
                ]);
            }

            $pdo->commit();

            log_audit_action($userId, 'payment.verified', 'payment', (int)$payment['payment_id'], [
                'order_ref'     => $orderRef,
                'amount'        => $payhereAmount,
                'status'        => 'success',
                'booking_ref'   => $booking['booking_ref'] ?? null,
                'md5_verified'  => true
            ]);

            // Dispatch booking confirmation email for paid booking (FR-MAIL-001)
            if (file_exists(__DIR__ . '/mailer.php')) {
                require_once __DIR__ . '/mailer.php';
                try {
                    send_booking_confirmation_email($bookingId);
                } catch (\Throwable $me) {
                    error_log('Paid booking confirmation email error: ' . $me->getMessage());
                }
            }

            return ['success' => true, 'message' => 'Payment verified and booking confirmed.', 'http_code' => 200];

        } else {
            // =========================================================
            // FAILED OR CANCELLED PAYMENT (status_code = -1 or -2)
            // =========================================================
            // Mark payment failed (FR-PAY-008)
            $payStatus = ($statusCode === -1) ? 'cancelled' : 'failed';
            $updPay = $pdo->prepare("
                UPDATE payments SET
                    status = :pstatus,
                    response_code = :rcode,
                    hash_verified = 1,
                    updated_at = NOW()
                WHERE order_ref = :oref
            ");
            $updPay->execute([
                ':pstatus' => $payStatus,
                ':rcode'   => $statusCode,
                ':oref'    => $orderRef
            ]);

            // Release held seats back to capacity (FR-PAY-008, BR-08)
            $relSeats = $pdo->prepare("
                UPDATE events SET
                    seats_available = seats_available + :seats,
                    updated_at = NOW()
                WHERE event_id = :eid
            ");
            $relSeats->execute([
                ':seats' => $seats,
                ':eid'   => $eventId
            ]);

            // Update booking status
            $updBkg = $pdo->prepare("
                UPDATE bookings SET
                    status = 'cancelled',
                    cancelled_at = NOW()
                WHERE booking_id = :bid
            ");
            $updBkg->execute([':bid' => $bookingId]);

            $pdo->commit();

            log_audit_action($userId, 'payment.failed', 'payment', (int)($payment['payment_id'] ?? 0), [
                'order_ref'     => $orderRef,
                'status'        => $payStatus,
                'response_code' => $statusCode
            ]);
            return ['success' => true, 'message' => 'Payment failed/cancelled. Seat hold released.', 'http_code' => 200];
        }

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("PayHere notification processing error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Internal database error.', 'http_code' => 500];
    }
}

/**
 * Local simulation of PayHere Sandbox callback for assessment testing
 * Generates valid cryptographic hash and executes the authoritative notification handler.
 * 
 * @param string $orderRef
 * @param float $amount
 * @param int $statusCode (2 = Success, -1 = Cancelled, -2 = Failed)
 * @return array
 */
function simulate_sandbox_payment_callback(string $orderRef, float $amount, int $statusCode = 2): array
{
    $formattedAmount = number_format($amount, 2, '.', '');
    $currency = 'LKR';
    $merchantId = PAYHERE_MERCHANT_ID;
    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));

    // Calculate genuine signature formula
    $computedSig = strtoupper(md5($merchantId . $orderRef . $formattedAmount . $currency . $statusCode . $secretHash));

    $simulatedPost = [
        'merchant_id'      => $merchantId,
        'order_id'         => $orderRef,
        'payment_id'       => 'PAYHERE-TEST-' . strtoupper(bin2hex(random_bytes(4))),
        'payhere_amount'   => $formattedAmount,
        'payhere_currency' => $currency,
        'status_code'      => $statusCode,
        'md5sig'           => $computedSig
    ];

    return process_payhere_notification($simulatedPost);
}
