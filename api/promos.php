<?php
/**
 * Eventora - Promo Codes API Endpoint
 * FR-PROMO-001 to FR-PROMO-006, BR-11, BR-12
 * 
 * Supports client-side validation and admin AJAX operations.
 */

header('Content-Type: application/json; charset=utf-8');

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/promos.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$currentUser = is_logged_in() ? current_user() : null;
$userId = $currentUser ? (int)$currentUser['user_id'] : null;

// ============================================================
// 1. GET: Real-time promo validation check
// ============================================================
if ($method === 'GET') {
    $code     = trim($_GET['code'] ?? '');
    $subtotal = (float)($_GET['subtotal'] ?? 0);

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'valid' => false, 'message' => 'Please provide a promo code.']);
        exit;
    }

    $result = validate_promo_code($code, $subtotal, $userId);
    echo json_encode(array_merge(['success' => true], $result));
    exit;
}

// ============================================================
// 2. POST: Validation or Admin CRUD actions
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $action = trim($input['action'] ?? 'validate');

    if ($action === 'validate') {
        $code     = trim($input['code'] ?? '');
        $subtotal = (float)($input['subtotal'] ?? 0);

        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'valid' => false, 'message' => 'Please provide a promo code.']);
            exit;
        }

        $result = validate_promo_code($code, $subtotal, $userId);
        echo json_encode(array_merge(['success' => true], $result));
        exit;
    }

    // Admin-only actions
    if (!$currentUser || $currentUser['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Admin access required.']);
        exit;
    }

    $promoId = (int)($input['promo_id'] ?? 0);

    if ($action === 'toggle' && $promoId > 0) {
        $ok = toggle_promo_status($promoId);
        echo json_encode(['success' => $ok, 'promo_id' => $promoId]);
        exit;
    }

    if ($action === 'delete' && $promoId > 0) {
        $del = delete_promo_code($promoId);
        echo json_encode($del);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action or missing parameters.']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
exit;
