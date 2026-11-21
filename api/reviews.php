<?php
/**
 * Eventora - Event Reviews & Ratings API Endpoint
 * FR-REV-001 to FR-REV-005, BR-17
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/reviews.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentUser = is_logged_in() ? current_user() : null;
$userId = $currentUser ? (int)$currentUser['user_id'] : null;

// ============================================================
// 1. GET: Fetch reviews & rating summary for an event
// ============================================================
if ($method === 'GET') {
    $eventId = (int)($_GET['event_id'] ?? 0);

    if ($eventId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing or invalid event_id.']);
        exit;
    }

    $isAdmin = $currentUser && ($currentUser['role'] === 'admin');
    $visibleOnly = !$isAdmin;

    $reviews = get_event_reviews($eventId, $visibleOnly);
    $summary = get_event_rating_summary($eventId);
    $userEligibility = $userId ? can_user_review_event($userId, $eventId) : null;

    echo json_encode([
        'success'     => true,
        'event_id'    => $eventId,
        'summary'     => $summary,
        'reviews'     => $reviews,
        'eligibility' => $userEligibility
    ]);
    exit;
}

// ============================================================
// 2. POST: Submit review or Moderate visibility
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = trim($input['action'] ?? 'submit');

    // Action A: Submit Review (FR-REV-001)
    if ($action === 'submit') {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in to submit an event review.']);
            exit;
        }

        $eventId = (int)($input['event_id'] ?? 0);
        $rating  = (int)($input['rating'] ?? 0);
        $comment = isset($input['comment']) ? trim($input['comment']) : null;

        if ($eventId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid event_id.']);
            exit;
        }

        $result = submit_event_review($userId, $eventId, $rating, $comment);
        if ($result['success']) {
            $summary = get_event_rating_summary($eventId);
            echo json_encode([
                'success'   => true,
                'message'   => 'Thank you! Your rating and review have been submitted.',
                'review_id' => $result['review_id'],
                'summary'   => $summary
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => $result['error']
            ]);
        }
        exit;
    }

    // Action B: Admin Toggle Visibility (BR-17)
    if ($action === 'toggle_visibility') {
        if (!$currentUser || $currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Administrator access required.']);
            exit;
        }

        $reviewId = (int)($input['review_id'] ?? 0);
        if ($reviewId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid review_id.']);
            exit;
        }

        $ok = toggle_review_visibility($reviewId);
        echo json_encode(['success' => $ok, 'review_id' => $reviewId]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
exit;
