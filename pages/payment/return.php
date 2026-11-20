<?php
/**
 * Eventora - PayHere Browser Return Landing Page
 * 
 * Implements:
 * - FR-PAY-005: Update status strictly from verified server notification, NOT from browser return alone
 * - FR-PAY-010: Display clear pending state when payment verification is still in-flight
 * - BR-19: Browser redirects alone do not establish payment success
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/bookings.php';
require_once __DIR__ . '/../../includes/payments.php';

require_login();

$user = current_user();
$bookingRef = $_GET['ref'] ?? '';

if (empty($bookingRef)) {
    redirect(APP_URL . '/pages/student/bookings.php');
}

$booking = get_booking_by_ref($bookingRef, (int)$user['id']);

if (!$booking) {
    set_flash_message('danger', 'Booking record not found.');
    redirect(APP_URL . '/pages/student/bookings.php');
}

$isConfirmed = ($booking['status'] === 'confirmed');
$isPending   = ($booking['status'] === 'pending_payment');

$pageTitle = 'Payment Status · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg text-center overflow-hidden" style="border-radius: 1rem;">
                <?php if ($isConfirmed): ?>
                    <!-- Success State -->
                    <div class="p-5 bg-success text-white">
                        <i class="bi bi-check-circle-fill display-3 mb-2 d-inline-block"></i>
                        <h1 class="h2 fw-bold text-white mb-1">Payment Successfully Verified!</h1>
                        <p class="text-white-50 mb-0">Your seats for <strong><?php echo e($booking['event_title']); ?></strong> are secured.</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="alert alert-light border small text-muted text-start mb-4">
                            <div><strong>Booking Reference:</strong> <?php echo e($booking['booking_ref']); ?></div>
                            <div><strong>Seats Confirmed:</strong> <?php echo (int)$booking['seats']; ?></div>
                            <div><strong>Total Amount:</strong> <?php echo format_currency($booking['total']); ?></div>
                            <div><strong>Verification:</strong> <span class="text-success"><i class="bi bi-shield-check"></i> Cryptographically Verified (FR-PAY-004)</span></div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="<?php echo APP_URL; ?>/pages/student/ticket.php?ref=<?php echo urlencode($booking['booking_ref']); ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-qr-code me-2"></i> View & Print QR E-Ticket (FR-TKT-002)
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary">
                                My Bookings Dashboard
                            </a>
                        </div>
                    </div>

                <?php elseif ($isPending): ?>
                    <!-- Pending State (FR-PAY-010, BR-19) -->
                    <div class="p-5 bg-warning text-dark">
                        <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Awaiting verification...</span>
                        </div>
                        <h1 class="h2 fw-bold mb-1">Payment Pending Verification</h1>
                        <p class="text-dark-50 mb-0">We have received your return request and are awaiting official server confirmation.</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <div class="alert alert-info border-0 text-start small mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Security Rule (FR-PAY-005, BR-19):</strong> Payment status is updated strictly from cryptographically verified server notifications, not from browser redirects alone. If your transaction just finished, please refresh in a few seconds.
                        </div>

                        <div class="d-grid gap-2">
                            <a href="return.php?ref=<?php echo urlencode($booking['booking_ref']); ?>" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise me-1"></i> Check Status Again
                            </a>
                            <a href="<?php echo APP_URL; ?>/pages/student/booking_detail.php?ref=<?php echo urlencode($booking['booking_ref']); ?>" class="btn btn-outline-secondary">
                                View Booking Details
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Other Status -->
                    <div class="p-5 bg-secondary text-white">
                        <i class="bi bi-info-circle-fill display-3 mb-2 d-inline-block"></i>
                        <h1 class="h2 fw-bold text-white mb-1">Booking <?php echo ucfirst(e($booking['status'])); ?></h1>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted">This booking is in <code><?php echo e($booking['status']); ?></code> status.</p>
                        <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-primary">Return to Bookings</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
