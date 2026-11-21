<?php
/**
 * Eventora - PayHere Sandbox Checkout Initiation Page
 * 
 * Implements:
 * - FR-PAY-001: PayHere sandbox environment checkout
 * - FR-PAY-002: Transmit merchant, order, amount, currency, customer and return/cancel/notify URLs
 * - FR-PAY-003: Associate unique local order reference with booking
 * - FR-PAY-009: Clearly identify sandbox/test mode (no credit card storage)
 * - BR-08: 10-minute temporary seat hold
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
    set_flash_message('danger', 'Booking reference is required for checkout.');
    redirect(APP_URL . '/pages/student/bookings.php');
}

$booking = get_booking_by_ref($bookingRef, (int)$user['id']);

if (!$booking) {
    set_flash_message('danger', 'Booking not found or access denied.');
    redirect(APP_URL . '/pages/student/bookings.php');
}

if ($booking['status'] === 'confirmed') {
    set_flash_message('info', 'This booking has already been paid and confirmed.');
    redirect(APP_URL . '/pages/student/booking_detail.php?ref=' . urlencode($bookingRef));
}

if ($booking['status'] === 'cancelled' || $booking['status'] === 'expired') {
    set_flash_message('warning', 'This booking seat hold has expired or was cancelled. Please book new seats.');
    redirect(APP_URL . '/index.php');
}

$totalAmount = (float)$booking['total'];
if ($totalAmount <= 0.00) {
    // Free event should not go through PayHere
    redirect(APP_URL . '/pages/student/booking_detail.php?ref=' . urlencode($bookingRef));
}

// Generate or retrieve unique order reference for this payment attempt (FR-PAY-003)
$orderData = get_or_create_payment_order((int)$booking['booking_id'], $totalAmount);
$orderRef  = $orderData['order_ref'];

// Generate cryptographic hash (FR-PAY-004, NFR-SEC-006)
$hash = generate_payhere_hash($orderRef, $totalAmount, 'LKR');

// Handle local sandbox simulation for offline / localhost evaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_payment'])) {
    if (verify_csrf_token()) {
        $simResult = simulate_sandbox_payment_callback($orderRef, $totalAmount, 2);
        if ($simResult['success']) {
            set_flash_message('success', 'Sandbox payment successfully simulated and verified! Official QR ticket generated.');
            redirect(APP_URL . '/pages/student/booking_detail.php?ref=' . urlencode($bookingRef));
        } else {
            set_flash_message('danger', $simResult['message']);
        }
    }
}

// Name splitting for customer info
$nameParts = explode(' ', trim($user['name']));
$firstName = $nameParts[0];
$lastName  = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : 'Student';

$pageTitle = 'PayHere Sandbox Checkout · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <!-- Sandbox Mode Notice (FR-PAY-009) -->
            <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4" style="border-radius: 0.75rem;">
                <i class="bi bi-shield-shaded display-6 text-warning" aria-hidden="true"></i>
                <div>
                    <h5 class="alert-heading fw-bold mb-1">PayHere Sandbox / Assessment Test Mode</h5>
                    <p class="small mb-0 text-dark">
                        This transaction is running in test mode. <strong>No live charges will be made</strong>, and no real card credentials will be collected or stored.
                    </p>
                </div>
            </div>

            <!-- Checkout Order Summary Card -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h4 fw-bold mb-0">Order Checkout</h1>
                            <small class="text-muted">Order ID: <code><?php echo e($orderRef); ?></code></small>
                        </div>
                        <span class="badge bg-primary fs-6 px-3 py-2">
                            LKR <?php echo number_format($totalAmount, 2); ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Itemized summary -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="text-dark"><?php echo e($booking['event_title']); ?></strong>
                            <span><?php echo (int)$booking['seats']; ?> Seat(s)</span>
                        </div>
                        <div class="text-muted small mb-2">
                            <i class="bi bi-calendar3 me-1"></i> <?php echo format_datetime($booking['start_date']); ?>
                        </div>
                        <div class="text-muted small mb-3">
                            <i class="bi bi-geo-alt me-1"></i> <?php echo e($booking['venue']); ?>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Subtotal:</span>
                            <span>LKR <?php echo number_format($booking['subtotal'], 2); ?></span>
                        </div>
                        <?php if ((float)$booking['discount_amount'] > 0.00): ?>
                            <div class="d-flex justify-content-between small text-success mb-2">
                                <span>Discount (<?php echo ucfirst(e($booking['discount_type'])); ?>):</span>
                                <span>- LKR <?php echo number_format($booking['discount_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fw-bold text-dark fs-5 pt-2 border-top">
                            <span>Payable Amount:</span>
                            <span class="text-primary">LKR <?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                    </div>

                    <!-- PayHere Official Sandbox Checkout Form (FR-PAY-002) -->
                    <form action="<?php echo PAYHERE_CHECKOUT_URL; ?>" method="POST" id="payhereCheckoutForm">
                        <input type="hidden" name="merchant_id" value="<?php echo PAYHERE_MERCHANT_ID; ?>">
                        <input type="hidden" name="return_url" value="<?php echo PAYHERE_RETURN_URL; ?>?ref=<?php echo urlencode($bookingRef); ?>">
                        <input type="hidden" name="cancel_url" value="<?php echo PAYHERE_CANCEL_URL; ?>?ref=<?php echo urlencode($bookingRef); ?>">
                        <input type="hidden" name="notify_url" value="<?php echo PAYHERE_NOTIFY_URL; ?>">

                        <!-- Customer Details -->
                        <input type="hidden" name="first_name" value="<?php echo e($firstName); ?>">
                        <input type="hidden" name="last_name" value="<?php echo e($lastName); ?>">
                        <input type="hidden" name="email" value="<?php echo e($user['email']); ?>">
                        <input type="hidden" name="phone" value="+94771234567">
                        <input type="hidden" name="address" value="NIIBS Campus, FCIT">
                        <input type="hidden" name="city" value="Kelaniya">
                        <input type="hidden" name="country" value="Sri Lanka">

                        <!-- Order Details -->
                        <input type="hidden" name="order_id" value="<?php echo e($orderRef); ?>">
                        <input type="hidden" name="items" value="<?php echo e($booking['event_title']); ?>">
                        <input type="hidden" name="currency" value="LKR">
                        <input type="hidden" name="amount" value="<?php echo number_format($totalAmount, 2, '.', ''); ?>">

                        <!-- Cryptographic Hash Signature (FR-PAY-004) -->
                        <input type="hidden" name="hash" value="<?php echo $hash; ?>">

                        <!-- Official Submit Button -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-warning btn-lg fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-up-right me-2"></i> Pay with PayHere Sandbox
                            </button>
                        </div>
                    </form>

                    <!-- Evaluation Helper: Instant Sandbox Simulation -->
                    <div class="p-3 bg-light rounded-3 border text-center mt-4">
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-cpu me-1 text-primary"></i> <strong>Offline / Localhost Assessment Simulation:</strong>
                            <br>Since external webhooks cannot reach <code>localhost</code> without tunneling, you can simulate a verified PayHere webhook callback instantly:
                        </small>
                        <form action="checkout.php?ref=<?php echo urlencode($bookingRef); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="simulate_payment" value="1">
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-check2-circle me-1"></i> Simulate Instant Sandbox Payment
                            </button>
                        </form>
                    </div>

                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>/pages/student/booking_detail.php?ref=<?php echo urlencode($bookingRef); ?>" class="text-muted small">
                            <i class="bi bi-arrow-left me-1"></i> Return to Booking Summary
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
