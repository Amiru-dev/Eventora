<?php
/**
 * Eventora - Student Waitlist Page
 * FR-WAIT-001, FR-WAIT-002, FR-WAIT-003, FR-WAIT-004, FR-WAIT-005, BR-13
 * FIFO queue positions, offer acceptance/decline, leave queue actions.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/waitlist.php';

require_role('student');

$currentUser = current_user();
$userId = (int)$currentUser['user_id'];

// Handle POST actions (leave, accept, decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action  = trim($_POST['action'] ?? '');
    $eventId = (int)($_POST['event_id'] ?? 0);
    $entryId = (int)($_POST['entry_id'] ?? 0);

    if ($action === 'leave' && $eventId > 0) {
        $result = leave_waitlist($userId, $eventId);
        if ($result['success']) {
            set_flash('success', 'You have been removed from the waitlist.');
        } else {
            set_flash('error', $result['error'] ?? 'Could not leave waitlist.');
        }
    } elseif ($action === 'accept' && $entryId > 0) {
        $result = accept_waitlist_offer($userId, $entryId);
        if ($result['success']) {
            set_flash('success', 'Seat offer accepted! Proceed to book your seat.');
            redirect(APP_URL . '/pages/event_detail.php?id=' . $result['event_id']);
        } else {
            set_flash('error', $result['error'] ?? 'Could not accept offer.');
        }
    } elseif ($action === 'decline' && $entryId > 0) {
        $result = decline_waitlist_offer($userId, $entryId);
        if ($result['success']) {
            set_flash('info', 'Offer declined. The seat will be offered to the next person in queue.');
        } else {
            set_flash('error', $result['error'] ?? 'Could not decline offer.');
        }
    }
    redirect(APP_URL . '/pages/student/waitlist.php');
}

$waitlistEntries = get_student_waitlist($userId);

$pageTitle    = 'Waitlist Queue · ' . APP_NAME;
$activePage   = 'student_waitlist';
$studentActive = 'waitlist';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">

            <?php echo flash_messages(); ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Waitlist Queue Positions</h1>
                    <p class="text-muted small mb-0">Track your FIFO queue positions for sold-out campus events.</p>
                </div>
                <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-compass me-1"></i> Discover Events
                </a>
            </div>

            <?php if (empty($waitlistEntries)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-clock-history display-4 text-muted mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark mb-2">You are not on any waitlist</h4>
                        <p class="text-muted small">When an event is sold out and has waitlisting enabled, you can join the queue from the event page.</p>
                        <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm mt-2">Browse Events</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0" aria-label="Waitlist Queue">
                            <thead>
                                <tr>
                                    <th scope="col">Event</th>
                                    <th scope="col">Queue Position</th>
                                    <th scope="col">Seats</th>
                                    <th scope="col">Joined On</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waitlistEntries as $entry): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">
                                                <a href="<?php echo APP_URL; ?>/pages/event_detail.php?id=<?php echo (int)$entry['event_id']; ?>" class="text-dark text-decoration-none">
                                                    <?php echo e($entry['event_title']); ?>
                                                </a>
                                            </strong>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php echo format_date($entry['start_date'], 'M d, Y'); ?>
                                                &nbsp;·&nbsp;
                                                <i class="bi bi-geo-alt me-1"></i>
                                                <?php echo e($entry['venue']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6 px-3">
                                                # <?php echo (int)$entry['position']; ?> in queue
                                            </span>
                                        </td>
                                        <td><?php echo (int)$entry['seats_requested']; ?> <?php echo (int)$entry['seats_requested'] === 1 ? 'Seat' : 'Seats'; ?></td>
                                        <td class="small text-muted"><?php echo format_date($entry['created_at'], 'M d, Y H:i'); ?></td>
                                        <td>
                                            <?php if ($entry['status'] === 'offered'): ?>
                                                <?php
                                                $expiresAt = $entry['offer_expires_at'];
                                                $minutesLeft = $expiresAt ? max(0, (int)((strtotime($expiresAt) - time()) / 60)) : 0;
                                                ?>
                                                <span class="badge bg-warning text-dark border">
                                                    <i class="bi bi-bell-fill me-1"></i> Seat Offered!
                                                </span>
                                                <?php if ($expiresAt): ?>
                                                    <div class="small text-danger mt-1">
                                                        <i class="bi bi-clock me-1"></i>
                                                        Expires in <?php echo $minutesLeft; ?> min
                                                        (<?php echo format_date($expiresAt, 'h:i A'); ?>)
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge badge-status badge-pending_payment">Waiting for Release</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($entry['status'] === 'offered'): ?>
                                                <!-- Accept Offer -->
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Accept this seat offer and proceed to booking?')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="accept">
                                                    <input type="hidden" name="entry_id" value="<?php echo (int)$entry['entry_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                                        <i class="bi bi-check-circle me-1"></i> Accept Seat
                                                    </button>
                                                </form>
                                                <!-- Decline Offer -->
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Decline this offer? The seat will pass to the next person in queue.')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="decline">
                                                    <input type="hidden" name="entry_id" value="<?php echo (int)$entry['entry_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning ms-1">
                                                        <i class="bi bi-x me-1"></i> Decline
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Leave Waitlist -->
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Leave the waitlist for this event?')">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="leave">
                                                    <input type="hidden" name="event_id" value="<?php echo (int)$entry['event_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-box-arrow-left me-1"></i> Leave Queue
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="alert alert-light border mt-4 p-3 small text-muted">
                <i class="bi bi-info-circle text-primary me-1"></i>
                <strong>How the Waitlist works:</strong> When a registered student cancels or the organizer expands capacity,
                the system automatically offers the seat to the <strong>next student in FIFO sequence</strong>.
                You will have <strong><?php echo WAITLIST_OFFER_MINUTES; ?> minutes</strong> to accept before the offer passes to the next person.
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
