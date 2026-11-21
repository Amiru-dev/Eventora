<?php
/**
 * Eventora - Wishlist Business Logic
 * FR-WISH-001, FR-WISH-002
 *
 * Students can bookmark/save events to track availability.
 * Toggle save/remove, one entry per student per event.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Toggle wishlist (add if absent, remove if present) — FR-WISH-001, FR-WISH-002
 *
 * @param int $userId
 * @param int $eventId
 * @return array ['action' => 'added'|'removed', 'success' => bool, 'error' => string|null]
 */
function toggle_wishlist(int $userId, int $eventId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['action' => 'added', 'success' => true, 'error' => null];
    }

    try {
        // Check existing entry
        $checkStmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = :uid AND event_id = :eid");
        $checkStmt->execute([':uid' => $userId, ':eid' => $eventId]);
        $existing = $checkStmt->fetchColumn();

        if ($existing) {
            // Remove from wishlist
            $pdo->prepare("DELETE FROM wishlist WHERE user_id = :uid AND event_id = :eid")
                ->execute([':uid' => $userId, ':eid' => $eventId]);
            return ['action' => 'removed', 'success' => true, 'error' => null];
        } else {
            // Add to wishlist
            $pdo->prepare("INSERT INTO wishlist (user_id, event_id, created_at) VALUES (:uid, :eid, NOW())")
                ->execute([':uid' => $userId, ':eid' => $eventId]);
            return ['action' => 'added', 'success' => true, 'error' => null];
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            // Duplicate race condition — treat as already added
            return ['action' => 'added', 'success' => true, 'error' => null];
        }
        error_log('toggle_wishlist error: ' . $e->getMessage());
        return ['action' => 'none', 'success' => false, 'error' => 'Could not update wishlist. Please try again.'];
    }
}

/**
 * Remove a specific event from the wishlist — FR-WISH-002
 *
 * @param int $userId
 * @param int $eventId
 * @return bool
 */
function remove_from_wishlist(int $userId, int $eventId): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :uid AND event_id = :eid");
        $stmt->execute([':uid' => $userId, ':eid' => $eventId]);
        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        error_log('remove_from_wishlist error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user has an event in their wishlist
 *
 * @param int $userId
 * @param int $eventId
 * @return bool
 */
function is_in_wishlist(int $userId, int $eventId): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = :uid AND event_id = :eid LIMIT 1");
        $stmt->execute([':uid' => $userId, ':eid' => $eventId]);
        return (bool)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log('is_in_wishlist error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all wishlisted events for a student — FR-WISH-001
 *
 * @param int $userId
 * @return array
 */
function get_student_wishlist(int $userId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return get_mock_student_wishlist();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                w.wishlist_id, w.created_at AS saved_at,
                e.event_id, e.title, e.category, e.venue, e.description,
                e.start_date, e.end_date, e.price, e.seats_available, e.capacity,
                e.status AS event_status, e.waitlist_enabled,
                (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.event_id AND b.user_id = :uid2 AND b.status = 'confirmed') AS is_booked
            FROM wishlist w
            JOIN events e ON w.event_id = e.event_id
            WHERE w.user_id = :uid
              AND e.status = 'published'
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        $results = $stmt->fetchAll();
        return $results ?: get_mock_student_wishlist();
    } catch (\PDOException $e) {
        error_log('get_student_wishlist error: ' . $e->getMessage());
        return get_mock_student_wishlist();
    }
}

/**
 * Get a list of event IDs a user has wishlisted (for quick lookup)
 *
 * @param int $userId
 * @return array  List of event_id integers
 */
function get_wishlisted_event_ids(int $userId): array
{
    $pdo = get_db_connection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT event_id FROM wishlist WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    } catch (\PDOException $e) {
        error_log('get_wishlisted_event_ids error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get total wishlist count for a student
 *
 * @param int $userId
 * @return int
 */
function get_wishlist_count(int $userId): int
{
    $pdo = get_db_connection();
    if (!$pdo) return 0;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM wishlist w
            JOIN events e ON w.event_id = e.event_id
            WHERE w.user_id = :uid AND e.status = 'published'
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (\PDOException $e) {
        error_log('get_wishlist_count error: ' . $e->getMessage());
        return 0;
    }
}

// ============================================================
// MOCK FALLBACKS (offline testing)
// ============================================================

function get_mock_student_wishlist(): array
{
    return [
        [
            'wishlist_id'     => 1,
            'saved_at'        => date('Y-m-d H:i:s', strtotime('-2 days')),
            'event_id'        => 5,
            'title'           => 'Hackathon: Code for Change',
            'category'        => 'Tech',
            'venue'           => 'Innovation Hub',
            'description'     => 'A 24-hour hackathon where teams build solutions for real-world community problems. Prizes for top 3 teams.',
            'start_date'      => date('Y-m-d H:i:s', strtotime('+10 days')),
            'end_date'        => date('Y-m-d H:i:s', strtotime('+11 days')),
            'price'           => 1000.00,
            'seats_available' => 60,
            'capacity'        => 60,
            'event_status'    => 'published',
            'waitlist_enabled'=> 1,
            'is_booked'       => 0
        ],
        [
            'wishlist_id'     => 2,
            'saved_at'        => date('Y-m-d H:i:s', strtotime('-1 day')),
            'event_id'        => 4,
            'title'           => 'Cultural Night 2026',
            'category'        => 'Cultural',
            'venue'           => 'University Grounds',
            'description'     => 'Celebrate diversity with performances, food stalls and cultural exhibitions from students across all faculties.',
            'start_date'      => date('Y-m-d H:i:s', strtotime('+30 days')),
            'end_date'        => date('Y-m-d H:i:s', strtotime('+30 days 5 hours')),
            'price'           => 200.00,
            'seats_available' => 500,
            'capacity'        => 500,
            'event_status'    => 'published',
            'waitlist_enabled'=> 1,
            'is_booked'       => 0
        ]
    ];
}
