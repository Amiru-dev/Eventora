<?php
/**
 * Eventora - Student Registration Page
 * FR-AUTH-001, FR-AUTH-002, FR-AUTH-006 (Prevents self-registration as admin)
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Student Registration · ' . APP_NAME;
$activePage = 'register';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="p-4 bg-primary text-white text-center">
                    <i class="bi bi-mortarboard display-5 mb-2 d-inline-block" aria-hidden="true"></i>
                    <h1 class="h3 fw-bold mb-1 text-white">Join Eventora</h1>
                    <p class="text-white-50 small mb-0">Register with your university credentials to book campus seats</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form id="studentRegisterForm" action="register.php" method="POST" novalidate class="needs-validation">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="A. U. Vidanapathirana" required minlength="3">
                            <div class="invalid-feedback">Please enter your full official name.</div>
                        </div>

                        <!-- University Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">University Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="student@eventora.com" required>
                            <div class="form-text small">Your official campus email address for receiving QR tickets.</div>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>

                        <!-- Password -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required minlength="8">
                                <div class="invalid-feedback">Must be at least 8 characters long.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                                <div class="invalid-feedback">Passwords must match.</div>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label small text-muted" for="terms">
                                I agree to the campus event attendance guidelines and code of conduct.
                            </label>
                            <div class="invalid-feedback">You must agree before submitting.</div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus-fill me-1" aria-hidden="true"></i> Create Student Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top small text-muted">
                        Already have an account? <a href="login.php" class="fw-bold text-primary">Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
