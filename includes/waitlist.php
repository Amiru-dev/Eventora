<?php
/**
 * Eventora - Waitlist Business Logic
 * FR-WAIT-001 to FR-WAIT-005, BR-13, UC-11
 *
 * FIFO queue: positions assigned on join, seat offers issued on cancellation/expansion,
 * 30-minute offer window (BR-13), automatic expiry handled by cron/expire_waitlist.php.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Join the waitlist for a sold-out event (FR-WAIT-001, BR-13)
 *
 * @param int $userId
 * @param int $eventId
 * @param int $seatsRequested
 * @return array ['success' => bool, 'position' => int|null, 'error' => string|null]
 */
function join_waitlist(int $userId, int $eventId, int $seatsRequested = 1): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        // Offline fallback
        return ['success' => true, 'position' => 3, 'error' => null];
    }

    try {
        // Validate event exists and has waitlist enabled
        $evtStmt = $pdo->prepare("SELECT event_id, title, waitlist_enabled, seats_available, status FROM events WHERE event_id = :id LIMIT 1");
        $evtStmt->execute([':id' => $eventId]);
        $event = $evtStmt->fetch();

        if (!$event) {
            return ['success' => false, 'position' => null, 'error' => 'Event not found.'];
        }
        if ($event['status'] !== 'published') {
            return ['success' => false, 'position' => null, 'error' => 'This event is not accepting waitlist registrations.'];
        }
        if (!(int)$event['waitlist_enabled']) {
            return ['success' => false, 'position' => null, 'error' => 'The waitlist is not enabled for this event.'];
        }
        if ((int)$event['seats_available'] > 0) {
            return ['success' => false, 'position' => null, 'error' => 'Seats are still available — book directly instead of joining the waitlist.'];
        }

        // Check if already on waitlist (active entry)
        $existStmt = $pdo->prepare("
            SELECT entry_id FROM waitlist
            WHERE user_id = :uid AND event_id = :eid AND status IN ('waiting', 'offered')
            LIMIT 1
        ");
        $existStmt->execute([':uid' => $userId, ':eid' => $eventId]);
        if ($existStmt->fetch()) {
            return ['success' => false, 'position' => null, 'error' => 'You are already on the waitlist for this event.'];
        }

        // Assign next FIFO position
        $posStmt = $pdo->prepare("
            SELECT COALESCE(MAX(position), 0) + 1 AS next_pos
            FROM waitlist
            WHERE event_id = :eid AND status IN ('waiting', 'offered')
        ");
        $posStmt->execute([':eid' => $eventId]);
        $nextPos = (int)$posStmt->fetchColumn();

        // Insert waitlist entry
        $insStmt = $pdo->prepare("
            INSERT INTO waitlist (user_id, event_id, position, seats_requested, status, created_at)
            VALUES (:uid, :eid, :pos, :seats, 'waiting', NOW())
        ");
        $insStmt->execute([
            ':uid'   => $userId,
            ':eid'   => $eventId,
            ':pos'   => $nextPos,
            ':seats' => $seatsRequested
        ]);

        return ['success' => true, 'position' => $nextPos, 'error' => null];

    } catch (\PDOException $e) {
        // Duplicate key — already on waitlist
        if ($e->getCode() == 23000) {
            return ['success' => false, 'position' => null, 'error' => 'You are already on the waitlist for this event.'];
        }
        error_log('join_waitlist error: ' . $e->getMessage());
        return ['success' => false, 'position' => null, 'error' => 'An unexpected error occurred. Please try again.'];
    }
}

/**
 * Clear older terminal waitlist rows for the same user/event/status so legacy
 * databases with uk_waitlist_user_event never hit duplicate-key errors.
 */
function clear_prior_waitlist_status(PDO $pdo, int $userId, int $eventId, string $status): void
{
    $stmt = $pdo->prepare("
        DELETE FROM waitlist
        WHERE user_id = :uid AND event_id = :eid AND status = :status
    ");
    $stmt->execute([':uid' => $userId, ':eid' => $eventId, ':status' => $status]);
}

/**
 * Leave (withdraw from) the waitlist (FR-WAIT-003)
 *
 * @param int $userId
 * @param int $eventId
 * @return array ['success' => bool, 'error' => string|null]
 */
function leave_waitlist(int $userId, int $eventId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['success' => true, 'error' => null];
    }

    try {
        clear_prior_waitlist_status($pdo, $userId, $eventId, 'left');

        $stmt = $pdo->prepare("
            UPDATE waitlist SET status = 'left'
            WHERE user_id = :uid AND event_id = :eid AND status IN ('waiting', 'offered')
        ");
        $stmt->execute([':uid' => $userId, ':eid' => $eventId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'No active waitlist entry found for this event.'];
        }

        // Resequence positions for remaining waiters
        resequence_waitlist($eventId);

        return ['success' => true, 'error' => null];

    } catch (\PDOException $e) {
        error_log('leave_waitlist error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Could not remove from waitlist. Please try again.'];
    }
}

/**
 * Accept a waitlist seat offer (FR-WAIT-004)
 * Marks the entry as 'accepted'; the student should then proceed to booking.
 *
 * @param int $userId
 * @param int $entryId
 * @return array ['success' => bool, 'event_id' => int|null, 'seats' => int, 'error' => string|null]
 */
function accept_waitlist_offer(int $userId, int $entryId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['success' => true, 'event_id' => 1, 'seats' => 1, 'error' => null];
    }

    try {
        // Fetch and validate offer
        $fetchStmt = $pdo->prepare("
            SELECT entry_id, event_id, seats_requested, status, offer_expires_at
            FROM waitlist
            WHERE entry_id = :eid AND user_id = :uid
            LIMIT 1
        ");
        $fetchStmt->execute([':eid' => $entryId, ':uid' => $userId]);
        $entry = $fetchStmt->fetch();

        if (!$entry) {
            return ['success' => false, 'event_id' => null, 'seats' => 0, 'error' => 'Waitlist entry not found.'];
        }
        if ($entry['status'] !== 'offered') {
            return ['success' => false, 'event_id' => null, 'seats' => 0, 'error' => 'No active seat offer found on this entry.'];
        }
        if (!empty($entry['offer_expires_at']) && strtotime($entry['offer_expires_at']) < time()) {
            // Auto-expire
            clear_prior_waitlist_status($pdo, $userId, (int)$entry['event_id'], 'expired');
            $pdo->prepare("UPDATE waitlist SET status = 'expired' WHERE entry_id = :id")->execute([':id' => $entryId]);
            return ['success' => false, 'event_id' => null, 'seats' => 0, 'error' => 'Your offer has expired. Please re-join the waitlist.'];
        }

        // Mark accepted
        clear_prior_waitlist_status($pdo, $userId, (int)$entry['event_id'], 'accepted');
        $stmt = $pdo->prepare("UPDATE waitlist SET status = 'accepted' WHERE entry_id = :id");
        $stmt->execute([':id' => $entryId]);

        return [
            'success'  => true,
            'event_id' => (int)$entry['event_id'],
            'seats'    => (int)$entry['seats_requested'],
            'error'    => null
        ];

    } catch (\PDOException $e) {
        error_log('accept_waitlist_offer error: ' . $e->getMessage());
        return ['success' => false, 'event_id' => null, 'seats' => 0, 'error' => 'Could not accept offer. Please try again.'];
    }
}

/**
 * Decline a waitlist offer (FR-WAIT-004)
 *
 * @param int $userId
 * @param int $entryId
 * @return array ['success' => bool, 'error' => string|null]
 */
function decline_waitlist_offer(int $userId, int $entryId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['success' => true, 'error' => null];
    }

    try {
        $evStmt = $pdo->prepare("SELECT event_id FROM waitlist WHERE entry_id = :eid AND user_id = :uid AND status = 'offered' LIMIT 1");
        $evStmt->execute([':eid' => $entryId, ':uid' => $userId]);
        $evId = $evStmt->fetchColumn();
        if (!$evId) {
            return ['success' => false, 'error' => 'No active offer found to decline.'];
        }

        clear_prior_waitlist_status($pdo, $userId, (int)$evId, 'declined');

        $stmt = $pdo->prepare("
            UPDATE waitlist SET status = 'declined'
            WHERE entry_id = :eid AND user_id = :uid AND status = 'offered'
        ");
        $stmt->execute([':eid' => $entryId, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'No active offer found to decline.'];
        }

        return ['success' => true, 'error' => null];

    } catch (\PDOException $e) {
        error_log('decline_waitlist_offer error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Could not decline offer.'];
    }
}

/**
 * Offer freed seat to next person in FIFO queue (FR-WAIT-002, BR-13)
 * Called when a booking is cancelled or seats become available.
 *
 * @param int $eventId
 * @param int $seatsFreed  How many seats just became available
 * @return int  Number of offers made
 */
function offer_seats_to_waitlist(int $eventId, int $seatsFreed = 1): int
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return 0;
    }

    try {
        $offerExpiry = date('Y-m-d H:i:s', time() + (WAITLIST_OFFER_MINUTES * 60));

        // Get next $seatsFreed waiters in FIFO order
        $fetchStmt = $pdo->prepare("
            SELECT entry_id, user_id, seats_requested
            FROM waitlist
            WHERE event_id = :eid AND status = 'waiting'
            ORDER BY position ASC
            LIMIT :limit
        ");
        $fetchStmt->bindValue(':eid', $eventId, \PDO::PARAM_INT);
        $fetchStmt->bindValue(':limit', $seatsFreed, \PDO::PARAM_INT);
        $fetchStmt->execute();
        $waiters = $fetchStmt->fetchAll();

        $offersCount = 0;
        foreach ($waiters as $waiter) {
            $updStmt = $pdo->prepare("
                UPDATE waitlist
                SET status = 'offered', offer_expires_at = :exp
                WHERE entry_id = :id
            ");
            $updStmt->execute([':exp' => $offerExpiry, ':id' => $waiter['entry_id']]);
            $offersCount++;

            // Dispatch waitlist offer email notification (FR-MAIL-003, BR-13)
            if (file_exists(__DIR__ . '/mailer.php')) {
                require_once __DIR__ . '/mailer.php';
                try {
                    send_waitlist_offer_email((int)$waiter['entry_id']);
                } catch (\Throwable $me) {
                    error_log('Waitlist offer email error: ' . $me->getMessage());
                }
            }
        }

        return $offersCount;

    } catch (\PDOException $e) {
        error_log('offer_seats_to_waitlist error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Expire overdue offers and promote next waiter (FR-WAIT-005, BR-13)
 * Called by cron/expire_waitlist.php
 *
 * @return array ['expired' => int, 'promoted' => int]
 */
function expire_waitlist_offers(): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['expired' => 0, 'promoted' => 0];
    }

    try {
        $pdo->beginTransaction();

        // Find expired offers grouped by event
        $expiredStmt = $pdo->prepare("
            SELECT entry_id, user_id, event_id
            FROM waitlist
            WHERE status = 'offered' AND offer_expires_at <= NOW()
        ");
        $expiredStmt->execute();
        $expiredEntries = $expiredStmt->fetchAll();

        if (empty($expiredEntries)) {
            $pdo->commit();
            return ['expired' => 0, 'promoted' => 0];
        }

        foreach ($expiredEntries as $exp) {
            clear_prior_waitlist_status($pdo, (int)$exp['user_id'], (int)$exp['event_id'], 'expired');
        }

        // Mark each expired
        $expiredIds = array_column($expiredEntries, 'entry_id');
        $placeholders = implode(',', array_fill(0, count($expiredIds), '?'));
        $markExpired = $pdo->prepare("UPDATE waitlist SET status = 'expired' WHERE entry_id IN ($placeholders)");
        $markExpired->execute($expiredIds);
        $expiredCount = $markExpired->rowCount();

        // For each affected event, promote next waiter in queue
        $affectedEventIds = array_unique(array_column($expiredEntries, 'event_id'));
        $promotedCount = 0;
        $offerExpiry = date('Y-m-d H:i:s', time() + (WAITLIST_OFFER_MINUTES * 60));

        foreach ($affectedEventIds as $eventId) {
            $nextStmt = $pdo->prepare("
                SELECT entry_id FROM waitlist
                WHERE event_id = :eid AND status = 'waiting'
                ORDER BY position ASC LIMIT 1
            ");
            $nextStmt->execute([':eid' => $eventId]);
            $next = $nextStmt->fetchColumn();

            if ($next) {
                $pdo->prepare("UPDATE waitlist SET status = 'offered', offer_expires_at = :exp WHERE entry_id = :id")
                    ->execute([':exp' => $offerExpiry, ':id' => $next]);
                $promotedCount++;
            }
        }

        $pdo->commit();
        return ['expired' => $expiredCount, 'promoted' => $promotedCount];

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('expire_waitlist_offers error: ' . $e->getMessage());
        return ['expired' => 0, 'promoted' => 0];
    }
}

/**
 * Resequence FIFO positions after a member leaves (FR-WAIT-001)
 *
 * @param int $eventId
 */
function resequence_waitlist(int $eventId): void
{
    $pdo = get_db_connection();
    if (!$pdo) return;

    try {
        $rows = $pdo->prepare("
            SELECT entry_id FROM waitlist
            WHERE event_id = :eid AND status IN ('waiting', 'offered')
            ORDER BY position ASC
        ");
        $rows->execute([':eid' => $eventId]);
        $entries = $rows->fetchAll(\PDO::FETCH_COLUMN);

        $upd = $pdo->prepare("UPDATE waitlist SET position = :pos WHERE entry_id = :id");
        foreach ($entries as $newPos => $entryId) {
            $upd->execute([':pos' => $newPos + 1, ':id' => $entryId]);
        }
    } catch (\PDOException $e) {
        error_log('resequence_waitlist error: ' . $e->getMessage());
    }
}

/**
 * Get all active waitlist entries for a student (FR-WAIT-001, FR-WAIT-002)
 *
 * @param int $userId
 * @return array
 */
function get_student_waitlist(int $userId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return get_mock_student_waitlist();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                w.entry_id, w.position, w.seats_requested, w.status,
                w.offer_expires_at, w.created_at,
                e.event_id, e.title AS event_title, e.category, e.venue,
                e.start_date, e.price, e.seats_available
            FROM waitlist w
            JOIN events e ON w.event_id = e.event_id
            WHERE w.user_id = :uid AND w.status IN ('waiting', 'offered')
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $results = $stmt->fetchAll();
        return $results ?: get_mock_student_waitlist();
    } catch (\PDOException $e) {
        error_log('get_student_waitlist error: ' . $e->getMessage());
        return get_mock_student_waitlist();
    }
}

/**
 * Get all waitlist entries for an event (Organizer view) (FR-WAIT-001)
 *
 * @param int $eventId
 * @return array
 */
function get_event_waitlist(int $eventId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                w.entry_id, w.position, w.seats_requested, w.status,
                w.offer_expires_at, w.created_at,
                u.user_id, u.name AS student_name, u.email AS student_email
            FROM waitlist w
            JOIN users u ON w.user_id = u.user_id
            WHERE w.event_id = :eid AND w.status IN ('waiting', 'offered')
            ORDER BY w.position ASC
        ");
        $stmt->execute([':eid' => $eventId]);
        return $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log('get_event_waitlist error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Check if a user is on the waitlist for a given event
 *
 * @param int $userId
 * @param int $eventId
 * @return array|null  Waitlist entry or null
 */
function get_waitlist_entry(int $userId, int $eventId): ?array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT entry_id, position, seats_requested, status, offer_expires_at, created_at
            FROM waitlist
            WHERE user_id = :uid AND event_id = :eid AND status IN ('waiting', 'offered')
            LIMIT 1
        ");
        $stmt->execute([':uid' => $userId, ':eid' => $eventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (\PDOException $e) {
        error_log('get_waitlist_entry error: ' . $e->getMessage());
        return null;
    }
}

// ============================================================
// MOCK FALLBACKS (offline testing)
// ============================================================

function get_mock_student_waitlist(): array
{
    return [
        [
            'entry_id'        => 1,
            'position'        => 2,
            'seats_requested' => 1,
            'status'          => 'waiting',
            'offer_expires_at'=> null,
            'created_at'      => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'event_id'        => 6,
            'event_title'     => 'Photography Workshop',
            'category'        => 'Workshop',
            'venue'           => 'Room 305, Arts Building',
            'start_date'      => date('Y-m-d H:i:s', strtotime('+5 days')),
            'price'           => 750.00,
            'seats_available' => 0
        ],
        [
            'entry_id'        => 2,
            'position'        => 1,
            'seats_requested' => 2,
            'status'          => 'offered',
            'offer_expires_at'=> date('Y-m-d H:i:s', strtotime('+18 minutes')),
            'created_at'      => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'event_id'        => 5,
            'event_title'     => 'Hackathon: Code for Change',
            'category'        => 'Tech',
            'venue'           => 'Innovation Hub',
            'start_date'      => date('Y-m-d H:i:s', strtotime('+10 days')),
            'price'           => 1000.00,
            'seats_available' => 0
        ]
    ];
}
