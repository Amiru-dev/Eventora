<?php
/**
 * Eventora - QR Code SVG API Endpoint
 * Generates crisp vector SVG QR codes for ticket validation.
 * FR-TKT-002, NFR-SEC-007
 */

if (!defined('APP_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../assets/vendor/phpqrcode/qrlib.php';

// Accept token, data, or t parameter
$data = trim($_GET['token'] ?? $_GET['data'] ?? $_GET['t'] ?? '');

if (empty($data)) {
    header('Content-Type: image/svg+xml; charset=utf-8');
    http_response_code(400);
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160"><rect width="100%" height="100%" fill="#f8f9fa"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#6c757d">No Data</text></svg>';
    exit;
}

// Limit payload size for safety
if (strlen($data) > 512) {
    $data = substr($data, 0, 512);
}

// Set caching headers for browser performance
header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: public, max-age=86400');

// Generate SVG QR code directly
QRcode::svg($data, false, QR_ECLEVEL_M, 4, 2);
exit;
