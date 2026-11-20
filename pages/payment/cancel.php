<?php
/**
 * Eventora - PayHere Browser Cancel Page
 * 
 * Implements:
 * - FR-PAY-008: Release associated seat hold if checkout is cancelled
 * - BR-08: Ensure capacity is not locked by aborted payment attempts
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/bookings.php';
require_once __DIR__ . '/../../includes/payments.php';

require_login();

$user = current_user();
$bookingRef = $_GET['ref'] ?? '';

// If booking reference provided, release seat hold if still pending (FR-PAY-008)
if (!empty($bookingRef)) {
    $booking = get_booking_by_ref($bookingRef, (int)$user['id']);
    if ($booking && $booking['status'] === 'pending_payment') {
        cancel_booking((int)$booking['booking_id'], (int)$user['id']);
    }
}

$pageTitle = 'Payment Cancelled · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="card border-0 shadow-lg p-5" style="border-radius: 1rem;">
                <div class="mb-3 text-warning">
                    <i class="bi bi-x-circle-fill display-1" aria-hidden="true"></i>
                </div>
                <h1 class="h3 fw-bold text-dark mb-2">Checkout Cancelled</h1>
                <p class="text-muted small mb-4">
                    Your payment at PayHere sandbox was cancelled. Any seats temporarily reserved for this attempt have been safely released back to room availability (FR-PAY-008). No funds were transferred.
                </p>

                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">
                        <i class="bi bi-compass me-1"></i> Browse Campus Events
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary">
                        View My Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
