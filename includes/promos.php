<?php
/**
 * Eventora - Promo Codes & Discounts Business Logic
 * FR-PROMO-001 to FR-PROMO-006, BR-11, BR-12
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Get all promo codes
 * FR-PROMO-001
 * 
 * @param bool $activeOnly
 * @return array
 */
function get_promo_codes(bool $activeOnly = false): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return get_mock_promo_codes($activeOnly);
    }

    try {
        $sql = "
            SELECT 
                p.*,
                u.name AS created_by_name
            FROM promo_codes p
            LEFT JOIN users u ON p.created_by = u.user_id
        ";
        if ($activeOnly) {
            $sql .= " WHERE p.is_active = 1 AND p.valid_from <= NOW() AND p.valid_until >= NOW()";
        }
        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->query($sql);
        $promos = $stmt->fetchAll();

        return $promos ?: get_mock_promo_codes($activeOnly);
    } catch (\PDOException $e) {
        error_log('get_promo_codes error: ' . $e->getMessage());
        return get_mock_promo_codes($activeOnly);
    }
}

/**
 * Get single promo code by ID
 * 
 * @param int $promoId
 * @return array|null
 */
function get_promo_code_by_id(int $promoId): ?array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        foreach (get_mock_promo_codes() as $p) {
            if ((int)$p['promo_id'] === $promoId) {
                return $p;
            }
        }
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE promo_id = :id LIMIT 1");
        $stmt->execute([':id' => $promoId]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (\PDOException $e) {
        error_log('get_promo_code_by_id error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get single promo code by code string
 * 
 * @param string $code
 * @return array|null
 */
function get_promo_code_by_code(string $code): ?array
{
    $code = strtoupper(trim($code));
    if (empty($code)) {
        return null;
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        foreach (get_mock_promo_codes() as $p) {
            if (strtoupper($p['code']) === $code) {
                return $p;
            }
        }
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (\PDOException $e) {
        error_log('get_promo_code_by_code error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create a new promo code
 * FR-PROMO-001, FR-PROMO-002, FR-PROMO-003, FR-PROMO-004, FR-PROMO-005
 * 
 * @param array $data
 * @param int $adminUserId
 * @return array ['success' => bool, 'promo_id' => int|null, 'error' => string|null]
 */
function create_promo_code(array $data, int $adminUserId): array
{
    $code = strtoupper(trim($data['code'] ?? ''));
    $type = trim($data['discount_type'] ?? 'percentage');
    $val  = (float)($data['discount_value'] ?? 0);
    $minSpend     = isset($data['min_spend']) && $data['min_spend'] !== '' ? (float)$data['min_spend'] : null;
    $maxDiscount  = isset($data['max_discount']) && $data['max_discount'] !== '' ? (float)$data['max_discount'] : null;
    $totalLimit   = isset($data['total_use_limit']) && $data['total_use_limit'] !== '' ? (int)$data['total_use_limit'] : null;
    $perUserLimit = isset($data['per_user_limit']) && (int)$data['per_user_limit'] > 0 ? (int)$data['per_user_limit'] : 1;
    $validFrom    = trim($data['valid_from'] ?? '');
    $validUntil   = trim($data['valid_until'] ?? '');

    // Validation
    if (empty($code) || !preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
        return ['success' => false, 'promo_id' => null, 'error' => 'Promo code must be 3-30 uppercase alphanumeric characters (hyphens and underscores allowed).'];
    }

    if (!in_array($type, ['percentage', 'fixed'], true)) {
        return ['success' => false, 'promo_id' => null, 'error' => 'Discount type must be percentage or fixed.'];
    }

    if ($val <= 0) {
        return ['success' => false, 'promo_id' => null, 'error' => 'Discount value must be greater than zero.'];
    }

    if ($type === 'percentage' && $val > 100) {
        return ['success' => false, 'promo_id' => null, 'error' => 'Percentage discount cannot exceed 100%.'];
    }

    if (empty($validFrom)) {
        $validFrom = date('Y-m-d H:i:s');
    }
    if (empty($validUntil)) {
        $validUntil = date('Y-m-d H:i:s', strtotime('+30 days'));
    }

    if (strtotime($validUntil) <= strtotime($validFrom)) {
        return ['success' => false, 'promo_id' => null, 'error' => 'Expiration date must be later than the start date.'];
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        // Mock fallback success
        return ['success' => true, 'promo_id' => 999, 'error' => null];
    }

    try {
        // Check duplicate code
        $dupStmt = $pdo->prepare("SELECT promo_id FROM promo_codes WHERE code = :code LIMIT 1");
        $dupStmt->execute([':code' => $code]);
        if ($dupStmt->fetch()) {
            return ['success' => false, 'promo_id' => null, 'error' => "Promo code '{$code}' already exists."];
        }

        $stmt = $pdo->prepare("
            INSERT INTO promo_codes (
                code, discount_type, discount_value, min_spend, max_discount,
                total_use_limit, per_user_limit, times_used, is_active,
                valid_from, valid_until, created_by, created_at
            ) VALUES (
                :code, :dtype, :dval, :min_spend, :max_discount,
                :total_limit, :per_user_limit, 0, 1,
                :vfrom, :vuntil, :created_by, NOW()
            )
        ");
        $stmt->execute([
            ':code'           => $code,
            ':dtype'          => $type,
            ':dval'           => $val,
            ':min_spend'      => $minSpend,
            ':max_discount'   => $maxDiscount,
            ':total_limit'    => $totalLimit,
            ':per_user_limit' => $perUserLimit,
            ':vfrom'          => date('Y-m-d H:i:s', strtotime($validFrom)),
            ':vuntil'         => date('Y-m-d H:i:s', strtotime($validUntil)),
            ':created_by'     => $adminUserId
        ]);

        return ['success' => true, 'promo_id' => (int)$pdo->lastInsertId(), 'error' => null];

    } catch (\PDOException $e) {
        error_log('create_promo_code error: ' . $e->getMessage());
        return ['success' => false, 'promo_id' => null, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Toggle promo code active status (FR-PROMO-001)
 * 
 * @param int $promoId
 * @return bool
 */
function toggle_promo_status(int $promoId): bool
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return true;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE promo_codes 
            SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW()
            WHERE promo_id = :id
        ");
        $stmt->execute([':id' => $promoId]);
        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        error_log('toggle_promo_status error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete or deactivate promo code
 * (Preserves historical booking integrity if already used)
 * 
 * @param int $promoId
 * @return array ['success' => bool, 'action' => 'deleted'|'deactivated', 'error' => string|null]
 */
function delete_promo_code(int $promoId): array
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return ['success' => true, 'action' => 'deleted', 'error' => null];
    }

    try {
        // Check if referenced in bookings or used
        $checkStmt = $pdo->prepare("
            SELECT times_used, (SELECT COUNT(*) FROM bookings WHERE promo_code_id = :id) AS booking_count
            FROM promo_codes WHERE promo_id = :id
        ");
        $checkStmt->execute([':id' => $promoId]);
        $row = $checkStmt->fetch();

        if (!$row) {
            return ['success' => false, 'action' => 'none', 'error' => 'Promo code not found.'];
        }

        if ((int)$row['times_used'] > 0 || (int)$row['booking_count'] > 0) {
            // Soft deactivate to preserve booking audit integrity
            $pdo->prepare("UPDATE promo_codes SET is_active = 0 WHERE promo_id = :id")->execute([':id' => $promoId]);
            return ['success' => true, 'action' => 'deactivated', 'error' => null];
        }

        // Hard delete if never used
        $del = $pdo->prepare("DELETE FROM promo_codes WHERE promo_id = :id");
        $del->execute([':id' => $promoId]);
        return ['success' => true, 'action' => 'deleted', 'error' => null];

    } catch (\PDOException $e) {
        error_log('delete_promo_code error: ' . $e->getMessage());
        return ['success' => false, 'action' => 'none', 'error' => 'Could not delete promo code.'];
    }
}

/**
 * Validate promo code against business rules (BR-11, BR-12)
 * 
 * @param string $code
 * @param float $subtotal
 * @param int|null $userId
 * @return array
 */
function validate_promo_code(string $code, float $subtotal, ?int $userId = null): array
{
    $code = strtoupper(trim($code));
    if (empty($code)) {
        return ['valid' => false, 'message' => 'Please enter a promo code.'];
    }

    $promo = get_promo_code_by_code($code);
    if (!$promo) {
        return ['valid' => false, 'message' => "Promo code '{$code}' does not exist."];
    }

    if ((int)$promo['is_active'] !== 1) {
        return ['valid' => false, 'message' => "Promo code '{$code}' is currently inactive."];
    }

    $now = time();
    $validFrom  = strtotime($promo['valid_from']);
    $validUntil = strtotime($promo['valid_until']);

    if ($now < $validFrom) {
        return ['valid' => false, 'message' => "Promo code '{$code}' is not yet active."];
    }
    if ($now > $validUntil) {
        return ['valid' => false, 'message' => "Promo code '{$code}' expired on " . format_date($promo['valid_until'], 'M d, Y') . "."];
    }

    if ($promo['min_spend'] !== null && $subtotal < (float)$promo['min_spend']) {
        return [
            'valid'   => false,
            'message' => "Promo code requires a minimum spend of " . format_currency((float)$promo['min_spend']) . " (current subtotal: " . format_currency($subtotal) . ")."
        ];
    }

    if ($promo['total_use_limit'] !== null && (int)$promo['times_used'] >= (int)$promo['total_use_limit']) {
        return ['valid' => false, 'message' => "Promo code '{$code}' has reached its maximum global usage limit."];
    }

    // Per-user limit check
    if ($userId !== null && $promo['per_user_limit'] !== null) {
        $pdo = get_db_connection();
        if ($pdo) {
            $userUsageStmt = $pdo->prepare("
                SELECT COUNT(*) FROM bookings
                WHERE user_id = :uid AND promo_code_id = :pid AND status != 'cancelled'
            ");
            $userUsageStmt->execute([':uid' => $userId, ':pid' => $promo['promo_id']]);
            $timesUsedByUser = (int)$userUsageStmt->fetchColumn();

            if ($timesUsedByUser >= (int)$promo['per_user_limit']) {
                return ['valid' => false, 'message' => "You have already reached the usage limit for promo code '{$code}'."];
            }
        }
    }

    // Calculate discount amount
    $discountAmount = 0.00;
    if ($promo['discount_type'] === 'percentage') {
        $calc = $subtotal * ((float)$promo['discount_value'] / 100.0);
        if ($promo['max_discount'] !== null && $calc > (float)$promo['max_discount']) {
            $calc = (float)$promo['max_discount'];
        }
        $discountAmount = round($calc, 2);
    } else {
        $discountAmount = round(min($subtotal, (float)$promo['discount_value']), 2);
    }

    return [
        'valid'           => true,
        'promo_id'        => (int)$promo['promo_id'],
        'code'            => $promo['code'],
        'discount_type'   => $promo['discount_type'],
        'discount_value'  => (float)$promo['discount_value'],
        'discount_amount' => $discountAmount,
        'min_spend'       => $promo['min_spend'] !== null ? (float)$promo['min_spend'] : null,
        'max_discount'    => $promo['max_discount'] !== null ? (float)$promo['max_discount'] : null,
        'message'         => "Promo code applied successfully! Saved " . format_currency($discountAmount) . "."
    ];
}

/**
 * Increment promo code usage counter atomically (FR-PROMO-006)
 * 
 * @param int $promoId
 * @return bool
 */
function increment_promo_usage(int $promoId): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return true;

    try {
        $stmt = $pdo->prepare("UPDATE promo_codes SET times_used = times_used + 1 WHERE promo_id = :id");
        $stmt->execute([':id' => $promoId]);
        return $stmt->rowCount() > 0;
    } catch (\PDOException $e) {
        error_log('increment_promo_usage error: ' . $e->getMessage());
        return false;
    }
}

// ============================================================
// MOCK FALLBACKS (for offline testing)
// ============================================================

function get_mock_promo_codes(bool $activeOnly = false): array
{
    $mock = [
        [
            'promo_id'        => 1,
            'code'            => 'WELCOME10',
            'discount_type'   => 'percentage',
            'discount_value'  => 10.00,
            'min_spend'       => 500.00,
            'max_discount'    => 200.00,
            'total_use_limit' => 100,
            'per_user_limit'  => 1,
            'times_used'      => 12,
            'is_active'       => 1,
            'valid_from'      => date('Y-m-d H:i:s', strtotime('-10 days')),
            'valid_until'     => date('Y-m-d H:i:s', strtotime('+80 days')),
            'created_by'      => 1,
            'created_by_name' => 'System Administrator',
            'created_at'      => date('Y-m-d H:i:s', strtotime('-10 days'))
        ],
        [
            'promo_id'        => 2,
            'code'            => 'FLAT100',
            'discount_type'   => 'fixed',
            'discount_value'  => 100.00,
            'min_spend'       => 300.00,
            'max_discount'    => null,
            'total_use_limit' => 50,
            'per_user_limit'  => 1,
            'times_used'      => 5,
            'is_active'       => 1,
            'valid_from'      => date('Y-m-d H:i:s', strtotime('-5 days')),
            'valid_until'     => date('Y-m-d H:i:s', strtotime('+55 days')),
            'created_by'      => 1,
            'created_by_name' => 'System Administrator',
            'created_at'      => date('Y-m-d H:i:s', strtotime('-5 days'))
        ]
    ];

    if ($activeOnly) {
        $now = time();
        $mock = array_filter($mock, function($p) use ($now) {
            return (int)$p['is_active'] === 1 && strtotime($p['valid_from']) <= $now && strtotime($p['valid_until']) >= $now;
        });
    }

    return array_values($mock);
}
