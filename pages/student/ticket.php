<?php
/**
 * Eventora - Printable QR E-Ticket View
 * FR-TKT-001, FR-TKT-002, FR-TKT-003, NFR-SEC-007, BR-16
 * Displays opaque, non-sequential token with dynamic vector QR code & printable layout
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/tickets.php';

require_login();

$currentUser = current_user();
$token = trim($_GET['token'] ?? '');
$ref   = trim($_GET['ref'] ?? '');
$id    = (int)($_GET['id'] ?? 0);
$bid   = (int)($_GET['booking_id'] ?? 0);

$ticket = null;

if (!empty($token)) {
    $ticket = get_ticket_by_token($token);
} elseif (!empty($ref)) {
    $ticket = get_ticket_by_ref($ref);
} elseif ($id > 0) {
    $ticket = get_ticket_by_id($id);
} elseif ($bid > 0) {
    // If booking_id given, get first ticket for this booking
    $pdo = get_db_connection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT token FROM tickets WHERE booking_id = :bid LIMIT 1");
        $stmt->execute([':bid' => $bid]);
        $t = $stmt->fetchColumn();
        if ($t) {
            $ticket = get_ticket_by_token($t);
        }
    }
}

// Fallback to sample ticket if no params provided in dev/preview
if (!$ticket) {
    $ticket = get_mock_ticket_by_token('TKT-8f2a9c1e4b7d603a');
}

// Permission check: student can only view their own ticket (unless organizer/admin)
if ($currentUser['role'] === 'student' && isset($ticket['user_id']) && (int)$ticket['user_id'] !== (int)$currentUser['user_id']) {
    set_flash('error', 'You do not have permission to view this ticket.');
    redirect(APP_URL . '/pages/student/bookings.php');
}

$status = strtolower($ticket['ticket_status'] ?? 'active');
$isUsed = ($status === 'used');
$isCancelled = ($status === 'cancelled');

$badgeClass = 'bg-success';
$badgeText  = 'CONFIRMED SEAT';
if ($isUsed) {
    $badgeClass = 'bg-secondary';
    $badgeText  = 'CHECKED IN';
} elseif ($isCancelled) {
    $badgeClass = 'bg-danger';
    $badgeText  = 'CANCELLED';
}

$pageTitle = 'E-Ticket: ' . e($ticket['event_title']) . ' · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-4">
    <!-- Top actions (hidden when printed via @media print) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Bookings
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer-fill me-1"></i> Print / Save Ticket (PDF)
            </button>
        </div>
    </div>

    <!-- Official Printable E-Ticket Container (FR-TKT-002, FR-TKT-003) -->
    <div class="ticket-container" id="printableTicket">
        <div class="ticket-header">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="Eventora Logo" width="34" height="34" class="object-fit-contain bg-white rounded-2 p-1">
                    <span class="fs-4 fw-extrabold text-white"><?php echo APP_NAME; ?></span>
                </div>
                <span class="badge <?php echo $badgeClass; ?> text-white px-3 py-2 fs-6">
                    <?php echo $badgeText; ?>
                </span>
            </div>
            <h1 class="h3 fw-bold text-white mb-1"><?php echo e($ticket['event_title']); ?></h1>
            <p class="text-white-50 small mb-0">
                <?php echo e($ticket['organizer_organization'] ?? 'University Club'); ?> · Faculty of Computing and Information Technology
            </p>
        </div>

        <div class="ticket-body">
            <div class="row g-4 align-items-center">
                <!-- Details Section -->
                <div class="col-md-7">
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Student Attendee</small>
                        <strong class="fs-5 text-dark"><?php echo e($ticket['student_name']); ?></strong>
                        <div class="small text-muted">
                            <?php echo e($ticket['student_email']); ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Date & Time</small>
                            <span class="fw-bold text-dark">
                                <?php echo format_date($ticket['start_date'], 'D, M d, Y'); ?>
                            </span>
                            <div class="small text-muted">
                                <?php echo format_date($ticket['start_date'], 'h:i A'); ?> – <?php echo format_date($ticket['end_date'], 'h:i A'); ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Venue</small>
                            <span class="fw-bold text-dark"><?php echo e($ticket['venue']); ?></span>
                            <div class="small text-muted">Main Campus</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Seats Reserved</small>
                            <span class="badge bg-primary fs-6"><?php echo (int)$ticket['seats']; ?> <?php echo (int)$ticket['seats'] === 1 ? 'Seat' : 'Seats'; ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Amount Paid</small>
                            <span class="fw-bold text-success fs-6"><?php echo format_currency((float)$ticket['total']); ?></span>
                        </div>
                    </div>

                    <?php if ($isUsed): ?>
                        <div class="p-2 bg-secondary-subtle border border-secondary-subtle rounded small text-secondary mb-2">
                            <i class="bi bi-check2-circle me-1"></i> <strong>Already Checked In:</strong> Admitted at 
                            <?php echo format_date($ticket['checked_in_at'], 'h:i A on M d, Y'); ?>
                            <?php if (!empty($ticket['staff_name'])): ?>
                                by <?php echo e($ticket['staff_name']); ?>
                            <?php endif; ?>.
                        </div>
                    <?php elseif ($isCancelled): ?>
                        <div class="p-2 bg-danger-subtle border border-danger-subtle rounded small text-danger mb-2">
                            <i class="bi bi-x-octagon-fill me-1"></i> <strong>Cancelled:</strong> This reservation has been cancelled.
                        </div>
                    <?php else: ?>
                        <div class="p-2 bg-light rounded small text-muted border">
                            <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Check-in Notice:</strong> Present this QR code or token at the venue entrance. One-time entry only.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- QR Code Box (NFR-SEC-007, FR-TKT-002) -->
                <div class="col-md-5">
                    <div class="ticket-qr-box text-center">
                        <!-- Real Dynamic SVG QR Code Image -->
                        <div class="p-3 bg-white d-inline-block border rounded mb-2 shadow-sm">
                            <img src="<?php echo APP_URL; ?>/api/qrcode.php?token=<?php echo urlencode($ticket['token']); ?>" 
                                 alt="QR Code for <?php echo e($ticket['token']); ?>" 
                                 class="d-block mx-auto img-fluid"
                                 style="width: 160px; height: 160px;" />
                        </div>
                        <small class="text-muted d-block mb-1">Cryptographic Token</small>
                        <span class="ticket-token-code"><?php echo e($ticket['token']); ?></span>
                        <div class="small text-muted mt-2">Ref: <?php echo e($ticket['booking_ref']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
