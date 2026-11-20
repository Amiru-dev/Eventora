<?php
/**
 * Eventora - Booking & Seat Management Engine
 * 
 * Implements:
 * - FR-BKG-001: Student requests whole-number seat quantity within limits
 * - FR-BKG-002: Authoritative server-side pricing recalculation (never trust client)
 * - FR-BKG-003: Pending-payment booking and time-limited seat hold for paid events
 * - FR-BKG-004: Concurrent overbooking prevention via conditional update and transactions
 * - FR-BKG-005: Paid booking confirmation gated by verified server callback
 * - FR-BKG-006: Instant confirmation and ticket issuance for free events
 * - FR-BKG-007: Scoped student booking viewing
 * - FR-BKG-008: Transactional booking cancellation and capacity release
 * - FR-BKG-009: Itemized summary (ref, event, seats, discount breakdown, total)
 * - FR-BKG-010: Automatic expiration of abandoned pending seat holds
 * - BR-02, BR-04, BR-05, BR-07, BR-08, BR-09, BR-12, BR-18, BR-20
 * - NFR-REL-001 & NFR-REL-002: Transactional integrity and concurrency protection
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

/**
 * Recalculate price authoritatively on the server
 * Section 5.1, FR-BKG-002, BR-05, BR-09, BR-12
 * 
 * @param float $unitPrice
 * @param int $seats
 * @param string|null $promoCode
 * @param int|null $userId
 * @return array ['subtotal' => float, 'discount_type' => string|null, 'discount_amount' => float, 'promo_id' => int|null, 'total' => float, 'promo_error' => string|null]
 */
function calculate_booking_price(float $unitPrice, int $seats, ?string $promoCode = null, ?int $userId = null): array
{
    $unitPrice = round(max(0.00, $unitPrice), 2);
    $seats     = max(1, $seats);

    // subtotal = unit_price * quantity
    $subtotal = round($unitPrice * $seats, 2);

    $bulkDiscount = 0.00;
    // BR-05: 10% off for bookings of 5 or more seats
    if ($seats >= BULK_DISCOUNT_MIN_SEATS && $subtotal > 0.00) {
        $bulkDiscount = round($subtotal * BULK_DISCOUNT_PERCENTAGE, 2);
    }

    $promoDiscount = 0.00;
    $promoId = null;
    $promoError = null;

    if (!empty($promoCode)) {
        $code = strtoupper(trim($promoCode));
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT * FROM promo_codes
                WHERE code = :code
                LIMIT 1
            ");
            $stmt->execute([':code' => $code]);
            $promo = $stmt->fetch();

            if (!$promo) {
                $promoError = "Promo code '{$code}' does not exist.";
            } elseif ((int)$promo['is_active'] !== 1) {
                $promoError = "Promo code '{$code}' is inactive.";
            } else {
                $now = time();
                $validFrom = strtotime($promo['valid_from']);
                $validUntil = strtotime($promo['valid_until']);

                if ($now < $validFrom || $now > $validUntil) {
                    $promoError = "Promo code '{$code}' has expired or is not yet valid.";
                } elseif ($promo['min_spend'] !== null && $subtotal < (float)$promo['min_spend']) {
                    $minSpendFmt = format_currency($promo['min_spend']);
                    $promoError = "Promo code '{$code}' requires a minimum spend of {$minSpendFmt}.";
                } elseif ($promo['total_use_limit'] !== null && (int)$promo['times_used'] >= (int)$promo['total_use_limit']) {
                    $promoError = "Promo code '{$code}' has reached its maximum global usage limit.";
                } else {
                    // Check per-user limit if user ID provided (FR-PROMO-006)
                    $userAllowed = true;
                    if ($userId && $promo['per_user_limit'] !== null) {
                        $userUsageStmt = $pdo->prepare("
                            SELECT COUNT(*) FROM bookings
                            WHERE user_id = :uid AND promo_code_id = :pid AND status != 'cancelled'
                        ");
                        $userUsageStmt->execute([':uid' => $userId, ':pid' => $promo['promo_id']]);
                        $timesUsedByUser = (int)$userUsageStmt->fetchColumn();

                        if ($timesUsedByUser >= (int)$promo['per_user_limit']) {
                            $promoError = "You have already reached the usage limit for code '{$code}'.";
                            $userAllowed = false;
                        }
                    }

                    if ($userAllowed) {
                        $promoId = (int)$promo['promo_id'];
                        if ($promo['discount_type'] === 'percentage') {
                            $calc = $subtotal * ((float)$promo['discount_value'] / 100.0);
                            if ($promo['max_discount'] !== null && $calc > (float)$promo['max_discount']) {
                                $calc = (float)$promo['max_discount'];
                            }
                            $promoDiscount = round($calc, 2);
                        } else {
                            // Fixed discount
                            $promoDiscount = round(min($subtotal, (float)$promo['discount_value']), 2);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Promo check fallback
            $promoError = "Could not verify promo code at this moment.";
        }
    }

    // BR-12: Non-stacking rule — Apply single best eligible discount
    $selectedDiscountType = null;
    $selectedDiscountAmount = 0.00;

    if ($bulkDiscount > 0.00 && $promoDiscount > 0.00) {
        if ($bulkDiscount >= $promoDiscount) {
            $selectedDiscountType   = 'bulk';
            $selectedDiscountAmount = $bulkDiscount;
            $promoId                = null; // Bulk chosen, promo discarded
        } else {
            $selectedDiscountType   = 'promo';
            $selectedDiscountAmount = $promoDiscount;
        }
    } elseif ($bulkDiscount > 0.00) {
        $selectedDiscountType   = 'bulk';
        $selectedDiscountAmount = $bulkDiscount;
    } elseif ($promoDiscount > 0.00) {
        $selectedDiscountType   = 'promo';
        $selectedDiscountAmount = $promoDiscount;
    }

    // Discount cannot exceed subtotal
    $selectedDiscountAmount = min($subtotal, $selectedDiscountAmount);
    $total = round(max(0.00, $subtotal - $selectedDiscountAmount), 2);

    return [
        'subtotal'        => $subtotal,
        'discount_type'   => $selectedDiscountType,
        'discount_amount' => $selectedDiscountAmount,
        'promo_id'        => $promoId,
        'total'           => $total,
        'promo_error'     => $promoError
    ];
}

/**
 * Generate a unique non-guessable booking reference
 * 
 * @return string
 */
function generate_booking_reference(): string
{
    return 'EVT-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Create a reservation with database transaction and overbooking prevention
 * FR-BKG-001, FR-BKG-003, FR-BKG-004, FR-BKG-006, BR-02, BR-04, BR-07, BR-08, BR-20
 * 
 * @param int $userId
 * @param int $eventId
 * @param int $seats
 * @param string|null $promoCode
 * @return array ['success' => bool, 'error' => string|null, 'booking' => array|null]
 */
function create_booking(int $userId, int $eventId, int $seats, ?string $promoCode = null): array
{
    $seats = (int)$seats;
    if ($seats < 1) {
        return ['success' => false, 'error' => 'You must book at least 1 seat.'];
    }

    try {
        $pdo = getDBConnection();

        // 1. Fetch event and check booking eligibility (BR-06)
        $evtStmt = $pdo->prepare("
            SELECT * FROM events
            WHERE event_id = :id
            LIMIT 1
        ");
        $evtStmt->execute([':id' => $eventId]);
        $event = $evtStmt->fetch();

        if (!$event) {
            return ['success' => false, 'error' => 'The selected event was not found.'];
        }

        if ($event['status'] !== 'published') {
            return ['success' => false, 'error' => 'This event is not currently accepting bookings (BR-06).'];
        }

        $now = date('Y-m-d H:i:s');
        if ($event['start_date'] <= $now) {
            return ['success' => false, 'error' => 'This event has already started or concluded.'];
        }

        // Max seats per student check (BR-04, FR-BKG-001)
        $maxAllowed = min((int)$event['max_per_student'], MAX_SEATS_PER_STUDENT);
        if ($seats > $maxAllowed) {
            return ['success' => false, 'error' => "You can book a maximum of {$maxAllowed} seats for this event."];
        }

        // Check active seats already booked by this student for this event (BR-04)
        $userSeatCheck = $pdo->prepare("
            SELECT COALESCE(SUM(seats), 0) FROM bookings
            WHERE user_id = :uid AND event_id = :eid AND status IN ('confirmed', 'pending_payment')
        ");
        $userSeatCheck->execute([':uid' => $userId, ':eid' => $eventId]);
        $existingSeats = (int)$userSeatCheck->fetchColumn();

        if (($existingSeats + $seats) > $maxAllowed) {
            $remaining = max(0, $maxAllowed - $existingSeats);
            return [
                'success' => false,
                'error'   => "Booking exceeds your per-student limit (BR-04). You already have {$existingSeats} active seats; you may book at most {$remaining} more."
            ];
        }

        // 2. Authoritative price calculation (FR-BKG-002, BR-05, BR-12, BR-20)
        $unitPrice = (float)$event['price'];
        $pricing = calculate_booking_price($unitPrice, $seats, $promoCode, $userId);

        $subtotal       = $pricing['subtotal'];
        $discountType   = $pricing['discount_type'];
        $discountAmount = $pricing['discount_amount'];
        $promoId        = $pricing['promo_id'];
        $total          = $pricing['total'];
        $isFree         = ($total <= 0.00);

        // 3. Database Transaction & Conditional Overbooking Prevention (FR-BKG-004, NFR-REL-001, NFR-REL-002)
        $pdo->beginTransaction();

        // Atomic conditional decrement: prevents overselling under concurrent requests (FR-BKG-004, BR-02)
        $capUpdate = $pdo->prepare("
            UPDATE events
            SET seats_available = seats_available - :seats, updated_at = NOW()
            WHERE event_id = :event_id AND seats_available >= :seats
        ");
        $capUpdate->execute([
            ':seats'    => $seats,
            ':event_id' => $eventId
        ]);

        if ($capUpdate->rowCount() === 0) {
            $pdo->rollBack();
            return [
                'success' => false,
                'error'   => 'Sorry, there are not enough seats available to fulfill your request (BR-02).'
            ];
        }

        $bookingRef = generate_booking_reference();

        if ($isFree) {
            // Free Event: Instant confirmation and ticket issuance (FR-BKG-006, BR-07)
            $status = 'confirmed';
            $holdExpiresAt = null;

            $bkgStmt = $pdo->prepare("
                INSERT INTO bookings (
                    booking_ref, user_id, event_id, seats, unit_price,
                    subtotal, discount_type, discount_amount, promo_code_id,
                    total, status, hold_expires_at, created_at
                ) VALUES (
                    :ref, :uid, :eid, :seats, :uprice,
                    :subtotal, :dtype, :damt, :pid,
                    :total, :status, :hold, NOW()
                )
            ");
            $bkgStmt->execute([
                ':ref'      => $bookingRef,
                ':uid'      => $userId,
                ':eid'      => $eventId,
                ':seats'    => $seats,
                ':uprice'   => $unitPrice,
                ':subtotal' => $subtotal,
                ':dtype'    => $discountType,
                ':damt'     => $discountAmount,
                ':pid'      => $promoId,
                ':total'    => 0.00,
                ':status'   => $status,
                ':hold'     => $holdExpiresAt
            ]);
            $bookingId = (int)$pdo->lastInsertId();

            // Issue unique opaque QR ticket token (FR-TKT-001, NFR-SEC-007)
            $ticketToken = 'TKT-' . bin2hex(random_bytes(16));
            $tktStmt = $pdo->prepare("
                INSERT INTO tickets (token, booking_id, event_id, status, created_at)
                VALUES (:token, :bid, :eid, 'active', NOW())
            ");
            $tktStmt->execute([
                ':token' => $ticketToken,
                ':bid'   => $bookingId,
                ':eid'   => $eventId
            ]);

        } else {
            // Paid Event: Pending payment and 10-minute seat hold (FR-BKG-003, BR-08)
            $status = 'pending_payment';
            $holdMinutes = defined('SEAT_HOLD_MINUTES') ? SEAT_HOLD_MINUTES : 10;
            $holdExpiresAt = date('Y-m-d H:i:s', strtotime("+{$holdMinutes} minutes"));

            $bkgStmt = $pdo->prepare("
                INSERT INTO bookings (
                    booking_ref, user_id, event_id, seats, unit_price,
                    subtotal, discount_type, discount_amount, promo_code_id,
                    total, status, hold_expires_at, created_at
                ) VALUES (
                    :ref, :uid, :eid, :seats, :uprice,
                    :subtotal, :dtype, :damt, :pid,
                    :total, :status, :hold, NOW()
                )
            ");
            $bkgStmt->execute([
                ':ref'      => $bookingRef,
                ':uid'      => $userId,
                ':eid'      => $eventId,
                ':seats'    => $seats,
                ':uprice'   => $unitPrice,
                ':subtotal' => $subtotal,
                ':dtype'    => $discountType,
                ':damt'     => $discountAmount,
                ':pid'      => $promoId,
                ':total'    => $total,
                ':status'   => $status,
                ':hold'     => $holdExpiresAt
            ]);
            $bookingId = (int)$pdo->lastInsertId();

            // Create initial pending payment attempt record (FR-PAY-003)
            $orderRef = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $payStmt = $pdo->prepare("
                INSERT INTO payments (order_ref, booking_id, amount, currency, status, created_at)
                VALUES (:oref, :bid, :amt, 'LKR', 'pending', NOW())
            ");
            $payStmt->execute([
                ':oref' => $orderRef,
                ':bid'  => $bookingId,
                ':amt'  => $total
            ]);
        }

        // Increment promo code usage if applied
        if ($promoId) {
            $pdo->prepare("UPDATE promo_codes SET times_used = times_used + 1 WHERE promo_id = :pid")->execute([':pid' => $promoId]);
        }

        $pdo->commit();

        return [
            'success' => true,
            'error'   => null,
            'booking' => [
                'booking_id'      => $bookingId,
                'booking_ref'     => $bookingRef,
                'event_id'        => $eventId,
                'event_title'     => $event['title'],
                'seats'           => $seats,
                'unit_price'      => $unitPrice,
                'subtotal'        => $subtotal,
                'discount_type'   => $discountType,
                'discount_amount' => $discountAmount,
                'total'           => $total,
                'status'          => $status,
                'is_free'         => $isFree,
                'hold_expires_at' => $holdExpiresAt
            ]
        ];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Booking creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'A database error occurred while creating your booking.'];
    }
}

/**
 * Retrieve bookings for the authenticated student
 * FR-BKG-007
 * 
 * @param int $userId
 * @return array
 */
function get_student_bookings(int $userId): array
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT b.*, e.title as event_title, e.category, e.venue, e.start_date, e.end_date,
                   t.token as ticket_token, t.status as ticket_status,
                   p.order_ref, p.status as payment_status
            FROM bookings b
            JOIN events e ON b.event_id = e.event_id
            LEFT JOIN tickets t ON b.booking_id = t.booking_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.user_id = :uid
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Retrieve booking details by reference with authorization verification
 * FR-BKG-009, FR-BKG-007
 * 
 * @param string $bookingRef
 * @param int|null $userId
 * @return array|null
 */
function get_booking_by_ref(string $bookingRef, ?int $userId = null): ?array
{
    try {
        $pdo = getDBConnection();
        $sql = "
            SELECT b.*, e.title as event_title, e.category, e.venue, e.start_date, e.end_date, e.organizer_id,
                   t.token as ticket_token, t.status as ticket_status,
                   p.order_ref, p.status as payment_status, p.payhere_tx_ref,
                   u.name as student_name, u.email as student_email
            FROM bookings b
            JOIN events e ON b.event_id = e.event_id
            JOIN users u ON b.user_id = u.user_id
            LEFT JOIN tickets t ON b.booking_id = t.booking_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.booking_ref = :ref
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ref' => trim($bookingRef)]);
        $booking = $stmt->fetch();

        if (!$booking) return null;

        // Verify authorization: must be the booking owner, or admin, or event organizer
        if ($userId !== null) {
            $currentUser = current_user();
            if ($currentUser && $currentUser['role'] !== 'admin') {
                if ((int)$booking['user_id'] !== $userId) {
                    return null; // Forbidden
                }
            }
        }

        return $booking;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Cancel an eligible booking transactionally and release seats
 * FR-BKG-008, BR-18, NFR-REL-001
 * 
 * @param int $bookingId
 * @param int $userId
 * @return array ['success' => bool, 'error' => string|null]
 */
function cancel_booking(int $bookingId, int $userId): array
{
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT b.*, e.start_date
            FROM bookings b
            JOIN events e ON b.event_id = e.event_id
            WHERE b.booking_id = :bid AND b.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':bid' => $bookingId, ':uid' => $userId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'error' => 'Booking not found or access denied.'];
        }

        if ($booking['status'] === 'cancelled') {
            return ['success' => false, 'error' => 'This booking has already been cancelled.'];
        }

        // Cannot cancel past events (FR-BKG-008, BR-18)
        if (strtotime($booking['start_date']) <= time()) {
            return ['success' => false, 'error' => 'Cannot cancel a booking for an event that has already started.'];
        }

        $pdo->beginTransaction();

        // 1. Update booking status
        $cancelStmt = $pdo->prepare("
            UPDATE bookings
            SET status = 'cancelled', cancelled_at = NOW()
            WHERE booking_id = :bid
        ");
        $cancelStmt->execute([':bid' => $bookingId]);

        // 2. Cancel active tickets
        $tktCancel = $pdo->prepare("
            UPDATE tickets
            SET status = 'cancelled'
            WHERE booking_id = :bid
        ");
        $tktCancel->execute([':bid' => $bookingId]);

        // 3. Release capacity transactionally (FR-BKG-008)
        $relCap = $pdo->prepare("
            UPDATE events
            SET seats_available = seats_available + :seats, updated_at = NOW()
            WHERE event_id = :eid
        ");
        $relCap->execute([
            ':seats' => (int)$booking['seats'],
            ':eid'   => (int)$booking['event_id']
        ]);

        $pdo->commit();

        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Booking cancellation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error while cancelling booking.'];
    }
}

/**
 * Automatically expire abandoned pending-payment holds and release held seats
 * FR-BKG-010, BR-08, NFR-REL-001
 * 
 * @return int Number of expired bookings released
 */
function expire_abandoned_seat_holds(): int
{
    $releasedCount = 0;
    try {
        $pdo = getDBConnection();
        $now = date('Y-m-d H:i:s');

        // Find expired pending-payment bookings
        $stmt = $pdo->prepare("
            SELECT booking_id, event_id, seats
            FROM bookings
            WHERE status = 'pending_payment' AND hold_expires_at IS NOT NULL AND hold_expires_at < :now
        ");
        $stmt->execute([':now' => $now]);
        $expiredList = $stmt->fetchAll();

        foreach ($expiredList as $item) {
            $pdo->beginTransaction();

            // Mark booking as expired
            $upd = $pdo->prepare("UPDATE bookings SET status = 'expired' WHERE booking_id = :bid");
            $upd->execute([':bid' => $item['booking_id']]);

            // Release held capacity
            $rel = $pdo->prepare("UPDATE events SET seats_available = seats_available + :seats WHERE event_id = :eid");
            $rel->execute([
                ':seats' => (int)$item['seats'],
                ':eid'   => (int)$item['event_id']
            ]);

            // Mark payment as failed / expired
            $pUpd = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE booking_id = :bid AND status = 'pending'");
            $pUpd->execute([':bid' => $item['booking_id']]);

            $pdo->commit();
            $releasedCount++;
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Hold expiration error: " . $e->getMessage());
    }

    return $releasedCount;
}
