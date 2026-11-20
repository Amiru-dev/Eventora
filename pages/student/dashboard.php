<?php
/**
 * Eventora - Student Dashboard Layout
 * Shows booking summary metrics, upcoming registered events, quick actions
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Student Portal · ' . APP_NAME;
$activePage = 'student_dashboard';
$studentActive = 'dashboard';

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
                    <p class="text-muted small mb-0">Welcome back, <strong>Test Student</strong>! Here is your campus event activity summary.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1" aria-hidden="true"></i> Find More Events
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards (360px mobile responsive) -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>2</h3>
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
                            <h3>LKR 500</h3>
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
                            <h3>3</h3>
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
                            <h3>1</h3>
                            <p>Waitlist Queue</p>
                        </div>
                        <div class="stat-card-icon stat-icon-info">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Confirmed Event Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">Next Confirmed Event</h2>
                    <span class="badge badge-status badge-confirmed">Confirmed</span>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h3 class="h5 fw-bold text-dark mb-2">Introduction to Web Development</h3>
                            <div class="text-muted small mb-2">
                                <span class="me-3"><i class="bi bi-calendar-event me-1 text-primary"></i> Fri, Nov 27, 2026 · 10:00 AM</span>
                                <span><i class="bi bi-geo-alt me-1 text-danger"></i> Lab 201, IT Building</span>
                            </div>
                            <div class="small text-muted">
                                Booking Ref: <strong>EVT-2026-9481</strong> · 1 Seat Reserved · Ticket Token: <code>e8b4f...d91c</code>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="<?php echo APP_URL; ?>/pages/student/ticket.php?ref=EVT-2026-9481" class="btn btn-outline-primary">
                                <i class="bi bi-qr-code me-1"></i> View E-Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table Skeleton -->
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
                                <th scope="col">Total (LKR)</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong class="text-dark">EVT-2026-9481</strong></td>
                                <td>Web Development Workshop</td>
                                <td>1</td>
                                <td>LKR 500.00</td>
                                <td><span class="badge badge-status badge-confirmed">Confirmed</span></td>
                                <td class="text-end">
                                    <a href="<?php echo APP_URL; ?>/pages/student/ticket.php" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-qr-code"></i> E-Ticket
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong class="text-dark">EVT-2026-1022</strong></td>
                                <td>AI Seminar</td>
                                <td>2</td>
                                <td>FREE</td>
                                <td><span class="badge badge-status badge-confirmed">Confirmed</span></td>
                                <td class="text-end">
                                    <a href="<?php echo APP_URL; ?>/pages/student/ticket.php" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-qr-code"></i> E-Ticket
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
