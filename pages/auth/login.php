<?php
/**
 * Eventora - Login Page
 * FR-AUTH-001, FR-AUTH-002, FR-AUTH-005
 * Semantic form layout, responsive card, validation hints
 */
require_once __DIR__ . '/../../config/app.php';

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
                    <form id="loginForm" action="login.php" method="POST" novalidate class="needs-validation">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="bi bi-envelope" aria-hidden="true"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="student@eventora.com" required autocomplete="email">
                            </div>
                            <div class="invalid-feedback">Please enter a valid university email address.</div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label">Password</label>
                                <a href="#" class="small text-muted">Forgot password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="bi bi-key" aria-hidden="true"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>

                        <!-- Remember Me -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small text-muted" for="remember">Remember this device</label>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign In
                            </button>
                        </div>
                    </form>

                    <!-- Test Accounts Helper Box (For module assessment & evaluation) -->
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
