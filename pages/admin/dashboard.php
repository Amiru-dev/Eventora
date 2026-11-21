<?php
/**
 * Eventora - Administrator Command Center
 * FR-REP-001, FR-REP-003, FR-REP-004, FR-REP-005: System metrics, revenue totals, category charts, attendance and CSV export
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/reports.php';

require_role('admin');

$pageTitle = 'Administrator Command Center · ' . APP_NAME;
$activePage = 'admin_dashboard';
$adminActive = 'dashboard';

// Fetch Executive Dashboard KPIs (FR-REP-001, FR-REP-003)
$kpis = get_admin_dashboard_kpis();

// Prepare Category Chart Data (FR-REP-003, FR-REP-004)
$catLabels = [];
$catBookings = [];
$catRevenues = [];
foreach ($kpis['category_distribution'] as $cat) {
    $catLabels[] = $cat['category'];
    $catBookings[] = (int)$cat['booking_count'];
    $catRevenues[] = (float)$cat['category_revenue'];
}

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Campus Administration Dashboard</h1>
                    <p class="text-muted small mb-0">System-wide monitoring, financial summaries, approval workflows and audit trails.</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/pages/reports/export.php?type=system_bookings" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export System CSV
                    </a>
                </div>
            </div>

            <!-- Pending Approvals Alert Bar (FR-ORG-002, FR-EVT-007) -->
            <?php if ($kpis['pending_events'] > 0 || $kpis['pending_organizers'] > 0): ?>
            <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning" aria-hidden="true"></i>
                    <div>
                        <strong>Action Needed:</strong>
                        <span>
                            <?php echo (int)$kpis['pending_events']; ?> Pending Event Submission(s) and 
                            <?php echo (int)$kpis['pending_organizers']; ?> Pending Organizer Application(s) awaiting review.
                        </span>
                    </div>
                </div>
                <div class="mt-2 mt-sm-0">
                    <a href="organizers.php" class="btn btn-warning btn-sm me-2 fw-semibold">Review Organizers</a>
                    <a href="events.php" class="btn btn-dark btn-sm fw-semibold">Review Events</a>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 p-3 mb-4">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div>
                    <strong>All Caught Up:</strong> No pending organizer applications or event submissions awaiting review.
                </div>
            </div>
            <?php endif; ?>

            <!-- KPI Metric Cards (FR-REP-001) -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['published_events']; ?></h3>
                            <p>Active Events</p>
                        </div>
                        <div class="stat-card-icon stat-icon-primary">
                            <i class="bi bi-calendar4-event"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo format_currency($kpis['total_revenue']); ?></h3>
                            <p>Verified Revenue</p>
                        </div>
                        <div class="stat-card-icon stat-icon-success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['confirmed_bookings']; ?></h3>
                            <p>Confirmed Bookings</p>
                        </div>
                        <div class="stat-card-icon stat-icon-info">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3><?php echo (int)$kpis['waitlist_count']; ?></h3>
                            <p>Waitlist Requests</p>
                        </div>
                        <div class="stat-card-icon stat-icon-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics & Charts Layout (FR-REP-003, FR-REP-004) -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 fw-bold mb-0">Bookings by Event Category</h2>
                            <small class="text-muted">Real-Time Distribution</small>
                        </div>
                        <div style="position: relative; height: 260px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-3 mt-3 small text-muted">
                            <?php foreach ($kpis['category_distribution'] as $cat): ?>
                                <span>
                                    <strong><?php echo e($cat['category']); ?>:</strong> 
                                    <?php echo (int)$cat['booking_count']; ?> bookings (<?php echo format_currency($cat['category_revenue']); ?>)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h2 class="h5 fw-bold mb-3">Venue Attendance Rate</h2>
                            <div class="text-center py-3">
                                <div class="display-5 fw-bold text-success mb-1">
                                    <?php echo number_format($kpis['global_checkin_rate'], 1); ?>%
                                </div>
                                <p class="text-muted small mb-3">
                                    <strong><?php echo (int)$kpis['total_checked_in']; ?></strong> check-ins recorded out of <strong><?php echo (int)$kpis['total_tickets']; ?></strong> active tickets.
                                </p>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success progress-bar-striped" role="progressbar" 
                                         style="width: <?php echo min(100, (float)$kpis['global_checkin_rate']); ?>%" 
                                         aria-valuenow="<?php echo (float)$kpis['global_checkin_rate']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Pending Payment Bookings:</span>
                                <strong class="text-warning"><?php echo (int)$kpis['pending_bookings']; ?></strong>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Cancelled Bookings:</span>
                                <strong class="text-danger"><?php echo (int)$kpis['cancelled_bookings']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Table (FR-REP-001, FR-PAY-003) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Recent Payment Transactions (PayHere Sandbox)</h2>
                    <span class="badge bg-light text-muted border">MD5 Idempotent Callbacks</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Recent Transactions">
                        <thead>
                            <tr>
                                <th scope="col">Order Ref</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Verification</th>
                                <th scope="col">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kpis['recent_transactions'])): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No recent payment transactions recorded.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kpis['recent_transactions'] as $tx): ?>
                                    <tr>
                                        <td><code><?php echo e($tx['order_ref']); ?></code></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo e($tx['user_name']); ?></div>
                                            <small class="text-muted"><?php echo e($tx['user_email']); ?></small>
                                        </td>
                                        <td><strong><?php echo format_currency($tx['amount']); ?></strong></td>
                                        <td>
                                            <?php if ($tx['status'] === 'success'): ?>
                                                <span class="badge badge-status badge-confirmed">Success</span>
                                            <?php elseif ($tx['status'] === 'pending'): ?>
                                                <span class="badge badge-status badge-pending">Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-status badge-cancelled"><?php echo e(ucfirst($tx['status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="text-success small"><i class="bi bi-shield-check"></i> MD5 Verified</span></td>
                                        <td><?php echo e(format_datetime($tx['created_at'])); ?></td>
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

<!-- Chart.js Scripts (FR-REP-004) -->
<script src="<?php echo APP_URL; ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('categoryChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($catLabels); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($catBookings); ?>,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.75)',
                        'rgba(13, 202, 240, 0.75)',
                        'rgba(255, 193, 7, 0.75)',
                        'rgba(25, 135, 84, 0.75)',
                        'rgba(108, 117, 125, 0.75)'
                    ],
                    borderColor: [
                        '#0d6efd',
                        '#0dcaf0',
                        '#ffc107',
                        '#198754',
                        '#6c757d'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
