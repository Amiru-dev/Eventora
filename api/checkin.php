<?php
/**
 * Eventora - Venue Check-in API Endpoint
 * FR-TKT-004, FR-TKT-005, FR-TKT-006, FR-TKT-007, BR-16, UC-10
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/events.php';
require_once __DIR__ . '/../includes/tickets.php';

// Authentication and Authorization: Organizer or Admin only
if (!is_logged_in() || !in_array(current_user()['role'] ?? '', ['organizer', 'admin'], true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'result'  => 'FORBIDDEN',
        'message' => 'Unauthorized: Staff check-in requires organizer or admin privileges.'
    ]);
    exit;
}

$currentUser = current_user();
$staffUserId = (int)$currentUser['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================
// 1. GET: Fetch attendees roster & live statistics
// ============================================================
if ($method === 'GET') {
    $eventId = (int)($_GET['event_id'] ?? 0);
    $search  = trim($_GET['search'] ?? '');

    if ($eventId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid event_id parameter.']);
        exit;
    }

    if ($currentUser['role'] === 'organizer' && !can_manage_event($eventId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to manage this event.']);
        exit;
    }

    $stats     = get_event_checkin_stats($eventId);
    $attendees = get_event_attendees($eventId, $search);

    echo json_encode([
        'success'   => true,
        'event_id'  => $eventId,
        'stats'     => $stats,
        'attendees' => $attendees
    ]);
    exit;
}

// ============================================================
// 2. POST: Validate ticket token & process check-in
// ============================================================
if ($method === 'POST') {
    // Read JSON body or POST form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $token   = trim($input['token'] ?? '');
    $eventId = isset($input['event_id']) && (int)$input['event_id'] > 0 ? (int)$input['event_id'] : null;

    if (empty($token)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'result'  => 'INVALID',
            'message' => 'Ticket token or reference is required.'
        ]);
        exit;
    }

    // If eventId provided, verify organizer permission
    if ($eventId !== null && $currentUser['role'] === 'organizer' && !can_manage_event($eventId)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'result'  => 'FORBIDDEN',
            'message' => 'You do not have permission to check in attendees for this event.'
        ]);
        exit;
    }

    // Process check-in (BR-16)
    $response = verify_and_checkin_ticket($token, $staffUserId, $eventId);

    // Refresh stats if eventId is known
    $targetEventId = $eventId ?: (isset($response['ticket']['event_id']) ? (int)$response['ticket']['event_id'] : 0);
    $stats = $targetEventId > 0 ? get_event_checkin_stats($targetEventId) : null;

    echo json_encode([
        'success'      => true,
        'result'       => $response['result'],
        'message'      => $response['message'],
        'ticket'       => $response['ticket'] ?? null,
        'attendee'     => $response['attendee'] ?? null,
        'checkin'      => $response['checkin'] ?? null,
        'checkin_time' => $response['checkin_time'] ?? null,
        'stats'        => $stats
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
exit;
