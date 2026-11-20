<?php
/**
 * Eventora - Administrator Command Center
 * FR-REP-001, FR-REP-004: Event counts, revenue totals, payment statuses, attendance and waitlists
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Administrator Command Center · ' . APP_NAME;
$activePage = 'admin_dashboard';
$adminActive = 'dashboard';

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
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export System CSV (FR-REP-005)
                    </button>
                </div>
            </div>

            <!-- Pending Approvals Alert Bar (FR-ORG-002, FR-EVT-007) -->
            <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning" aria-hidden="true"></i>
                    <div>
                        <strong>Action Needed:</strong>
                        <span>2 Pending Event Submissions and 1 Pending Organizer Application awaiting review.</span>
                    </div>
                </div>
                <div class="mt-2 mt-sm-0">
                    <a href="organizers.php" class="btn btn-warning btn-sm me-2 fw-semibold">Review Organizers</a>
                    <a href="events.php" class="btn btn-dark btn-sm fw-semibold">Review Events</a>
                </div>
            </div>

            <!-- KPI Metric Cards (FR-REP-001) -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>6</h3>
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
                            <h3>LKR 128.5K</h3>
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
                            <h3>142</h3>
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
                            <h3>12</h3>
                            <p>Waitlist Requests</p>
                        </div>
                        <div class="stat-card-icon stat-icon-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics & Charts Layout Preview (FR-REP-004) -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 fw-bold mb-0">Bookings by Event Category</h2>
                            <small class="text-muted">Current Semester Distribution</small>
                        </div>
                        <!-- Visual representation of categories -->
                        <div class="progress-stacked mb-3" style="height: 25px;">
                            <div class="progress" role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100" title="Workshops (35%)">
                                <div class="progress-bar bg-primary">Workshops 35%</div>
                            </div>
                            <div class="progress" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" title="Tech (25%)">
                                <div class="progress-bar bg-info">Tech 25%</div>
                            </div>
                            <div class="progress" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" title="Seminars (20%)">
                                <div class="progress-bar bg-warning text-dark">Seminars 20%</div>
                            </div>
                            <div class="progress" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" title="Cultural & Sports (20%)">
                                <div class="progress-bar bg-success">Cultural 20%</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span><i class="bi bi-circle-fill text-primary me-1"></i> Workshops</span>
                            <span><i class="bi bi-circle-fill text-info me-1"></i> Tech</span>
                            <span><i class="bi bi-circle-fill text-warning me-1"></i> Seminars</span>
                            <span><i class="bi bi-circle-fill text-success me-1"></i> Cultural / Sports</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4">
                        <h2 class="h5 fw-bold mb-3">Venue Attendance Rate</h2>
                        <div class="text-center py-2">
                            <div class="display-5 fw-bold text-success mb-1">87.4%</div>
                            <p class="text-muted small mb-3">Average ticket check-in rate across 18 campus sessions.</p>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 87.4%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Recent Payment Transactions (PayHere Sandbox)</h2>
                    <span class="badge bg-light text-muted border">Idempotent Callbacks</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Recent Transactions">
                        <thead>
                            <tr>
                                <th scope="col">Order Ref</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Amount (LKR)</th>
                                <th scope="col">Status</th>
                                <th scope="col">Hash Verification</th>
                                <th scope="col">Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>ORD-2026-98124</code></td>
                                <td>Test Student (student@eventora.com)</td>
                                <td>LKR 500.00</td>
                                <td><span class="badge badge-status badge-confirmed">Success</span></td>
                                <td><span class="text-success small"><i class="bi bi-shield-check"></i> MD5 Verified</span></td>
                                <td>2026-11-20 15:30</td>
                            </tr>
                            <tr>
                                <td><code>ORD-2026-98123</code></td>
                                <td>Kasun Perera (kasun@niibs.lk)</td>
                                <td>LKR 1,000.00</td>
                                <td><span class="badge badge-status badge-confirmed">Success</span></td>
                                <td><span class="text-success small"><i class="bi bi-shield-check"></i> MD5 Verified</span></td>
                                <td>2026-11-20 14:10</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
