<?php
/**
 * Eventora - Waitlist API Endpoint
 * FR-WAIT-001 to FR-WAIT-005, BR-13
 *
 * GET  ?event_id=N             → list of waitlist entries for event (organizer/admin only)
 * POST {action: 'join', event_id, seats}         → join waitlist
 * POST {action: 'leave', event_id}               → leave waitlist
 * POST {action: 'accept', entry_id}              → accept seat offer
 * POST {action: 'decline', entry_id}             → decline seat offer
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/waitlist.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to access the waitlist.']);
    exit;
}

$currentUser = current_user();
$userId      = (int)$currentUser['user_id'];
$role        = $currentUser['role'] ?? '';
$method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================
// GET: Fetch waitlist for a student or event
// ============================================================
if ($method === 'GET') {
    $eventId = (int)($_GET['event_id'] ?? 0);

    if ($eventId > 0 && in_array($role, ['organizer', 'admin'], true)) {
        // Organizer/Admin: full event waitlist
        $entries = get_event_waitlist($eventId);
        echo json_encode(['success' => true, 'waitlist' => $entries, 'count' => count($entries)]);
    } else {
        // Student: own waitlist entries
        $entries = get_student_waitlist($userId);
        echo json_encode(['success' => true, 'waitlist' => $entries, 'count' => count($entries)]);
    }
    exit;
}

// ============================================================
// POST: Waitlist actions
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    $action  = trim($input['action'] ?? '');
    $eventId = (int)($input['event_id'] ?? 0);
    $entryId = (int)($input['entry_id'] ?? 0);

    switch ($action) {
        case 'join':
            if ($role !== 'student') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Only students can join the waitlist.']);
                exit;
            }
            if ($eventId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid event_id.']);
                exit;
            }
            $seats  = max(1, (int)($input['seats'] ?? 1));
            $result = join_waitlist($userId, $eventId, $seats);
            echo json_encode(array_merge($result, ['action' => 'join']));
            break;

        case 'leave':
            if ($eventId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid event_id.']);
                exit;
            }
            $result = leave_waitlist($userId, $eventId);
            echo json_encode(array_merge($result, ['action' => 'leave']));
            break;

        case 'accept':
            if ($entryId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid entry_id.']);
                exit;
            }
            $result = accept_waitlist_offer($userId, $entryId);
            if ($result['success']) {
                // Build booking URL to redirect student
                $result['redirect_url'] = APP_URL . '/pages/event_detail.php?id=' . ($result['event_id'] ?? '');
            }
            echo json_encode(array_merge($result, ['action' => 'accept']));
            break;

        case 'decline':
            if ($entryId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid entry_id.']);
                exit;
            }
            $result = decline_waitlist_offer($userId, $entryId);
            echo json_encode(array_merge($result, ['action' => 'decline']));
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action. Use: join, leave, accept, decline.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
exit;
