<?php
/**
 * Eventora - Student Wishlist Page
 * FR-WISH-001, FR-WISH-002
 * Students can view, browse, and remove events from their saved wishlist.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/wishlist.php';

require_role('student');

$currentUser = current_user();
$userId = (int)$currentUser['user_id'];

// Handle POST: Remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $eventId = (int)($_POST['event_id'] ?? 0);
    if ($eventId > 0 && $_POST['action'] === 'remove') {
        $ok = remove_from_wishlist($userId, $eventId);
        if ($ok) {
            set_flash('success', 'Event removed from your wishlist.');
        } else {
            set_flash('error', 'Could not remove event from wishlist.');
        }
    }
    redirect(APP_URL . '/pages/student/wishlist.php');
}

$wishlistItems = get_student_wishlist($userId);

$pageTitle    = 'Saved Wishlist · ' . APP_NAME;
$activePage   = 'student_wishlist';
$studentActive = 'wishlist';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">

            <?php echo flash_messages(); ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Saved Events (Wishlist)</h1>
                    <p class="text-muted small mb-0">Events you have bookmarked to track availability and booking deadlines.</p>
                </div>
                <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-compass me-1"></i> Discover More Events
                </a>
            </div>

            <?php if (empty($wishlistItems)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-bookmark-heart display-4 text-muted mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark mb-2">Your wishlist is empty</h4>
                        <p class="text-muted small">Browse campus events and click the <i class="bi bi-bookmark"></i> icon to save them here for later.</p>
                        <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm mt-2">Browse Events</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php foreach ($wishlistItems as $item): ?>
                        <?php
                        $seatsAvail = (int)$item['seats_available'];
                        $isBooked   = (bool)(int)($item['is_booked'] ?? 0);
                        $isPast     = strtotime($item['start_date']) < time();
                        $isFull     = ($seatsAvail <= 0);
                        $hasWaitlist = (bool)(int)$item['waitlist_enabled'];

                        // Category CSS class
                        $catClass = 'badge-' . strtolower(preg_replace('/[^a-z0-9]/i', '', $item['category']));

                        // Availability badge
                        if ($isPast) {
                            $availBadge = '<span class="badge bg-secondary-subtle text-secondary border">Event Ended</span>';
                        } elseif ($isBooked) {
                            $availBadge = '<span class="badge bg-success-subtle text-success border"><i class="bi bi-check-circle me-1"></i>Booked</span>';
                        } elseif ($isFull && $hasWaitlist) {
                            $availBadge = '<span class="badge bg-warning-subtle text-warning border"><i class="bi bi-clock me-1"></i>Waitlist Available</span>';
                        } elseif ($isFull) {
                            $availBadge = '<span class="badge bg-danger-subtle text-danger border">Sold Out</span>';
                        } else {
                            $availBadge = '<span class="badge bg-success-subtle text-success border">' . $seatsAvail . ' seats left</span>';
                        }
                        ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge badge-category <?php echo $catClass; ?>"><?php echo e($item['category']); ?></span>
                                        <?php echo $availBadge; ?>
                                    </div>
                                    <h2 class="h5 fw-bold text-dark mb-2">
                                        <a href="<?php echo APP_URL; ?>/pages/event_detail.php?id=<?php echo (int)$item['event_id']; ?>" class="text-dark text-decoration-none">
                                            <?php echo e($item['title']); ?>
                                        </a>
                                    </h2>
                                    <p class="small text-muted mb-3 flex-grow-1"><?php echo e(substr($item['description'] ?? '', 0, 100)) . (strlen($item['description'] ?? '') > 100 ? '…' : ''); ?></p>

                                    <div class="small text-muted mb-3">
                                        <div><i class="bi bi-calendar3 me-1 text-primary"></i> <?php echo format_date($item['start_date'], 'D, M d, Y · h:i A'); ?></div>
                                        <div><i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo e($item['venue']); ?></div>
                                        <div>
                                            <i class="bi bi-tag me-1 text-secondary"></i>
                                            <?php echo (float)$item['price'] > 0 ? format_currency((float)$item['price']) : '<span class="text-success fw-semibold">Free</span>'; ?>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 pt-2 border-top">
                                        <?php if ($isBooked): ?>
                                            <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-success btn-sm flex-grow-1">
                                                <i class="bi bi-ticket-perforated me-1"></i> View Ticket
                                            </a>
                                        <?php elseif (!$isPast): ?>
                                            <a href="<?php echo APP_URL; ?>/pages/event_detail.php?id=<?php echo (int)$item['event_id']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                                <?php if ($isFull && $hasWaitlist): ?>
                                                    <i class="bi bi-clock me-1"></i> Join Waitlist
                                                <?php else: ?>
                                                    <i class="bi bi-calendar-check me-1"></i> Book Now
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="btn btn-outline-secondary btn-sm flex-grow-1 disabled">Event Ended</span>
                                        <?php endif; ?>

                                        <!-- Remove from Wishlist -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove this event from your wishlist?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="event_id" value="<?php echo (int)$item['event_id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove from wishlist">
                                                <i class="bi bi-bookmark-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-top-0 py-2 px-4">
                                    <small class="text-muted">
                                        <i class="bi bi-bookmark me-1"></i> Saved <?php echo format_date($item['saved_at'], 'M d, Y'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
