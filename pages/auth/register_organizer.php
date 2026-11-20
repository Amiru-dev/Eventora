<?php
/**
 * Eventora - Organizer Registration Page
 * FR-AUTH-004: Organizer registration creates pending profile; admin approves
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Organizer Registration · ' . APP_NAME;
$activePage = 'register_organizer';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7">
            <div class="card border-0 shadow-lg" style="border-radius: 1rem; overflow: hidden;">
                <div class="p-4 bg-dark text-white text-center">
                    <i class="bi bi-briefcase display-5 mb-2 text-warning d-inline-block" aria-hidden="true"></i>
                    <h1 class="h3 fw-bold mb-1 text-white">Register as Event Organizer</h1>
                    <p class="text-white-50 small mb-0">Submit your organization profile for administrative review</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-info-circle-fill fs-5" aria-hidden="true"></i>
                        <small>
                            <strong>Approval Notice:</strong> In accordance with university policy (FR-AUTH-004), your account will be created with <em>pending</em> status until verified by a campus administrator.
                        </small>
                    </div>

                    <form id="organizerRegisterForm" action="register_organizer.php" method="POST" novalidate class="needs-validation">
                        <!-- Representative Name -->
                        <div class="mb-3">
                            <label for="org_name" class="form-label">Contact Person Name</label>
                            <input type="text" class="form-control" id="org_name" name="name" placeholder="John Doe" required minlength="3">
                            <div class="invalid-feedback">Representative name is required.</div>
                        </div>

                        <!-- Organization / Club Name -->
                        <div class="mb-3">
                            <label for="organization" class="form-label">Club / Student Society Name</label>
                            <input type="text" class="form-control" id="organization" name="organization" placeholder="e.g. NIIBS Tech Club, Rotaract, Sports Council" required>
                            <div class="invalid-feedback">Please provide your society or faculty club name.</div>
                        </div>

                        <!-- Contact Details -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Official Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="club@eventora.com" required>
                                <div class="invalid-feedback">Valid email required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+94 77 123 4567" required>
                                <div class="invalid-feedback">Valid contact number required.</div>
                            </div>
                        </div>

                        <!-- Organization Bio / Purpose Statement -->
                        <div class="mb-3">
                            <label for="bio" class="form-label">Organization Description / Purpose</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Briefly describe your club and the kinds of events you host on campus..." required></textarea>
                            <div class="invalid-feedback">Please provide a brief description.</div>
                        </div>

                        <!-- Password -->
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Account Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send-check me-1" aria-hidden="true"></i> Submit Organizer Application
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top small text-muted">
                        Already have an approved organizer profile? <a href="login.php" class="fw-bold text-primary">Log In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
