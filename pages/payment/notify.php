<?php
/**
 * Eventora - PayHere Server Notification Webhook Endpoint
 * 
 * Implements:
 * - FR-PAY-004: Verify PayHere signature using merchant secret
 * - FR-PAY-005: Update status strictly from verified server notification
 * - FR-PAY-006: Idempotent processing of duplicate callbacks
 * - FR-PAY-007: Log transaction details and timestamps
 * - BR-07, BR-19
 * - NFR-SEC-006: Cryptographic verification
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/payments.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

// Process PayHere notification payload
$result = process_payhere_notification($_POST);

http_response_code($result['http_code']);
header('Content-Type: text/plain; charset=utf-8');
echo $result['message'];
exit;
