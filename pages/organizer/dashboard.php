<?php
/**
 * Eventora - Organizer Operations Hub
 * FR-ORG-005, FR-REP-002, FR-REP-003, FR-REP-005: Scoped to events owned by the organizer
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/events.php';
require_once __DIR__ . '/../../includes/reports.php';

require_role('organizer');

$pageTitle = 'Organizer Hub · ' . APP_NAME;
$activePage = 'org_dashboard';
$orgActive = 'dashboard';

$currentUser = current_user();
$orgProfile = get_organizer_profile((int)$currentUser['user_id']);
$orgProfileId = (int)($orgProfile['profile_id'] ?? 0);
$orgName = $orgProfile['organization_name'] ?? 'Organizer Hub';

// Fetch Organizer Dashboard KPIs (FR-REP-002)
$kpis = get_organizer_dashboard_kpis($orgProfileId);

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_organizer.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Organizer Operations Hub</h1>
                    <p class="text-muted small mb-0">Managing society events for <strong><?php echo e($orgName); ?></strong>.</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/pages/reports/export.php?type=org_bookings" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Bookings CSV
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/organizer/event_form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Submit New Event
                    </a>
                </div>
            </div>

            <!-- KPI Summary Cards (FR-REP-002) -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['total_events']; ?></h3>
                            <p>Total Events</p>
                        </div>
                        <div class="stat-card-icon stat-icon-primary">
                            <i class="bi bi-calendar3"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['seats_sold']; ?> / <?php echo (int)$kpis['total_capacity']; ?></h3>
                            <p>Seats Booked / Cap</p>
                        </div>
                        <div class="stat-card-icon stat-icon-info">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo format_currency($kpis['gross_revenue']); ?></h3>
                            <p>Gross Value</p>
                        </div>
                        <div class="stat-card-icon stat-icon-success">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo number_format($kpis['org_checkin_rate'], 1); ?>%</h3>
                            <p>Check-in Rate</p>
                        </div>
                        <div class="stat-card-icon stat-icon-warning">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action: Venue Check-in Banner -->
            <div class="card border-0 shadow-sm bg-primary text-white p-4 mb-4" style="border-radius: 1rem;">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <h2 class="h5 fw-bold mb-1 text-white"><i class="bi bi-upc-scan me-2"></i> Ready for Venue Check-in?</h2>
                        <p class="text-white-50 small mb-0">Launch the mobile QR scanner or manual token entry console to validate incoming attendees.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?php echo APP_URL; ?>/pages/organizer/checkin.php" class="btn btn-light text-primary fw-bold">
                            Open Venue Scanner <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Managed Events Table & Performance (FR-REP-002, FR-REP-005) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Event Performance & Roster</h2>
                    <a href="events.php" class="small fw-bold">Manage All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Organizer Events">
                        <thead>
                            <tr>
                                <th scope="col">Event Title</th>
                                <th scope="col">Category</th>
                                <th scope="col">Date</th>
                                <th scope="col">Booked / Cap</th>
                                <th scope="col">Price</th>
                                <th scope="col">Revenue</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kpis['events_performance'])): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No events found under this organizer profile.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kpis['events_performance'] as $evt): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark"><?php echo e($evt['title']); ?></strong>
                                            <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo e($evt['venue']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-category badge-<?php echo strtolower($evt['category']); ?>">
                                                <?php echo e($evt['category']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e(format_date($evt['start_date'])); ?></td>
                                        <td>
                                            <strong><?php echo (int)$evt['seats_booked']; ?></strong> / <?php echo (int)$evt['capacity']; ?> seats
                                            <div class="progress mt-1" style="height: 4px; width: 80px;">
                                                <?php 
                                                    $pct = $evt['capacity'] > 0 ? min(100, round(($evt['seats_booked'] / $evt['capacity']) * 100)) : 0;
                                                ?>
                                                <div class="progress-bar bg-primary" style="width: <?php echo $pct; ?>%"></div>
                                            </div>
                                        </td>
                                        <td><?php echo (float)$evt['price'] > 0 ? format_currency($evt['price']) : '<span class="badge bg-success-subtle text-success">FREE</span>'; ?></td>
                                        <td><strong><?php echo format_currency($evt['event_revenue']); ?></strong></td>
                                        <td>
                                            <?php if ($evt['status'] === 'published'): ?>
                                                <span class="badge badge-status badge-published">Published</span>
                                            <?php elseif ($evt['status'] === 'pending_approval'): ?>
                                                <span class="badge badge-status badge-pending">Pending Review</span>
                                            <?php elseif ($evt['status'] === 'completed'): ?>
                                                <span class="badge badge-status badge-confirmed">Completed</span>
                                            <?php else: ?>
                                                <span class="badge badge-status badge-draft"><?php echo e(ucfirst($evt['status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="event_form.php?id=<?php echo $evt['event_id']; ?>" class="btn btn-outline-secondary" title="Edit Event">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="checkin.php?event=<?php echo $evt['event_id']; ?>" class="btn btn-outline-primary" title="Check-in Scanner">
                                                    <i class="bi bi-qr-code-scan"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/pages/reports/export.php?type=attendees&event_id=<?php echo $evt['event_id']; ?>" class="btn btn-outline-success" title="Download Attendee Roster CSV">
                                                    <i class="bi bi-file-earmark-arrow-down"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Bookings Table (FR-REP-002) -->
            <?php if (!empty($kpis['recent_bookings'])): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Recent Event Bookings</h2>
                    <span class="badge bg-light text-muted border">Live Roster Activity</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Recent Bookings">
                        <thead>
                            <tr>
                                <th scope="col">Booking Ref</th>
                                <th scope="col">Student</th>
                                <th scope="col">Event</th>
                                <th scope="col">Seats</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kpis['recent_bookings'] as $bkg): ?>
                                <tr>
                                    <td><code><?php echo e($bkg['booking_ref']); ?></code></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo e($bkg['student_name']); ?></div>
                                        <small class="text-muted"><?php echo e($bkg['student_email']); ?></small>
                                    </td>
                                    <td><?php echo e($bkg['event_title']); ?></td>
                                    <td><?php echo (int)$bkg['seats']; ?></td>
                                    <td><strong><?php echo format_currency($bkg['total']); ?></strong></td>
                                    <td>
                                        <?php if ($bkg['status'] === 'confirmed'): ?>
                                            <span class="badge badge-status badge-confirmed">Confirmed</span>
                                        <?php elseif ($bkg['status'] === 'pending_payment'): ?>
                                            <span class="badge badge-status badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-status badge-cancelled"><?php echo e(ucfirst($bkg['status'])); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e(format_datetime($bkg['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
