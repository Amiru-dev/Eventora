<?php
/**
 * Eventora - Public Event Detail Page
 * FR-EVT-005, FR-BKG-001, FR-BKG-002, FR-BKG-003, FR-BKG-006, FR-REV-004
 * Shows full event description, capacity, pricing calculation, reviews and booking widget
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/events.php';
require_once __DIR__ . '/../includes/bookings.php';
require_once __DIR__ . '/../includes/reviews.php';

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$bookingError = null;
$reviewError = null;

// Rating and Reviews data (FR-REV-005)
$ratingSummary = get_event_rating_summary($eventId);
$reviewsList   = get_event_reviews($eventId);

// Load event from database or fallback mock
$dbEvent = get_event_by_id($eventId);
if ($dbEvent) {
    $event = [
        'id'               => (int)$dbEvent['event_id'],
        'title'            => $dbEvent['title'],
        'category'         => $dbEvent['category'],
        'venue'            => $dbEvent['venue'],
        'start_date'       => $dbEvent['start_date'],
        'end_date'         => $dbEvent['end_date'],
        'price'            => (float)$dbEvent['price'],
        'capacity'         => (int)$dbEvent['capacity'],
        'seats_available'  => (int)$dbEvent['seats_available'],
        'organizer'        => $dbEvent['organizer_name'] ?? 'University Society',
        'organizer_bio'    => $dbEvent['organizer_bio'] ?? 'Official student organization.',
        'max_per_student'  => (int)$dbEvent['max_per_student'],
        'waitlist_enabled' => (int)$dbEvent['waitlist_enabled'],
        'rating_avg'       => $ratingSummary['average_rating'],
        'rating_count'     => $ratingSummary['total_reviews'],
        'description'      => $dbEvent['description']
    ];
} else {
    $event = [
        'id'               => $eventId,
        'title'            => 'Introduction to Web Development Workshop',
        'category'         => 'Workshop',
        'venue'            => 'Lab 201, IT Building, NIIBS Campus',
        'start_date'       => '2026-11-27 10:00:00',
        'end_date'         => '2026-11-27 13:00:00',
        'price'            => 500.00,
        'capacity'         => 40,
        'seats_available'  => 32,
        'organizer'        => 'NIIBS Tech Club',
        'organizer_bio'    => 'Official student organization promoting modern software development practices.',
        'max_per_student'  => 5,
        'waitlist_enabled' => 1,
        'rating_avg'       => $ratingSummary['average_rating'],
        'rating_count'     => $ratingSummary['total_reviews'],
        'description'      => "Kickstart your web development journey! In this intensive 3-hour practical session, students will learn semantic HTML5, modern CSS3 layout using Flexbox and Grid, along with foundational JavaScript interactivity. Each participant will build and style their own university portfolio page."
    ];
}

$currentUser = is_logged_in() ? current_user() : null;
$currentUserId = $currentUser ? (int)($currentUser['user_id'] ?? $currentUser['id'] ?? 0) : null;
$reviewEligibility = $currentUserId ? can_user_review_event($currentUserId, $eventId) : null;

// Process POST Actions (Booking, Review Submission, Review Moderation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $bookingError = 'Security token invalid. Please resubmit.';
    } else {
        $formAction = trim($_POST['form_action'] ?? 'book');

        // Review Submission (FR-REV-001)
        if ($formAction === 'submit_review') {
            if (!$currentUser) {
                set_flash_message('info', 'Please sign in to submit a review.');
                redirect(APP_URL . '/pages/auth/login.php?return=' . urlencode('pages/event_detail.php?id=' . $eventId));
            } else {
                $rating  = (int)($_POST['rating'] ?? 5);
                $comment = trim($_POST['comment'] ?? '');

                $revResult = submit_event_review($currentUserId, $eventId, $rating, $comment);
                if ($revResult['success']) {
                    set_flash_message('success', 'Thank you! Your verified rating and review have been submitted.');
                } else {
                    set_flash_message('error', $revResult['error'] ?? 'Could not submit review.');
                }
                redirect(APP_URL . '/pages/event_detail.php?id=' . $eventId . '#reviews-heading');
            }
        }

        // Review Moderation (BR-17)
        if ($formAction === 'toggle_review') {
            if ($currentUser && $currentUser['role'] === 'admin') {
                $revId = (int)($_POST['review_id'] ?? 0);
                if ($revId > 0) {
                    toggle_review_visibility($revId);
                    set_flash_message('info', 'Review visibility updated.');
                }
            }
            redirect(APP_URL . '/pages/event_detail.php?id=' . $eventId . '#reviews-heading');
        }

        // Seat Reservation (FR-BKG-001)
        if (!is_logged_in()) {
            set_flash_message('info', 'Please sign in with your student account to reserve seats.');
            redirect(APP_URL . '/pages/auth/login.php?return=' . urlencode('pages/event_detail.php?id=' . $eventId));
        } else {
            $user = current_user();
            if ($user['role'] !== 'student' && $user['role'] !== 'admin') {
                $bookingError = 'Only registered students may book event seats.';
            } else {
                $requestedSeats = (int)($_POST['seats'] ?? 1);
                $promoCode = trim($_POST['promo'] ?? '');

                $bookResult = create_booking((int)$user['id'], $eventId, $requestedSeats, $promoCode);
                if ($bookResult['success']) {
                    $bData = $bookResult['booking'];
                    if ($bData['is_free']) {
                        set_flash_message('success', 'Your reservation is confirmed! Free admission ticket generated successfully.');
                    } else {
                        set_flash_message('info', 'Seats held for 10 minutes. Proceed to PayHere sandbox payment to confirm your booking.');
                    }
                    redirect(APP_URL . '/pages/student/booking_detail.php?ref=' . urlencode($bData['booking_ref']));
                } else {
                    $bookingError = $bookResult['error'];
                }
            }
        }
    }
}

$pageTitle = e($event['title']) . ' · ' . APP_NAME;
$activePage = 'events';

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/index.php">Events</a></li>
            <li class="breadcrumb-item"><span class="badge bg-light text-muted border"><?php echo htmlspecialchars($event['category']); ?></span></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($event['title']); ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Event Main Content Column -->
        <div class="col-lg-8">
            <article class="card border-0 shadow-sm overflow-hidden mb-4">
                <!-- Event Visual Header -->
                <div class="event-card-img-wrapper" style="height: 260px;">
                    <i class="bi bi-laptop display-1" aria-hidden="true"></i>
                    <span class="event-card-badge badge-category badge-workshop">
                        <?php echo htmlspecialchars($event['category']); ?>
                    </span>
                    <div class="event-price-tag fs-6">
                        LKR <?php echo number_format($event['price'], 2); ?> per seat
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <h1 class="h2 fw-bold mb-0"><?php echo htmlspecialchars($event['title']); ?></h1>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i> Approved & Published
                        </span>
                    </div>

                    <!-- Meta Tags Grid -->
                    <div class="row g-3 my-2 p-3 bg-light rounded-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-check text-primary fs-5" aria-hidden="true"></i>
                                <div>
                                    <small class="text-muted d-block">Date & Time</small>
                                    <strong class="text-dark">Friday, Nov 27, 2026</strong>
                                    <div class="small text-muted">10:00 AM – 01:00 PM (Asia/Colombo)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-geo-alt-fill text-danger fs-5" aria-hidden="true"></i>
                                <div>
                                    <small class="text-muted d-block">Location Venue</small>
                                    <strong class="text-dark"><?php echo htmlspecialchars($event['venue']); ?></strong>
                                    <div class="small text-muted">Main Campus Building</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people-fill text-info fs-5" aria-hidden="true"></i>
                                <div>
                                    <small class="text-muted d-block">Seat Capacity</small>
                                    <strong class="text-dark"><?php echo $event['seats_available']; ?> seats available</strong>
                                    <div class="small text-muted">Total room limit: <?php echo $event['capacity']; ?> seats</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check text-success fs-5" aria-hidden="true"></i>
                                <div>
                                    <small class="text-muted d-block">Organized By</small>
                                    <strong class="text-dark"><?php echo htmlspecialchars($event['organizer']); ?></strong>
                                    <div class="small text-muted">Verified University Entity</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="h5 fw-bold mt-4 mb-3">About this Event</h2>
                    <p class="lead fs-6 text-secondary" style="line-height: 1.7;">
                        <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                    </p>
                </div>
            </article>

            <!-- Reviews & Feedback Section (FR-REV-001 to FR-REV-005, BR-17) -->
            <section class="card border-0 shadow-sm p-4 mb-4" id="reviews-heading" aria-labelledby="reviews-title">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <h2 id="reviews-title" class="h5 fw-bold mb-0">Student Reviews & Ratings</h2>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-4 fw-bold text-dark"><?php echo number_format((float)$ratingSummary['average_rating'], 1); ?></span>
                        <div class="text-warning">
                            <?php 
                            $starsCount = round((float)$ratingSummary['average_rating']);
                            for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= $starsCount ? '-fill' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted small">(<?php echo (int)$ratingSummary['total_reviews']; ?> <?php echo (int)$ratingSummary['total_reviews'] === 1 ? 'review' : 'reviews'; ?>)</span>
                    </div>
                </div>

                <!-- Rating Breakdown Bars (FR-REV-005) -->
                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="row g-2 align-items-center">
                        <?php for ($s = 5; $s >= 1; $s--): 
                            $pct = $ratingSummary['percentages'][$s] ?? 0;
                            $count = $ratingSummary['breakdown'][$s] ?? 0;
                        ?>
                            <div class="col-2 small text-muted text-end"><?php echo $s; ?> <i class="bi bi-star-fill text-warning"></i></div>
                            <div class="col-8">
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="col-2 small text-muted"><?php echo $count; ?></div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Review Eligibility & Submission Form (FR-REV-001, FR-REV-002, FR-REV-003, BR-17) -->
                <?php if ($currentUser && $reviewEligibility && $reviewEligibility['can_review']): ?>
                    <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-4">
                        <div class="card-body p-3">
                            <h3 class="h6 fw-bold text-primary mb-2"><i class="bi bi-pencil-square me-1"></i> Write a Verified Review</h3>
                            <form action="event_detail.php?id=<?php echo $eventId; ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="form_action" value="submit_review">

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Your Rating (1-5 Stars)</label>
                                    <div class="d-flex gap-3">
                                        <?php for ($r = 5; $r >= 1; $r--): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="rating" id="rating-<?php echo $r; ?>" value="<?php echo $r; ?>" <?php echo $r === 5 ? 'checked' : ''; ?> required>
                                                <label class="form-check-label text-warning" for="rating-<?php echo $r; ?>">
                                                    <?php echo $r; ?> <i class="bi bi-star-fill"></i>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="review_comment" class="form-label small fw-semibold">Your Feedback (Optional, Max 1000 characters)</label>
                                    <textarea class="form-control" id="review_comment" name="comment" rows="3" maxlength="1000" placeholder="Share your experience, highlights, and feedback for fellow students..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-send me-1"></i> Submit Verified Review
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($currentUser && $reviewEligibility && $reviewEligibility['reason'] === 'already_reviewed'): ?>
                    <div class="alert alert-success small mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>
                            <strong>You reviewed this event:</strong> <?php echo (int)$reviewEligibility['existing_review']['rating']; ?> <i class="bi bi-star-fill text-warning"></i>
                            <?php if (!empty($reviewEligibility['existing_review']['comment'])): ?>
                                — "<?php echo e($reviewEligibility['existing_review']['comment']); ?>"
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($currentUser && $reviewEligibility && !$reviewEligibility['can_review']): ?>
                    <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary fs-5"></i>
                        <div><?php echo e($reviewEligibility['message']); ?></div>
                    </div>
                <?php elseif (!$currentUser): ?>
                    <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-lock me-1"></i> Attend this event? Sign in with your student account to write a review.</span>
                        <a href="<?php echo APP_URL; ?>/pages/auth/login.php?return=<?php echo urlencode('pages/event_detail.php?id=' . $eventId); ?>" class="btn btn-outline-primary btn-sm">Sign In</a>
                    </div>
                <?php endif; ?>

                <!-- Reviews List -->
                <div class="border-top pt-3">
                    <?php if (empty($reviewsList)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-chat-square-text fs-3 d-block mb-1"></i> No reviews written yet. Be the first verified attendee to review this event!
                        </div>
                    <?php else: ?>
                        <?php foreach ($reviewsList as $rev): 
                            $rStars = (int)$rev['rating'];
                            $isVis  = ((int)$rev['is_visible'] === 1);
                        ?>
                            <div class="d-flex gap-3 mb-4 pb-3 border-bottom <?php echo !$isVis ? 'opacity-50' : ''; ?>">
                                <div class="stat-card-icon stat-icon-primary flex-shrink-0" style="width: 40px; height: 40px;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="small text-dark"><?php echo e($rev['user_name']); ?></strong>
                                            <?php if (!empty($rev['is_verified_attendee'])): ?>
                                                <span class="badge bg-success-subtle text-success border" style="font-size: 0.65rem;">
                                                    <i class="bi bi-patch-check-fill me-1"></i> Verified Attendee
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!$isVis): ?>
                                                <span class="badge bg-danger-subtle text-danger border" style="font-size: 0.65rem;">Hidden by Moderator</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo format_date($rev['created_at'], 'M d, Y'); ?></small>
                                    </div>
                                    <div class="text-warning small my-1">
                                        <?php for ($k = 1; $k <= 5; $k++): ?>
                                            <i class="bi bi-star<?php echo $k <= $rStars ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($rev['comment'])): ?>
                                        <p class="small text-muted mb-0"><?php echo nl2br(e($rev['comment'])); ?></p>
                                    <?php endif; ?>

                                    <!-- Admin Moderation Button (BR-17) -->
                                    <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                                        <form method="POST" class="mt-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="form_action" value="toggle_review">
                                            <input type="hidden" name="review_id" value="<?php echo (int)$rev['review_id']; ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.7rem;">
                                                <i class="bi bi-eye-slash me-1"></i> <?php echo $isVis ? 'Moderate / Hide' : 'Restore Visibility'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Booking Widget Sidebar Column (FR-BKG-001, FR-BKG-002) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 90px; border-radius: 1rem;">
                <div class="card-header bg-white border-bottom p-3">
                    <h2 class="h5 fw-bold mb-0">Reserve Your Seat</h2>
                    <small class="text-muted">Instant e-ticket upon successful confirmation</small>
                </div>
                <div class="card-body p-4">
                    <?php if ($bookingError): ?>
                        <div class="alert alert-danger shadow-sm mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
                            <span class="small"><?php echo e($bookingError); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($event['seats_available'] <= 0): ?>
                        <div class="text-center py-4">
                            <span class="badge bg-danger fs-6 px-3 py-2 mb-3">SOLD OUT</span>
                            <h3 class="h5 fw-bold text-dark mb-2">All Seats Have Been Reserved</h3>
                            <p class="small text-muted mb-4">
                                This event has reached full capacity. You can join the FIFO waitlist to receive priority booking when seats are released.
                            </p>
                            <a href="<?php echo APP_URL; ?>/pages/student/waitlist.php?join=<?php echo $eventId; ?>" class="btn btn-warning w-100 fw-bold">
                                <i class="bi bi-hourglass-split me-1"></i> Join Waitlist Queue (FIFO)
                            </a>
                        </div>
                    <?php else: ?>
                        <form id="bookingForm" action="event_detail.php?id=<?php echo $eventId; ?>" method="POST" class="needs-validation" data-unit-price="<?php echo $event['price']; ?>">
                            <!-- CSRF Protection (FR-VAL-003) -->
                            <?php echo csrf_field(); ?>

                            <!-- Seat Quantity (FR-BKG-001) -->
                            <div class="mb-3">
                                <label for="seat-quantity" class="form-label d-flex justify-content-between">
                                    <span>Number of Seats</span>
                                    <span class="text-muted small">Max <?php echo $event['max_per_student']; ?> seats</span>
                                </label>
                                <input type="number" id="seat-quantity" name="seats" class="form-control form-control-lg text-center fw-bold" min="1" max="<?php echo min($event['seats_available'], $event['max_per_student']); ?>" value="1" required>
                                <div class="invalid-feedback">Please select valid quantity (1 - <?php echo min($event['seats_available'], $event['max_per_student']); ?>).</div>
                            </div>

                            <!-- Promo Code (FR-PROMO-003, BR-11) -->
                            <div class="mb-3">
                                <label for="promo-input" class="form-label">Promo Code (Optional)</label>
                                <div class="input-group">
                                    <input type="text" id="promo-input" name="promo" class="form-control text-uppercase" placeholder="e.g. WELCOME10">
                                    <button type="button" class="btn btn-outline-secondary">Apply</button>
                                </div>
                                <small class="text-muted">Discounts don't stack; best rate applies automatically.</small>
                            </div>

                            <!-- Price Breakdown Summary (FR-BKG-002, BR-05, BR-09) -->
                            <div class="bg-light p-3 rounded-3 mb-4">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Seat Subtotal:</span>
                                    <span>LKR <span id="subtotal-val"><?php echo number_format($event['price'], 2); ?></span></span>
                                </div>
                                <div class="d-flex justify-content-between small text-success mb-2">
                                    <span>Bulk / Promo Discount:</span>
                                    <span>- LKR <span id="discount-val">0.00</span></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between fw-bold text-dark fs-5">
                                    <span>Total Payable:</span>
                                    <span class="text-primary">LKR <span id="total-val"><?php echo number_format($event['price'], 2); ?></span></span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-credit-card-2-front me-2" aria-hidden="true"></i> Proceed to Book
                                </button>
                                <a href="<?php echo APP_URL; ?>/pages/student/wishlist.php?add=<?php echo $eventId; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-heart me-1" aria-hidden="true"></i> Add to Wishlist
                                </a>
                            </div>

                            <div class="text-center mt-3 small text-muted">
                                <i class="bi bi-shield-lock-fill text-success me-1" aria-hidden="true"></i> Secure Checkout via PayHere Sandbox
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
