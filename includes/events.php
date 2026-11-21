<?php
/**
 * Eventora - Event Management & Approval Workflow
 * 
 * Implements:
 * - FR-EVT-006: Organizers create and edit owned events
 * - FR-EVT-007: Administrators approve, reject, publish, suspend, cancel and archive events
 * - FR-EVT-008: Record approving admin, decision timestamp and rejection reason
 * - FR-EVT-009: Reject capacity changes lower than seats already committed/held
 * - FR-EVT-010: Hide unpublished, rejected, suspended and cancelled events from public catalog
 * - FR-ORG-001 to FR-ORG-007: Multiple organizers, approval, suspension and access scoping
 * - BR-02: Seat capacity enforcement
 * - BR-06: Future approved event rules
 * - BR-14 & BR-15: Organizer ownership and admin global access
 * - UC-03: Detailed organizer and event approval lifecycle
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';

/**
 * Create a new event draft / submission by an organizer
 * FR-EVT-006, FR-ORG-003, UC-03
 * 
 * @param int $organizerProfileId
 * @param array $data
 * @return array ['success' => bool, 'error' => string|null, 'event_id' => int|null]
 */
function create_event(int $organizerProfileId, array $data): array
{
    $title           = sanitize_input($data['title'] ?? '');
    $category        = sanitize_input($data['category'] ?? '');
    $venue           = sanitize_input($data['venue'] ?? '');
    $startDate       = trim($data['start_date'] ?? '');
    $endDate         = trim($data['end_date'] ?? '');
    $price           = (float)($data['price'] ?? 0.00);
    $capacity        = (int)($data['capacity'] ?? 0);
    $maxPerStudent   = (int)($data['max_per_student'] ?? 5);
    $waitlistEnabled = !empty($data['waitlist_enabled']) ? 1 : 0;
    $description     = trim($data['description'] ?? '');

    // Server-side validations (FR-VAL-002)
    if (mb_strlen($title) < 5) {
        return ['success' => false, 'error' => 'Event title must be at least 5 characters long.'];
    }
    if (empty($category)) {
        return ['success' => false, 'error' => 'Please select an event category.'];
    }
    if (empty($venue)) {
        return ['success' => false, 'error' => 'Campus venue is required.'];
    }
    if (empty($startDate) || empty($endDate)) {
        return ['success' => false, 'error' => 'Both start and end date/times are required.'];
    }

    $startTs = strtotime($startDate);
    $endTs   = strtotime($endDate);
    if ($startTs === false || $endTs === false) {
        return ['success' => false, 'error' => 'Invalid date format provided.'];
    }
    if ($endTs <= $startTs) {
        return ['success' => false, 'error' => 'End date/time must be after the event start time.'];
    }

    if ($price < 0.00) {
        return ['success' => false, 'error' => 'Price cannot be negative (enter 0 for free events).'];
    }
    if ($capacity < 1) {
        return ['success' => false, 'error' => 'Capacity must be at least 1 seat.'];
    }
    if ($maxPerStudent < 1 || $maxPerStudent > MAX_SEATS_PER_STUDENT) {
        $maxLimit = MAX_SEATS_PER_STUDENT;
        return ['success' => false, 'error' => "Max seats per student must be between 1 and {$maxLimit} (BR-04)."];
    }
    if (mb_strlen($description) < 20) {
        return ['success' => false, 'error' => 'Event description must be at least 20 characters long.'];
    }

    try {
        $pdo = getDBConnection();

        // Verify organizer is approved before creating publishable event (BR-14)
        $orgCheck = $pdo->prepare("SELECT approval_status FROM organizer_profiles WHERE profile_id = :id LIMIT 1");
        $orgCheck->execute([':id' => $organizerProfileId]);
        $org = $orgCheck->fetch();

        if (!$org || $org['approval_status'] !== 'approved') {
            return ['success' => false, 'error' => 'Organizer profile is not approved to submit events (FR-AUTH-004).'];
        }

        // Initially submitted with 'pending_approval' status (UC-03 Step 2)
        $sql = "
            INSERT INTO events (
                organizer_id, title, description, category, venue,
                start_date, end_date, price, capacity, seats_available,
                status, waitlist_enabled, max_per_student, created_at
            ) VALUES (
                :organizer_id, :title, :description, :category, :venue,
                :start_date, :end_date, :price, :capacity, :seats_available,
                'pending_approval', :waitlist_enabled, :max_per_student, NOW()
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':organizer_id'     => $organizerProfileId,
            ':title'            => $title,
            ':description'      => $description,
            ':category'         => $category,
            ':venue'            => $venue,
            ':start_date'       => $startDate,
            ':end_date'         => $endDate,
            ':price'            => round($price, 2),
            ':capacity'         => $capacity,
            ':seats_available'  => $capacity, // Initially all seats are open
            ':waitlist_enabled' => $waitlistEnabled,
            ':max_per_student'  => $maxPerStudent
        ]);

        $eventId = (int)$pdo->lastInsertId();
        return ['success' => true, 'error' => null, 'event_id' => $eventId];

    } catch (PDOException $e) {
        error_log("Event creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error while saving event. Please try again.'];
    }
}

/**
 * Update an existing event with capacity enforcement and ownership checks
 * FR-EVT-006, FR-EVT-009, FR-AUTH-007, BR-02
 * 
 * @param int $eventId
 * @param int $organizerProfileId
 * @param array $data
 * @param bool $isAdmin
 * @return array ['success' => bool, 'error' => string|null]
 */
function update_event(int $eventId, int $organizerProfileId, array $data, bool $isAdmin = false): array
{
    try {
        $pdo = getDBConnection();

        // 1. Fetch current event details
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = :id LIMIT 1");
        $stmt->execute([':id' => $eventId]);
        $current = $stmt->fetch();

        if (!$current) {
            return ['success' => false, 'error' => 'Event not found.'];
        }

        // Ownership enforcement (FR-AUTH-007, BR-15)
        if (!$isAdmin && (int)$current['organizer_id'] !== $organizerProfileId) {
            return ['success' => false, 'error' => 'Access denied: You can only edit your own events (FR-AUTH-007).'];
        }

        $newCapacity = isset($data['capacity']) ? (int)$data['capacity'] : (int)$current['capacity'];

        // 2. Capacity change validation (FR-EVT-009, BR-02)
        // Calculate seats already committed or held
        $committedSeats = (int)$current['capacity'] - (int)$current['seats_available'];
        if ($newCapacity < $committedSeats) {
            return [
                'success' => false,
                'error'   => "Cannot reduce capacity to {$newCapacity}. There are already {$committedSeats} seats committed or reserved by students (FR-EVT-009)."
            ];
        }

        // New seats available = new capacity - already committed
        $newSeatsAvailable = $newCapacity - $committedSeats;

        $title           = sanitize_input($data['title'] ?? $current['title']);
        $category        = sanitize_input($data['category'] ?? $current['category']);
        $venue           = sanitize_input($data['venue'] ?? $current['venue']);
        $startDate       = trim($data['start_date'] ?? $current['start_date']);
        $endDate         = trim($data['end_date'] ?? $current['end_date']);
        $price           = isset($data['price']) ? round((float)$data['price'], 2) : (float)$current['price'];
        $maxPerStudent   = isset($data['max_per_student']) ? (int)$data['max_per_student'] : (int)$current['max_per_student'];
        $waitlistEnabled = isset($data['waitlist_enabled']) ? (!empty($data['waitlist_enabled']) ? 1 : 0) : (int)$current['waitlist_enabled'];
        $description     = trim($data['description'] ?? $current['description']);

        // When organizer edits a published event, return it to 'pending_approval' (UC-03)
        $newStatus = $current['status'];
        if (!$isAdmin && $current['status'] === 'published') {
            $newStatus = 'pending_approval';
        }

        $updateSql = "
            UPDATE events SET
                title = :title,
                category = :category,
                venue = :venue,
                start_date = :start_date,
                end_date = :end_date,
                price = :price,
                capacity = :capacity,
                seats_available = :seats_available,
                max_per_student = :max_per_student,
                waitlist_enabled = :waitlist_enabled,
                description = :description,
                status = :status,
                updated_at = NOW()
            WHERE event_id = :event_id
        ";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':title'            => $title,
            ':category'         => $category,
            ':venue'            => $venue,
            ':start_date'       => $startDate,
            ':end_date'         => $endDate,
            ':price'            => $price,
            ':capacity'         => $newCapacity,
            ':seats_available'  => $newSeatsAvailable,
            ':max_per_student'  => $maxPerStudent,
            ':waitlist_enabled' => $waitlistEnabled,
            ':description'      => $description,
            ':status'           => $newStatus,
            ':event_id'         => $eventId
        ]);

        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        error_log("Event update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error during event update.'];
    }
}

/**
 * Retrieve single event by ID with organizer metadata
 * 
 * @param int $eventId
 * @return array|null
 */
function get_event_by_id(int $eventId): ?array
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT e.*, op.organization as organizer_name, op.bio as organizer_bio, u.name as organizer_contact
            FROM events e
            LEFT JOIN organizer_profiles op ON e.organizer_id = op.profile_id
            LEFT JOIN users u ON op.user_id = u.user_id
            WHERE e.event_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get all events belonging to a specific organizer
 * FR-ORG-005
 * 
 * @param int $organizerProfileId
 * @return array
 */
function get_events_by_organizer(int $organizerProfileId): array
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM events
            WHERE organizer_id = :org_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':org_id' => $organizerProfileId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get all events for administrator management
 * FR-EVT-007
 * 
 * @param string|null $statusFilter
 * @return array
 */
function get_all_events_admin(?string $statusFilter = null): array
{
    try {
        $pdo = getDBConnection();
        $sql = "
            SELECT e.*, op.organization as organizer_name, u.name as admin_approver_name
            FROM events e
            LEFT JOIN organizer_profiles op ON e.organizer_id = op.profile_id
            LEFT JOIN users u ON e.approved_by = u.user_id
        ";
        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " WHERE e.status = :status";
        }
        $sql .= " ORDER BY e.created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($statusFilter && $statusFilter !== 'all') {
            $stmt->execute([':status' => $statusFilter]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Administrator approves event and publishes it
 * FR-EVT-007, FR-EVT-008, UC-03
 * 
 * @param int $eventId
 * @param int $adminUserId
 * @return bool
 */
function admin_approve_event(int $eventId, int $adminUserId): bool
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE events SET
                status = 'published',
                approved_by = :admin_id,
                approved_at = NOW(),
                rejection_reason = NULL
            WHERE event_id = :event_id
        ");
        $stmt->execute([
            ':admin_id' => $adminUserId,
            ':event_id' => $eventId
        ]);
        $ok = $stmt->rowCount() > 0;

        if ($ok) {
            log_audit_action($adminUserId, 'event.approved', 'event', $eventId);
        }

        // Dispatch event approval notification (FR-MAIL-004)
        if ($ok && file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            try {
                send_event_approval_email($eventId, 'published');
            } catch (\Throwable $me) {
                error_log('Event approval email error: ' . $me->getMessage());
            }
        }

        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Administrator rejects event with reason
 * FR-EVT-007, FR-EVT-008, UC-03
 * 
 * @param int $eventId
 * @param int $adminUserId
 * @param string $rejectionReason
 * @return bool
 */
function admin_reject_event(int $eventId, int $adminUserId, string $rejectionReason): bool
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE events SET
                status = 'draft',
                approved_by = :admin_id,
                rejection_reason = :reason,
                approved_at = NOW()
            WHERE event_id = :event_id
        ");
        $stmt->execute([
            ':admin_id' => $adminUserId,
            ':reason'   => sanitize_input($rejectionReason),
            ':event_id' => $eventId
        ]);
        $ok = $stmt->rowCount() > 0;

        if ($ok) {
            log_audit_action($adminUserId, 'event.rejected', 'event', $eventId, ['reason' => $rejectionReason]);
        }

        if ($ok) {
            log_audit_action($adminUserId, 'event.approved', 'event', $eventId);
        }

        // Dispatch event rejection notification (FR-MAIL-004)
        if ($ok && file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            try {
                send_event_approval_email($eventId, 'rejected', $rejectionReason);
            } catch (\Throwable $me) {
                error_log('Event rejection email error: ' . $me->getMessage());
            }
        }

        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Administrator updates event status (suspend, cancel, archive)
 * FR-EVT-007
 * 
 * @param int $eventId
 * @param string $status
 * @param int $adminUserId
 * @return bool
 */
function admin_set_event_status(int $eventId, string $status, int $adminUserId): bool
{
    $validStatuses = ['published', 'suspended', 'cancelled', 'archived', 'draft'];
    if (!in_array($status, $validStatuses, true)) {
        return false;
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE events SET
                status = :status,
                updated_at = NOW()
            WHERE event_id = :event_id
        ");
        $stmt->execute([
            ':status'   => $status,
            ':event_id' => $eventId
        ]);
        $ok = $stmt->rowCount() > 0;
        if ($ok) {
            log_audit_action($adminUserId, 'event.status_changed', 'event', $eventId, ['status' => $status]);
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Retrieve all organizer profiles for administrator review
 * FR-ORG-001, FR-ORG-002
 * 
 * @param string|null $statusFilter
 * @return array
 */
function get_all_organizers(?string $statusFilter = null): array
{
    try {
        $pdo = getDBConnection();
        $sql = "
            SELECT op.*, u.name as user_name, u.email as user_email, u.is_active,
                   admin_u.name as approver_name
            FROM organizer_profiles op
            JOIN users u ON op.user_id = u.user_id
            LEFT JOIN users admin_u ON op.approved_by = admin_u.user_id
        ";
        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " WHERE op.approval_status = :status";
        }
        $sql .= " ORDER BY op.created_at DESC";

        $stmt = $pdo->prepare($sql);
        if ($statusFilter && $statusFilter !== 'all') {
            $stmt->execute([':status' => $statusFilter]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Administrator approves organizer account
 * FR-AUTH-004, FR-ORG-002
 * 
 * @param int $profileId
 * @param int $adminUserId
 * @return bool
 */
function admin_approve_organizer(int $profileId, int $adminUserId): bool
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE organizer_profiles SET
                approval_status = 'approved',
                approved_by = :admin_id,
                decided_at = NOW(),
                rejection_reason = NULL
            WHERE profile_id = :id
        ");
        $stmt->execute([
            ':admin_id' => $adminUserId,
            ':id'       => $profileId
        ]);
        $ok = $stmt->rowCount() > 0;

        if ($ok) {
            log_audit_action($adminUserId, 'organizer.approved', 'organizer_profile', $profileId);
        }

        if ($ok) {
            log_audit_action($adminUserId, 'event.approved', 'event', $eventId);
        }

        // Dispatch organizer approval notification (FR-MAIL-004)
        if ($ok && file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            try {
                $uidStmt = $pdo->prepare("SELECT user_id FROM organizer_profiles WHERE profile_id = :id");
                $uidStmt->execute([':id' => $profileId]);
                $uid = (int)$uidStmt->fetchColumn();
                if ($uid > 0) {
                    send_organizer_approval_email($uid, 'approved');
                }
            } catch (\Throwable $me) {
                error_log('Organizer approval email error: ' . $me->getMessage());
            }
        }

        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Administrator rejects organizer account
 * FR-ORG-002
 * 
 * @param int $profileId
 * @param int $adminUserId
 * @param string $reason
 * @return bool
 */
function admin_reject_organizer(int $profileId, int $adminUserId, string $reason): bool
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE organizer_profiles SET
                approval_status = 'rejected',
                approved_by = :admin_id,
                rejection_reason = :reason,
                decided_at = NOW()
            WHERE profile_id = :id
        ");
        $stmt->execute([
            ':admin_id' => $adminUserId,
            ':reason'   => sanitize_input($reason),
            ':id'       => $profileId
        ]);
        $ok = $stmt->rowCount() > 0;

        if ($ok) {
            log_audit_action($adminUserId, 'organizer.rejected', 'organizer_profile', $profileId, ['reason' => $reason]);
        }

        if ($ok) {
            log_audit_action($adminUserId, 'event.approved', 'event', $eventId);
        }

        // Dispatch organizer rejection notification (FR-MAIL-004)
        if ($ok && file_exists(__DIR__ . '/mailer.php')) {
            require_once __DIR__ . '/mailer.php';
            try {
                $uidStmt = $pdo->prepare("SELECT user_id FROM organizer_profiles WHERE profile_id = :id");
                $uidStmt->execute([':id' => $profileId]);
                $uid = (int)$uidStmt->fetchColumn();
                if ($uid > 0) {
                    send_organizer_approval_email($uid, 'rejected', $reason);
                }
            } catch (\Throwable $me) {
                error_log('Organizer rejection email error: ' . $me->getMessage());
            }
        }

        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Administrator suspends organizer account (FR-ORG-006)
 * 
 * @param int $profileId
 * @return bool
 */
function admin_suspend_organizer(int $profileId): bool
{
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE organizer_profiles SET
                approval_status = 'suspended'
            WHERE profile_id = :id
        ");
        $stmt->execute([':id' => $profileId]);
        $ok = $stmt->rowCount() > 0;
        if ($ok) {
            log_audit_action(null, 'organizer.suspended', 'organizer_profile', $profileId);
        }
        return $ok;
    } catch (PDOException $e) {
        return false;
    }
}
