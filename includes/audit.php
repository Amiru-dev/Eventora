<?php
/**
 * Eventora - System Audit Logging Engine
 * FR-AUD-001, NFR-AUD-001, NFR-SEC-001, NFR-SEC-004, NFR-SEC-009
 * 
 * Records immutable audit logs for administrative actions, approval decisions,
 * payment state changes, venue check-ins, and security events.
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/reports.php';

/**
 * Retrieve client IP address reliably
 * 
 * @return string
 */
function get_client_ip(): string
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // May contain comma-separated IPs; pick the first
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // Validate IP format; fallback if invalid
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '127.0.0.1';
    }

    return $ip;
}

/**
 * Record an audit log entry
 * FR-AUD-001, NFR-AUD-001
 * 
 * @param int|null $userId ID of user performing action (null for system/unauthenticated)
 * @param string $action Standard action descriptor (e.g. 'auth.login_success', 'event.approved')
 * @param string|null $entityType Type of affected entity (e.g. 'event', 'booking', 'user')
 * @param int|null $entityId ID of affected entity
 * @param mixed|null $details Contextual details (array will be JSON-encoded)
 * @return bool
 */
function log_audit_action(
    ?int $userId,
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    $details = null
): bool {
    // If user ID is null, attempt to resolve from current authenticated session
    if ($userId === null && function_exists('current_user')) {
        $user = current_user();
        if ($user) {
            $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
            if ($userId <= 0) {
                $userId = null;
            }
        }
    }

    $ipAddress = get_client_ip();

    // Format details to JSON or string
    $detailsString = null;
    if ($details !== null) {
        if (is_array($details) || is_object($details)) {
            $detailsString = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $detailsString = (string)$details;
        }
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        // Fallback: log to PHP error log so records are never lost
        error_log(sprintf(
            "[AUDIT_LOG_FALLBACK] User: %s | Action: %s | Entity: %s#%s | IP: %s | Details: %s",
            $userId ?? 'SYSTEM',
            $action,
            $entityType ?? 'N/A',
            $entityId ?? 'N/A',
            $ipAddress,
            $detailsString ?? 'None'
        ));
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, NOW())
        ");
        return $stmt->execute([
            ':user_id'     => $userId,
            ':action'      => $action,
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
            ':details'     => $detailsString,
            ':ip_address'  => $ipAddress
        ]);
    } catch (\PDOException $e) {
        error_log("Failed to insert audit log: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch filtered audit logs with actor information
 * FR-AUD-001
 * 
 * @param array $filters Supported: 'action', 'category', 'user_id', 'search', 'date_from', 'date_to'
 * @param int $limit
 * @param int $offset
 * @return array
 */
function get_audit_logs(array $filters = [], int $limit = 50, int $offset = 0): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return get_mock_audit_logs($filters, $limit, $offset);
    }

    try {
        $whereClauses = [];
        $params = [];

        // Filter by category prefix (e.g. 'auth', 'event', 'booking', 'payment', 'checkin', 'promo')
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $whereClauses[] = "a.action LIKE :category";
            $params[':category'] = $filters['category'] . '.%';
        }

        // Filter by exact action
        if (!empty($filters['action'])) {
            $whereClauses[] = "a.action = :action";
            $params[':action'] = $filters['action'];
        }

        // Filter by actor user_id
        if (!empty($filters['user_id'])) {
            $whereClauses[] = "a.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }

        // Filter by entity type
        if (!empty($filters['entity_type'])) {
            $whereClauses[] = "a.entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "a.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "a.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        // Keyword search in action, details, user name, or email
        if (!empty($filters['search'])) {
            $whereClauses[] = "(a.action LIKE :search OR a.details LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR a.ip_address LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "
            SELECT 
                a.log_id, a.user_id, a.action, a.entity_type, a.entity_id,
                a.details, a.ip_address, a.created_at,
                u.name AS user_name, u.email AS user_email, u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
        ";

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " ORDER BY a.created_at DESC, a.log_id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();

    } catch (\PDOException $e) {
        error_log("get_audit_logs error: " . $e->getMessage());
        return get_mock_audit_logs($filters, $limit, $offset);
    }
}

/**
 * Count total audit logs matching criteria for pagination
 * 
 * @param array $filters
 * @return int
 */
function get_audit_log_count(array $filters = []): int
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return count(get_mock_audit_logs($filters, 1000, 0));
    }

    try {
        $whereClauses = [];
        $params = [];

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $whereClauses[] = "a.action LIKE :category";
            $params[':category'] = $filters['category'] . '.%';
        }
        if (!empty($filters['action'])) {
            $whereClauses[] = "a.action = :action";
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $whereClauses[] = "a.user_id = :user_id";
            $params[':user_id'] = (int)$filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $whereClauses[] = "a.entity_type = :entity_type";
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "a.created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "a.created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $whereClauses[] = "(a.action LIKE :search OR a.details LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR a.ip_address LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "
            SELECT COUNT(*) 
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
        ";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();

    } catch (\PDOException $e) {
        error_log("get_audit_log_count error: " . $e->getMessage());
        return 10;
    }
}

/**
 * Standard Action Categories for Filtering
 * 
 * @return array
 */
function get_audit_action_categories(): array
{
    return [
        'all'      => 'All Activity Categories',
        'auth'     => 'Authentication & Access',
        'event'    => 'Events & Approvals',
        'booking'  => 'Bookings & Holds',
        'payment'  => 'Payments & Callbacks',
        'checkin'  => 'Venue Check-in',
        'promo'    => 'Promo Codes',
        'security' => 'Security & Throttling'
    ];
}

/**
 * Export filtered audit logs to CSV
 * FR-REP-005, NFR-SEC-009
 * 
 * @param array $filters
 * @return void
 */
function export_audit_logs_csv(array $filters = []): void
{
    $logs = get_audit_logs($filters, 5000, 0);

    $headers = [
        'Log ID',
        'Timestamp',
        'Actor Name',
        'Actor Email',
        'Actor Role',
        'Action Code',
        'Entity Type',
        'Entity ID',
        'IP Address',
        'Details'
    ];

    $rows = [];
    foreach ($logs as $log) {
        $rows[] = [
            'log_id'      => $log['log_id'],
            'created_at'  => $log['created_at'],
            'actor_name'  => $log['user_name'] ?? 'System / Anonymous',
            'actor_email' => $log['user_email'] ?? 'N/A',
            'actor_role'  => $log['user_role'] ?? 'system',
            'action'      => $log['action'],
            'entity_type' => $log['entity_type'] ?? 'N/A',
            'entity_id'   => $log['entity_id'] ?? 'N/A',
            'ip_address'  => $log['ip_address'] ?? '127.0.0.1',
            'details'     => $log['details'] ?? ''
        ];
    }

    $filename = 'eventora_audit_log_' . date('Ymd_His') . '.csv';
    output_secure_csv($headers, $rows, $filename);
}

/**
 * Comprehensive mock audit logs for offline testing and demos
 * 
 * @param array $filters
 * @param int $limit
 * @param int $offset
 * @return array
 */
function get_mock_audit_logs(array $filters = [], int $limit = 50, int $offset = 0): array
{
    $mockLogs = [
        [
            'log_id'      => 101,
            'user_id'     => 1,
            'user_name'   => 'Platform Admin',
            'user_email'  => 'admin@eventora.com',
            'user_role'   => 'admin',
            'action'      => 'event.approved',
            'entity_type' => 'event',
            'entity_id'   => 1,
            'details'     => '{"title":"Introduction to Web Development","approved_by":"admin"}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ],
        [
            'log_id'      => 100,
            'user_id'     => 2,
            'user_name'   => 'Club Organizer',
            'user_email'  => 'organizer@eventora.com',
            'user_role'   => 'organizer',
            'action'      => 'checkin.successful',
            'entity_type' => 'ticket',
            'entity_id'   => 42,
            'details'     => '{"event_id":1,"token":"9f2a...8b1e","scanner_mode":"webcam"}',
            'ip_address'  => '192.168.1.105',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-45 minutes'))
        ],
        [
            'log_id'      => 99,
            'user_id'     => 3,
            'user_name'   => 'Test Student',
            'user_email'  => 'student@eventora.com',
            'user_role'   => 'student',
            'action'      => 'payment.verified',
            'entity_type' => 'payment',
            'entity_id'   => 12,
            'details'     => '{"order_ref":"ORD-2026-98124","amount":500,"status":"success","md5_verified":true}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'log_id'      => 98,
            'user_id'     => 3,
            'user_name'   => 'Test Student',
            'user_email'  => 'student@eventora.com',
            'user_role'   => 'student',
            'action'      => 'booking.created',
            'entity_type' => 'booking',
            'entity_id'   => 8,
            'details'     => '{"booking_ref":"EVT-2026-9481","seats":1,"total":500,"status":"pending_payment"}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-2 hours 5 minutes'))
        ],
        [
            'log_id'      => 97,
            'user_id'     => 1,
            'user_name'   => 'Platform Admin',
            'user_email'  => 'admin@eventora.com',
            'user_role'   => 'admin',
            'action'      => 'promo.created',
            'entity_type' => 'promo_code',
            'entity_id'   => 4,
            'details'     => '{"code":"NIIBS2025","discount_type":"percentage","discount_val":15,"usage_limit":100}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'log_id'      => 96,
            'user_id'     => 1,
            'user_name'   => 'Platform Admin',
            'user_email'  => 'admin@eventora.com',
            'user_role'   => 'admin',
            'action'      => 'organizer.approved',
            'entity_type' => 'organizer_profile',
            'entity_id'   => 1,
            'details'     => '{"organization":"NIIBS Tech Club","status":"approved"}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ],
        [
            'log_id'      => 95,
            'user_id'     => null,
            'user_name'   => null,
            'user_email'  => null,
            'user_role'   => null,
            'action'      => 'auth.login_failure',
            'entity_type' => 'user',
            'entity_id'   => null,
            'details'     => '{"email":"intruder@test.com","reason":"invalid_password"}',
            'ip_address'  => '203.94.75.12',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-8 hours'))
        ],
        [
            'log_id'      => 94,
            'user_id'     => 3,
            'user_name'   => 'Test Student',
            'user_email'  => 'student@eventora.com',
            'user_role'   => 'student',
            'action'      => 'auth.login_success',
            'entity_type' => 'user',
            'entity_id'   => 3,
            'details'     => '{"email":"student@eventora.com","role":"student"}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-8 hours 2 minutes'))
        ],
        [
            'log_id'      => 93,
            'user_id'     => null,
            'user_name'   => null,
            'user_email'  => null,
            'user_role'   => null,
            'action'      => 'booking.holds_expired',
            'entity_type' => 'system',
            'entity_id'   => null,
            'details'     => '{"expired_count":2,"cron_run":true}',
            'ip_address'  => '127.0.0.1',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-12 hours'))
        ],
        [
            'log_id'      => 92,
            'user_id'     => 2,
            'user_name'   => 'Club Organizer',
            'user_email'  => 'organizer@eventora.com',
            'user_role'   => 'organizer',
            'action'      => 'checkin.duplicate_rejected',
            'entity_type' => 'ticket',
            'entity_id'   => 42,
            'details'     => '{"warning":"TICKET_ALREADY_USED","checked_in_previously":true}',
            'ip_address'  => '192.168.1.105',
            'created_at'  => date('Y-m-d H:i:s', strtotime('-14 hours'))
        ]
    ];

    // Filter by category
    if (!empty($filters['category']) && $filters['category'] !== 'all') {
        $prefix = $filters['category'] . '.';
        $mockLogs = array_filter($mockLogs, function($l) use ($prefix) {
            return strpos($l['action'], $prefix) === 0;
        });
    }

    // Filter by search
    if (!empty($filters['search'])) {
        $s = strtolower($filters['search']);
        $mockLogs = array_filter($mockLogs, function($l) use ($s) {
            return str_contains(strtolower($l['action']), $s) ||
                   str_contains(strtolower($l['details'] ?? ''), $s) ||
                   str_contains(strtolower($l['user_name'] ?? ''), $s) ||
                   str_contains(strtolower($l['user_email'] ?? ''), $s);
        });
    }

    return array_slice(array_values($mockLogs), $offset, $limit);
}
