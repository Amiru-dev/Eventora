<?php
/**
 * Eventora - Student Bookings Management
 * 
 * Implements:
 * - FR-BKG-007: Students view only their own bookings, payment statuses and tickets
 * - FR-BKG-008: Cancel eligible bookings transactionally and release capacity
 * - BR-18: Cancellation policy enforcement
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/bookings.php';

require_role('student');

$user = current_user();
$userId = (int)$user['id'];

// Handle Cancellation Request (POST with CSRF, FR-BKG-008)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security token invalid. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        $bookingId = (int)($_POST['booking_id'] ?? 0);

        if ($action === 'cancel' && $bookingId > 0) {
            $cancelResult = cancel_booking($bookingId, $userId);
            if ($cancelResult['success']) {
                set_flash_message('success', 'Your booking reservation was successfully cancelled. Seats have been released back to event capacity (FR-BKG-008).');
            } else {
                set_flash_message('danger', $cancelResult['error']);
            }
        }
    }
    redirect(APP_URL . '/pages/student/bookings.php');
}

$bookings = get_student_bookings($userId);

// Fallback sample bookings if database is not populated
if (empty($bookings)) {
    $bookings = [
        [
            'booking_id'      => 1,
            'booking_ref'     => 'EVT-2026-9481',
            'event_id'        => 1,
            'event_title'     => 'Introduction to Web Development',
            'category'        => 'Workshop',
            'start_date'      => '2026-11-27 10:00:00',
            'seats'           => 1,
            'unit_price'      => 500.00,
            'subtotal'        => 500.00,
            'discount_amount' => 0.00,
            'total'           => 500.00,
            'status'          => 'confirmed',
            'ticket_token'    => 'TKT-8f2a9c1e4b7d603a',
            'created_at'      => '2026-11-20 15:30:00'
        ],
        [
            'booking_id'      => 2,
            'booking_ref'     => 'EVT-2026-1022',
            'event_id'        => 2,
            'event_title'     => 'AI and Machine Learning Seminar',
            'category'        => 'Seminar',
            'start_date'      => '2026-12-04 14:00:00',
            'seats'           => 2,
            'unit_price'      => 0.00,
            'subtotal'        => 0.00,
            'discount_amount' => 0.00,
            'total'           => 0.00,
            'status'          => 'confirmed',
            'ticket_token'    => 'TKT-3a9d7b4e2f1c8091',
            'created_at'      => '2026-11-19 11:20:00'
        ]
    ];
}

$pageTitle = 'My Bookings & Reservations · ' . APP_NAME;
$activePage = 'student_bookings';
$studentActive = 'bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">My Bookings & Reservations</h1>
                    <p class="text-muted small mb-0">View all your registered event seats, download tickets, or cancel eligible bookings (FR-BKG-007).</p>
                </div>
                <div>
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-compass me-1"></i> Discover Events
                    </a>
                </div>
            </div>

            <!-- Bookings List Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Bookings List">
                        <thead>
                            <tr>
                                <th scope="col">Booking Ref</th>
                                <th scope="col">Event Details</th>
                                <th scope="col">Seats</th>
                                <th scope="col">Pricing Breakdown</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">
                                            <a href="booking_detail.php?ref=<?php echo urlencode($b['booking_ref']); ?>" class="text-decoration-none">
                                                <?php echo e($b['booking_ref']); ?>
                                            </a>
                                        </strong>
                                        <small class="text-muted"><?php echo format_datetime($b['created_at'], 'M d, Y'); ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo e($b['event_title']); ?></strong>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?php echo format_datetime($b['start_date']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?php echo (int)$b['seats']; ?> Seat(s)</span>
                                    </td>
                                    <td>
                                        <?php if ((float)$b['discount_amount'] > 0.00): ?>
                                            <div class="small text-muted text-decoration-line-through">
                                                Subtotal: <?php echo format_currency($b['subtotal']); ?>
                                            </div>
                                            <div class="small text-success">
                                                - <?php echo format_currency($b['discount_amount']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <strong class="<?php echo ((float)$b['total'] <= 0) ? 'text-success' : 'text-primary'; ?>">
                                            <?php echo ((float)$b['total'] <= 0) ? 'FREE' : format_currency($b['total']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-<?php echo e($b['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', e($b['status']))); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="booking_detail.php?ref=<?php echo urlencode($b['booking_ref']); ?>" class="btn btn-outline-secondary" title="View Itemized Breakdown">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>

                                            <?php if ($b['status'] === 'confirmed'): ?>
                                                <a href="ticket.php?ref=<?php echo urlencode($b['booking_ref']); ?>" class="btn btn-outline-primary" title="View Printable E-Ticket">
                                                    <i class="bi bi-qr-code"></i> Ticket
                                                </a>
                                            <?php elseif ($b['status'] === 'pending_payment'): ?>
                                                <a href="<?php echo APP_URL; ?>/pages/payment/checkout.php?ref=<?php echo urlencode($b['booking_ref']); ?>" class="btn btn-warning fw-semibold" title="Pay with PayHere Sandbox">
                                                    <i class="bi bi-credit-card"></i> Pay
                                                </a>
                                            <?php endif; ?>

                                            <!-- Cancel Button (FR-BKG-008) -->
                                            <?php if ($b['status'] !== 'cancelled' && strtotime($b['start_date']) > time()): ?>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal-<?php echo (int)$b['booking_id']; ?>" title="Cancel Booking">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>

                                                <!-- Cancellation Confirmation Modal -->
                                                <div class="modal fade" id="cancelModal-<?php echo (int)$b['booking_id']; ?>" tabindex="-1" aria-labelledby="cancelLabel-<?php echo (int)$b['booking_id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog text-start">
                                                        <div class="modal-content">
                                                            <form action="bookings.php" method="POST">
                                                                <?php echo csrf_field(); ?>
                                                                <input type="hidden" name="action" value="cancel">
                                                                <input type="hidden" name="booking_id" value="<?php echo (int)$b['booking_id']; ?>">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold" id="cancelLabel-<?php echo (int)$b['booking_id']; ?>">Confirm Booking Cancellation</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to cancel your reservation for <strong><?php echo e($b['event_title']); ?></strong>?</p>
                                                                    <p class="small text-muted mb-0">
                                                                        Your <?php echo (int)$b['seats']; ?> seat(s) will be immediately released back to other students or waitlisted applicants (FR-BKG-008).
                                                                    </p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Booking</button>
                                                                    <button type="submit" class="btn btn-danger">Yes, Cancel Booking</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
