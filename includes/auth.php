<?php
/**
 * Eventora - Authentication & Role-Based Authorization
 * 
 * Implements:
 * - FR-AUTH-001: Register student account with name, unique email, password
 * - FR-AUTH-002: Hash passwords and verify them (password_hash / password_verify)
 * - FR-AUTH-003: Student, organizer, and administrator roles with server authorization
 * - FR-AUTH-004: Organizer creates pending profile; requires admin approval
 * - FR-AUTH-005: Regenerate session ID on login; destroy on logout
 * - FR-AUTH-006: Prevent self-registration as administrator
 * - FR-AUTH-007: Enforce organizer ownership
 * - NFR-SEC-001: Passwords hashed, credentials protected
 * - NFR-SEC-002: PDO prepared statements
 * - NFR-SEC-004: Server-side authorization checks on every protected action
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

start_session_safe();

/**
 * Register a new student account
 * FR-AUTH-001, FR-AUTH-002, FR-AUTH-006
 * 
 * @param string $name
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null, 'user_id' => int|null]
 */
function register_student(string $name, string $email, string $password): array
{
    $name = sanitize_input($name);
    $email = strtolower(trim($email));

    // Server-side validation (FR-VAL-002)
    if (mb_strlen($name) < 3) {
        return ['success' => false, 'error' => 'Full name must be at least 3 characters long.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
    }

    try {
        $pdo = getDBConnection();

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        // Hash password securely (FR-AUTH-002, NFR-SEC-001)
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Force student role (FR-AUTH-006: Prevents self-registration as admin)
        $insertStmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, is_active, created_at)
            VALUES (:name, :email, :password_hash, 'student', 1, NOW())
        ");
        $insertStmt->execute([
            ':name'          => $name,
            ':email'         => $email,
            ':password_hash' => $passwordHash
        ]);

        $userId = (int)$pdo->lastInsertId();
        return ['success' => true, 'error' => null, 'user_id' => $userId];

    } catch (PDOException $e) {
        error_log("Student registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed due to a database error. Please try again.'];
    }
}

/**
 * Register a new organizer account (pending administrative approval)
 * FR-AUTH-004, FR-ORG-001
 * 
 * @param string $name
 * @param string $email
 * @param string $password
 * @param string $organization
 * @param string $phone
 * @param string $bio
 * @return array ['success' => bool, 'error' => string|null]
 */
function register_organizer(
    string $name,
    string $email,
    string $password,
    string $organization,
    string $phone,
    string $bio
): array {
    $name = sanitize_input($name);
    $email = strtolower(trim($email));
    $organization = sanitize_input($organization);
    $phone = sanitize_input($phone);
    $bio = sanitize_input($bio);

    if (mb_strlen($name) < 3) {
        return ['success' => false, 'error' => 'Contact name must be at least 3 characters.'];
    }
    if (mb_strlen($organization) < 3) {
        return ['success' => false, 'error' => 'Club or Organization name is required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters.'];
    }

    try {
        $pdo = getDBConnection();

        // Check unique email
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Transaction to insert both user and pending organizer profile
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, is_active, created_at)
            VALUES (:name, :email, :password_hash, 'organizer', 1, NOW())
        ");
        $userStmt->execute([
            ':name'          => $name,
            ':email'         => $email,
            ':password_hash' => $passwordHash
        ]);
        $userId = (int)$pdo->lastInsertId();

        // Organizer profile with 'pending' status (FR-AUTH-004)
        $profileStmt = $pdo->prepare("
            INSERT INTO organizer_profiles (user_id, bio, phone, organization, approval_status, created_at)
            VALUES (:user_id, :bio, :phone, :organization, 'pending', NOW())
        ");
        $profileStmt->execute([
            ':user_id'      => $userId,
            ':bio'          => $bio,
            ':phone'        => $phone,
            ':organization' => $organization
        ]);

        $pdo->commit();
        return ['success' => true, 'error' => null];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Organizer registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Application submission failed. Please try again.'];
    }
}

/**
 * Authenticate user and initiate session
 * FR-AUTH-002, FR-AUTH-004, FR-AUTH-005
 * 
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null, 'user' => array|null]
 */
function login_user(string $email, string $password): array
{
    $email = strtolower(trim($email));

    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Please enter both your email and password.'];
    }

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, op.profile_id, op.approval_status as org_status, op.organization
            FROM users u
            LEFT JOIN organizer_profiles op ON u.user_id = op.user_id
            WHERE u.email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        // Verify hashed password (FR-AUTH-002)
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        // Account status check
        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'error' => 'Your account has been deactivated. Please contact the administrator.'];
        }

        // Organizer approval gate (FR-AUTH-004, UC-03)
        if ($user['role'] === 'organizer') {
            if ($user['org_status'] === 'pending') {
                return [
                    'success' => false,
                    'error'   => 'Your organizer account is pending administrator approval. You will receive access once verified.'
                ];
            } elseif ($user['org_status'] === 'rejected') {
                return [
                    'success' => false,
                    'error'   => 'Your organizer application was rejected. Please contact campus administration.'
                ];
            } elseif ($user['org_status'] === 'suspended') {
                return [
                    'success' => false,
                    'error'   => 'Your organizer privileges have been suspended (FR-ORG-006).'
                ];
            }
        }

        // Regenerate session ID to prevent session fixation attacks (FR-AUTH-005)
        session_regenerate_id(true);

        // Store authoritative session payload
        $_SESSION['user'] = [
            'id'           => (int)$user['user_id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'role'         => $user['role'],
            'profile_id'   => $user['profile_id'] ? (int)$user['profile_id'] : null,
            'organization' => $user['organization'] ?? null
        ];

        return ['success' => true, 'error' => null, 'user' => $_SESSION['user']];

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Authentication service unavailable. Please try again.'];
    }
}

/**
 * Destroy current session and log out
 * FR-AUTH-005
 */
function logout_user(): void
{
    start_session_safe();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Get currently authenticated user data
 * 
 * @return array|null
 */
function current_user(): ?array
{
    start_session_safe();
    return $_SESSION['user'] ?? null;
}

/**
 * Check if a user is authenticated
 * 
 * @return bool
 */
function is_logged_in(): bool
{
    return current_user() !== null;
}

/**
 * Check if the authenticated user has a specific role
 * FR-AUTH-003
 * 
 * @param string|array $roles
 * @return bool
 */
function has_role($roles): bool
{
    $user = current_user();
    if (!$user) return false;

    if (is_array($roles)) {
        return in_array($user['role'], $roles, true);
    }
    return $user['role'] === $roles;
}

/**
 * Authorization guard: Require user to be logged in
 * 
 * @param string|null $redirectAfter
 */
function require_login(?string $redirectAfter = null): void
{
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please sign in to access this page.');
        $target = APP_URL . '/pages/auth/login.php';
        if ($redirectAfter) {
            $target .= '?return=' . urlencode($redirectAfter);
        }
        redirect($target);
    }
}

/**
 * Authorization guard: Require user to have one of the specified roles
 * FR-AUTH-003, NFR-SEC-004
 * 
 * @param string|array $roles
 */
function require_role($roles): void
{
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        set_flash_message('danger', 'Access denied. You do not have permission to access that resource.');
        redirect(APP_URL . '/index.php');
    }
}

/**
 * Enforce organizer event ownership
 * FR-AUTH-007, BR-15: Organizers can only manage their own events; Admin can manage all
 * 
 * @param int $eventId
 * @return bool
 */
function can_manage_event(int $eventId): bool
{
    $user = current_user();
    if (!$user) return false;

    // Administrators have global management access (BR-15)
    if ($user['role'] === 'admin') {
        return true;
    }

    if ($user['role'] === 'organizer' && !empty($user['profile_id'])) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                SELECT event_id FROM events
                WHERE event_id = :event_id AND organizer_id = :organizer_id
                LIMIT 1
            ");
            $stmt->execute([
                ':event_id'     => $eventId,
                ':organizer_id' => $user['profile_id']
            ]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    return false;
}
