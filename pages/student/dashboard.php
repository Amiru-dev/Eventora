<?php
/**
 * Eventora - Student Dashboard Layout
 * FR-REP-006: Shows booking summary metrics, upcoming registered events, quick actions
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/reports.php';

require_role('student');

$currentUser = current_user();
$pageTitle = 'Student Portal · ' . APP_NAME;
$activePage = 'student_dashboard';
$studentActive = 'dashboard';

// Fetch Student Dashboard KPIs (FR-REP-006)
$kpis = get_student_dashboard_kpis((int)$currentUser['user_id']);

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Student Sidebar Partial -->
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <!-- Dashboard Main Content -->
    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Student Dashboard</h1>
                    <p class="text-muted small mb-0">Welcome back, <strong><?php echo e($currentUser['name']); ?></strong>! Here is your campus event activity summary.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1" aria-hidden="true"></i> Find More Events
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards (FR-REP-006, 360px mobile responsive) -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['active_bookings']; ?></h3>
                            <p>Active Bookings</p>
                        </div>
                        <div class="stat-card-icon stat-icon-primary">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo format_currency($kpis['total_spent']); ?></h3>
                            <p>Total Spent</p>
                        </div>
                        <div class="stat-card-icon stat-icon-success">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['wishlist_count']; ?></h3>
                            <p>Saved in Wishlist</p>
                        </div>
                        <div class="stat-card-icon stat-icon-warning">
                            <i class="bi bi-heart"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['waitlist_count']; ?></h3>
                            <p>Waitlist Queue</p>
                        </div>
                        <div class="stat-card-icon stat-icon-info">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Confirmed Event Card -->
            <?php if (!empty($kpis['next_event'])): ?>
                <?php $next = $kpis['next_event']; ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <h2 class="h5 fw-bold mb-0">Next Confirmed Event</h2>
                        <span class="badge badge-status badge-confirmed">Confirmed</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-8">
                                <h3 class="h5 fw-bold text-dark mb-2"><?php echo e($next['title']); ?></h3>
                                <div class="text-muted small mb-2">
                                    <span class="me-3"><i class="bi bi-calendar-event me-1 text-primary"></i> <?php echo e(format_datetime($next['start_date'])); ?></span>
                                    <span><i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo e($next['venue']); ?></span>
                                </div>
                                <div class="small text-muted">
                                    Booking Ref: <strong><?php echo e($next['booking_ref']); ?></strong> · <?php echo (int)$next['seats']; ?> Seat(s) Reserved
                                    <?php if (!empty($next['ticket_token'])): ?>
                                        · Ticket Token: <code><?php echo e(substr($next['ticket_token'], 0, 10)); ?>...</code>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="<?php echo APP_URL; ?>/pages/student/ticket.php?ref=<?php echo urlencode($next['booking_ref']); ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-qr-code me-1"></i> View E-Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm mb-4 p-4 text-center">
                    <div class="py-3">
                        <i class="bi bi-calendar2-check fs-1 text-muted mb-2"></i>
                        <h3 class="h5 fw-bold">No Upcoming Confirmed Events</h3>
                        <p class="text-muted small mb-3">You don't have any confirmed registrations for upcoming events yet.</p>
                        <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-search me-1"></i> Browse Campus Events
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Recent Bookings Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Recent Bookings</h2>
                    <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="small fw-bold">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Recent Bookings">
                        <thead>
                            <tr>
                                <th scope="col">Reference</th>
                                <th scope="col">Event</th>
                                <th scope="col">Seats</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kpis['recent_bookings'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No booking records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kpis['recent_bookings'] as $bkg): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?php echo e($bkg['booking_ref']); ?></strong></td>
                                        <td><?php echo e($bkg['event_title']); ?></td>
                                        <td><?php echo (int)$bkg['seats']; ?></td>
                                        <td><strong><?php echo (float)$bkg['total'] > 0 ? format_currency($bkg['total']) : 'FREE'; ?></strong></td>
                                        <td>
                                            <?php if ($bkg['status'] === 'confirmed'): ?>
                                                <span class="badge badge-status badge-confirmed">Confirmed</span>
                                            <?php elseif ($bkg['status'] === 'pending_payment'): ?>
                                                <span class="badge badge-status badge-pending">Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-status badge-cancelled"><?php echo e(ucfirst($bkg['status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($bkg['status'] === 'confirmed'): ?>
                                                <a href="<?php echo APP_URL; ?>/pages/student/ticket.php?ref=<?php echo urlencode($bkg['booking_ref']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-qr-code"></i> E-Ticket
                                                </a>
                                            <?php elseif ($bkg['status'] === 'pending_payment'): ?>
                                                <a href="<?php echo APP_URL; ?>/pages/payment/checkout.php?ref=<?php echo urlencode($bkg['booking_ref']); ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-credit-card"></i> Pay Now
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
