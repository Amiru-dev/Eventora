<?php
/**
 * Eventora - Organizer Registration Page
 * FR-AUTH-004: Organizer registration creates pending profile; admin approves
 * FR-VAL-002, FR-VAL-003
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$errors = [];
$formData = [
    'name'         => '',
    'organization' => '',
    'email'        => '',
    'phone'        => '',
    'bio'          => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token expired. Please resubmit the form.';
    } else {
        $name            = $_POST['name'] ?? '';
        $organization    = $_POST['organization'] ?? '';
        $email           = $_POST['email'] ?? '';
        $phone           = $_POST['phone'] ?? '';
        $bio             = $_POST['bio'] ?? '';
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $formData = [
            'name'         => $name,
            'organization' => $organization,
            'email'        => $email,
            'phone'        => $phone,
            'bio'          => $bio
        ];

        if (mb_strlen(trim($name)) < 3) {
            $errors[] = 'Representative contact name is required (min 3 characters).';
        }
        if (mb_strlen(trim($organization)) < 3) {
            $errors[] = 'Club or Society name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid contact email is required.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (empty($errors)) {
            $result = register_organizer($name, $email, $password, $organization, $phone, $bio);
            if ($result['success']) {
                set_flash_message('success', 'Your organizer application for "' . e($organization) . '" was submitted! An administrator will review and approve your account shortly.');
                redirect(APP_URL . '/pages/auth/login.php');
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

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
                            <strong>Approval Notice:</strong> In accordance with university policy, your account will be created with <em>pending</em> status until verified by a campus administrator.
                        </small>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm mb-4">
                            <ul class="mb-0 small ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo e($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="organizerRegisterForm" action="register_organizer.php" method="POST" novalidate class="needs-validation">
                        <!-- CSRF Field (FR-VAL-003) -->
                        <?php echo csrf_field(); ?>

                        <!-- Representative Name -->
                        <div class="mb-3">
                            <label for="org_name" class="form-label">Contact Person Name</label>
                            <input type="text" class="form-control" id="org_name" name="name" placeholder="John Doe" value="<?php echo e($formData['name']); ?>" required minlength="3">
                            <div class="invalid-feedback">Representative name is required.</div>
                        </div>

                        <!-- Organization / Club Name -->
                        <div class="mb-3">
                            <label for="organization" class="form-label">Club / Student Society Name</label>
                            <input type="text" class="form-control" id="organization" name="organization" placeholder="e.g. NIIBS Tech Club, Rotaract, Sports Council" value="<?php echo e($formData['organization']); ?>" required>
                            <div class="invalid-feedback">Please provide your society or faculty club name.</div>
                        </div>

                        <!-- Contact Details -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Official Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="club@eventora.com" value="<?php echo e($formData['email']); ?>" required>
                                <div class="invalid-feedback">Valid email required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+94 77 123 4567" value="<?php echo e($formData['phone']); ?>" required>
                                <div class="invalid-feedback">Valid contact number required.</div>
                            </div>
                        </div>

                        <!-- Organization Bio / Purpose Statement -->
                        <div class="mb-3">
                            <label for="bio" class="form-label">Organization Description / Purpose</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Briefly describe your club and the kinds of events you host on campus..." required><?php echo e($formData['bio']); ?></textarea>
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
