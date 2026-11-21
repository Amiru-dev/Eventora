<?php
/**
 * Eventora - Wishlist API Endpoint
 * FR-WISH-001, FR-WISH-002
 *
 * GET  → get student's saved wishlist events
 * POST {action: 'toggle', event_id}   → add or remove from wishlist
 * POST {action: 'remove', event_id}   → explicitly remove
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wishlist.php';

if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to access your wishlist.']);
    exit;
}

$currentUser = current_user();
$userId = (int)$currentUser['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================
// GET: Fetch wishlist
// ============================================================
if ($method === 'GET') {
    $wishlist = get_student_wishlist($userId);
    echo json_encode([
        'success'  => true,
        'wishlist' => $wishlist,
        'count'    => count($wishlist)
    ]);
    exit;
}

// ============================================================
// POST: Toggle or remove
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = $_POST;

    $action  = trim($input['action'] ?? 'toggle');
    $eventId = (int)($input['event_id'] ?? 0);

    if ($eventId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing event_id.']);
        exit;
    }

    if ($action === 'remove') {
        $ok = remove_from_wishlist($userId, $eventId);
        echo json_encode([
            'success' => $ok,
            'action'  => 'removed',
            'event_id'=> $eventId
        ]);
    } else {
        // toggle (default)
        $result = toggle_wishlist($userId, $eventId);
        echo json_encode(array_merge($result, ['event_id' => $eventId]));
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
exit;
