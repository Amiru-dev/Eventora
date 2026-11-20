<?php
/**
 * Eventora - Common Helper Functions
 * CSRF protection, input sanitization, flash messaging, currency & datetime formatting
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}

/**
 * Start PHP session safely with hardened cookie parameters
 */
function start_session_safe(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600,
            'path'     => '/',
            'domain'   => $cookieParams['domain'],
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Generate or retrieve CSRF token
 * FR-VAL-003, NFR-SEC-005
 * 
 * @return string
 */
function csrf_token(): string
{
    start_session_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF token input field for HTML forms
 * 
 * @return string
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify submitted CSRF token against session
 * FR-VAL-003, NFR-SEC-005
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token = null): bool
{
    start_session_safe();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize plain text string
 * 
 * @param mixed $input
 * @return string
 */
function sanitize_input($input): string
{
    if (is_null($input)) {
        return '';
    }
    return trim(strip_tags((string)$input));
}

/**
 * Escape output for safe HTML display (XSS prevention)
 * NFR-SEC-003
 * 
 * @param mixed $str
 * @return string
 */
function e($str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Add a flash notification message to session
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function set_flash_message(string $type, string $message): void
{
    start_session_safe();
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all pending flash messages
 * 
 * @return array
 */
function get_flash_messages(): array
{
    start_session_safe();
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render flash messages as accessible Bootstrap alerts
 * 
 * @return string HTML
 */
function render_flash_messages(): string
{
    $messages = get_flash_messages();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="container mt-3" aria-live="polite">';
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        $html .= "<div class=\"alert alert-{$type} alert-dismissible fade show shadow-sm\" role=\"alert\">";
        $html .= "<span>{$text}</span>";
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Format currency with 2 decimal places and LKR prefix
 * BR-09, NFR-LOC-001
 * 
 * @param float|int|string $amount
 * @return string
 */
function format_currency($amount): string
{
    $val = (float)$amount;
    return 'LKR ' . number_format($val, 2, '.', ',');
}

/**
 * Format datetime string in Asia/Colombo time
 * BR-10, NFR-LOC-001
 * 
 * @param string $datetime
 * @param string $format
 * @return string
 */
function format_datetime(string $datetime, string $format = 'M d, Y · h:i A'): string
{
    try {
        $dt = new DateTime($datetime, new DateTimeZone(APP_TIMEZONE));
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Safe HTTP header redirect
 * 
 * @param string $url
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}
