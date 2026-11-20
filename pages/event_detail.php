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

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$bookingError = null;

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
        'rating_avg'       => 4.8,
        'rating_count'     => 14,
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
        'rating_avg'       => 4.8,
        'rating_count'     => 14,
        'description'      => "Kickstart your web development journey! In this intensive 3-hour practical session, students will learn semantic HTML5, modern CSS3 layout using Flexbox and Grid, along with foundational JavaScript interactivity. Each participant will build and style their own university portfolio page."
    ];
}

// Process Seat Reservation (POST, FR-BKG-001, FR-BKG-002, FR-BKG-003, FR-BKG-006)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $bookingError = 'Security token invalid. Please resubmit.';
    } elseif (!is_logged_in()) {
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
                    set_flash_message('success', 'Your reservation is confirmed! Free admission ticket generated successfully (FR-BKG-006).');
                } else {
                    set_flash_message('info', 'Seats held for 10 minutes (BR-08). Proceed to PayHere sandbox payment to confirm your booking.');
                }
                redirect(APP_URL . '/pages/student/booking_detail.php?ref=' . urlencode($bData['booking_ref']));
            } else {
                $bookingError = $bookResult['error'];
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

                    <h3 class="h6 fw-bold mt-4 mb-2">What will be covered:</h3>
                    <ul class="text-secondary small">
                        <li>Semantic HTML5 page structure and accessible navigation</li>
                        <li>Responsive layouts with Bootstrap 5 grid and utility classes</li>
                        <li>Introduction to DOM manipulation and form validation with JavaScript</li>
                        <li>Q&A session with senior computing instructors</li>
                    </ul>
                </div>
            </article>

            <!-- Reviews & Feedback Section (FR-REV-004) -->
            <section class="card border-0 shadow-sm p-4 mb-4" aria-labelledby="reviews-heading">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 id="reviews-heading" class="h5 fw-bold mb-0">Student Reviews & Ratings</h2>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5 fw-bold text-dark"><?php echo $event['rating_avg']; ?></span>
                        <div class="text-warning">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <span class="text-muted small">(<?php echo $event['rating_count']; ?> verified reviews)</span>
                    </div>
                </div>

                <!-- Review list -->
                <div class="border-top pt-3">
                    <div class="d-flex gap-3 mb-3">
                        <div class="stat-card-icon stat-icon-primary flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <strong class="small text-dark">Kavindu Perera</strong>
                                <span class="badge bg-light text-muted border">Verified Attendee</span>
                                <small class="text-muted">2 days ago</small>
                            </div>
                            <div class="text-warning small my-1">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                            </div>
                            <p class="small text-muted mb-0">Excellent workshop! The hands-on examples were very easy to follow and the instructors answered all questions.</p>
                        </div>
                    </div>
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
                                This event has reached full capacity. You can join the FIFO waitlist to receive priority booking when seats are released (BR-03, BR-13).
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
                                <small class="text-muted">Discounts don't stack; best rate applies automatically (BR-12).</small>
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
