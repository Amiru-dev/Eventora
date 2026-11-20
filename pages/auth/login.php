<?php
/**
 * Eventora - Login Page
 * FR-AUTH-002, FR-AUTH-003, FR-AUTH-005, FR-VAL-002, FR-VAL-003
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Redirect if already authenticated
if (is_logged_in()) {
    $user = current_user();
    if ($user['role'] === 'admin') redirect(APP_URL . '/pages/admin/dashboard.php');
    if ($user['role'] === 'organizer') redirect(APP_URL . '/pages/organizer/dashboard.php');
    redirect(APP_URL . '/pages/student/dashboard.php');
}

$error = null;
$emailValue = '';
$returnUrl = $_GET['return'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please attempt sign-in again.';
    } else {
        $email      = $_POST['email'] ?? '';
        $password   = $_POST['password'] ?? '';
        $emailValue = $email;

        $loginResult = login_user($email, $password);
        if ($loginResult['success']) {
            $user = $loginResult['user'];
            set_flash_message('success', 'Signed in successfully as ' . e($user['name']) . '.');

            // If a specific return URL was requested, route there safely
            if (!empty($returnUrl) && strpos($returnUrl, 'http') === false) {
                redirect(APP_URL . '/' . ltrim($returnUrl, '/'));
            }

            // Role-based destination
            if ($user['role'] === 'admin') {
                redirect(APP_URL . '/pages/admin/dashboard.php');
            } elseif ($user['role'] === 'organizer') {
                redirect(APP_URL . '/pages/organizer/dashboard.php');
            } else {
                redirect(APP_URL . '/pages/student/dashboard.php');
            }
        } else {
            $error = $loginResult['error'];
        }
    }
}

$pageTitle = 'Sign In · ' . APP_NAME;
$activePage = 'login';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <!-- Login Card -->
            <div class="card border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="p-4 bg-primary text-white text-center">
                    <i class="bi bi-person-lock display-5 mb-2 d-inline-block" aria-hidden="true"></i>
                    <h1 class="h3 fw-bold mb-1 text-white">Welcome Back</h1>
                    <p class="text-white-50 small mb-0">Sign in to manage your bookings and campus events</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-circle-fill fs-5" aria-hidden="true"></i>
                            <span class="small"><?php echo e($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" action="login.php<?php echo !empty($returnUrl) ? '?return=' . urlencode($returnUrl) : ''; ?>" method="POST" novalidate class="needs-validation">
                        <!-- CSRF Protection (FR-VAL-003, NFR-SEC-005) -->
                        <?php echo csrf_field(); ?>

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="bi bi-envelope" aria-hidden="true"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="student@eventora.com" value="<?php echo e($emailValue); ?>" required autocomplete="email">
                            </div>
                            <div class="invalid-feedback">Please enter a valid university email address.</div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="bi bi-key" aria-hidden="true"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign In
                            </button>
                        </div>
                    </form>

                    <!-- Test Accounts Helper Box -->
                    <div class="alert alert-light border mt-4 p-3 small">
                        <strong class="d-block text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Assessment Test Credentials:</strong>
                        <div class="text-muted">
                            <div>• <strong>Admin:</strong> <code>admin@eventora.com</code> / <code>Admin@123</code></div>
                            <div>• <strong>Student:</strong> <code>student@eventora.com</code> / <code>Student@123</code></div>
                            <div>• <strong>Organizer:</strong> <code>organizer@eventora.com</code> / <code>Organizer@123</code></div>
                        </div>
                    </div>

                    <!-- Registration Links -->
                    <div class="text-center mt-4 pt-3 border-top small text-muted">
                        Don't have an account yet?
                        <a href="register.php" class="fw-bold text-primary">Create Student Account</a>
                        <div class="mt-1">
                            Want to organize events? <a href="register_organizer.php" class="text-secondary">Register as Organizer</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
