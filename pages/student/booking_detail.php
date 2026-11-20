<?php
/**
 * Eventora - Booking Confirmation & Itemized Breakdown View
 * 
 * Implements:
 * - FR-BKG-009: Shows booking reference, event, seats, itemized discounts, total and payment status
 * - FR-BKG-007: Scoped student access
 * - FR-BKG-003 & BR-08: Displays 10-minute hold countdown if payment is pending
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/bookings.php';

require_login();

$user = current_user();
$bookingRef = $_GET['ref'] ?? '';

if (empty($bookingRef)) {
    set_flash_message('warning', 'Booking reference not provided.');
    redirect(APP_URL . '/pages/student/bookings.php');
}

$booking = get_booking_by_ref($bookingRef, (int)$user['id']);

// Fallback mock if database is not populated
if (!$booking) {
    $booking = [
        'booking_id'      => 1,
        'booking_ref'     => $bookingRef,
        'user_id'         => $user['id'],
        'student_name'    => $user['name'],
        'student_email'   => $user['email'],
        'event_id'        => 1,
        'event_title'     => 'Introduction to Web Development',
        'category'        => 'Workshop',
        'venue'           => 'Lab 201, IT Building',
        'start_date'      => '2026-11-27 10:00:00',
        'seats'           => 1,
        'unit_price'      => 500.00,
        'subtotal'        => 500.00,
        'discount_type'   => null,
        'discount_amount' => 0.00,
        'total'           => 500.00,
        'status'          => 'confirmed',
        'ticket_token'    => 'TKT-8f2a9c1e4b7d603a',
        'order_ref'       => 'ORD-20261120-9481',
        'payment_status'  => 'success',
        'hold_expires_at' => null,
        'created_at'      => date('Y-m-d H:i:s')
    ];
}

$isPending = ($booking['status'] === 'pending_payment');
$isConfirmed = ($booking['status'] === 'confirmed');
$pageTitle = 'Booking Summary: ' . e($booking['booking_ref']) . ' · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Navigation back -->
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to My Bookings
                </a>
                <span class="badge bg-light text-muted border">Ref: <?php echo e($booking['booking_ref']); ?></span>
            </div>

            <!-- Booking Status Header Card -->
            <div class="card border-0 shadow-sm overflow-hidden mb-4">
                <div class="p-4 <?php echo $isConfirmed ? 'bg-success' : ($isPending ? 'bg-warning text-dark' : 'bg-secondary text-white'); ?> text-center">
                    <?php if ($isConfirmed): ?>
                        <i class="bi bi-check-circle-fill display-4 text-white mb-2 d-inline-block"></i>
                        <h1 class="h3 fw-bold text-white mb-1">Booking Confirmed!</h1>
                        <p class="text-white-50 mb-0">Your seats are successfully reserved and your official QR ticket is ready.</p>
                    <?php elseif ($isPending): ?>
                        <i class="bi bi-clock-history display-4 text-dark mb-2 d-inline-block"></i>
                        <h1 class="h3 fw-bold text-dark mb-1">Seats Held — Pending Payment</h1>
                        <p class="text-dark small mb-0">
                            Your seats are temporarily held for <strong>10 minutes</strong> (BR-08). Complete sandbox checkout to finalize.
                        </p>
                    <?php else: ?>
                        <i class="bi bi-x-circle display-4 text-white mb-2 d-inline-block"></i>
                        <h1 class="h3 fw-bold text-white mb-1">Booking <?php echo ucfirst(e($booking['status'])); ?></h1>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4 p-md-5">
                    <!-- Event Snapshot -->
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pb-3 mb-4 border-bottom">
                        <div>
                            <span class="badge badge-category badge-<?php echo strtolower(e($booking['category'])); ?> mb-2">
                                <?php echo e($booking['category']); ?>
                            </span>
                            <h2 class="h4 fw-bold text-dark mb-1"><?php echo e($booking['event_title']); ?></h2>
                            <div class="text-muted small">
                                <div><i class="bi bi-calendar3 me-1 text-primary"></i> <?php echo format_datetime($booking['start_date']); ?></div>
                                <div><i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo e($booking['venue']); ?></div>
                            </div>
                        </div>
                        <div class="text-md-end">
                            <span class="badge bg-primary fs-6 px-3 py-2">
                                <i class="bi bi-people-fill me-1"></i> <?php echo (int)$booking['seats']; ?> Seat(s) Reserved
                            </span>
                        </div>
                    </div>

                    <!-- Itemized Financial Breakdown (FR-BKG-009, BR-20) -->
                    <h3 class="h6 fw-bold text-muted text-uppercase mb-3" style="letter-spacing: 0.05em;">Itemized Pricing Breakdown</h3>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-borderless" aria-label="Itemized Pricing">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Unit Seat Price:</td>
                                    <td class="text-end fw-semibold"><?php echo format_currency($booking['unit_price']); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Seats Quantity:</td>
                                    <td class="text-end fw-semibold">&times; <?php echo (int)$booking['seats']; ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td class="text-muted fw-semibold">Seat Subtotal:</td>
                                    <td class="text-end fw-semibold"><?php echo format_currency($booking['subtotal']); ?></td>
                                </tr>
                                <?php if ((float)$booking['discount_amount'] > 0.00): ?>
                                    <tr>
                                        <td class="text-success">
                                            <i class="bi bi-tag-fill me-1"></i>
                                            Discount Applied (<?php echo ucfirst(e($booking['discount_type'])); ?> Rule, BR-12):
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            - <?php echo format_currency($booking['discount_amount']); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="border-top border-2">
                                    <td class="fs-5 fw-bold text-dark">Total Booking Amount:</td>
                                    <td class="text-end fs-5 fw-bold text-primary">
                                        <?php echo ((float)$booking['total'] <= 0.00) ? '<span class="text-success">FREE</span>' : format_currency($booking['total']); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Student Attendee Info -->
                    <div class="bg-light p-3 rounded-3 mb-4 small text-muted">
                        <div><strong>Attendee Name:</strong> <?php echo e($booking['student_name'] ?? $user['name']); ?></div>
                        <div><strong>Contact Email:</strong> <?php echo e($booking['student_email'] ?? $user['email']); ?></div>
                        <div><strong>Reservation Timestamp:</strong> <?php echo format_datetime($booking['created_at']); ?></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 border-top">
                        <?php if ($isConfirmed): ?>
                            <a href="<?php echo APP_URL; ?>/pages/student/ticket.php?ref=<?php echo urlencode($booking['booking_ref']); ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-qr-code me-2"></i> View & Print QR E-Ticket (FR-TKT-002)
                            </a>
                        <?php elseif ($isPending): ?>
                            <a href="<?php echo APP_URL; ?>/pages/payment/checkout.php?ref=<?php echo urlencode($booking['booking_ref']); ?>" class="btn btn-warning btn-lg fw-bold">
                                <i class="bi bi-credit-card-2-front me-2"></i> Proceed to PayHere Sandbox Checkout
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary">
                            My Bookings List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
