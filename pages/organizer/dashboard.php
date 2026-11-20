<?php
/**
 * Eventora - Organizer Dashboard Layout
 * FR-ORG-005, FR-REP-002: Scoped to events owned by the organizer
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('organizer');

$pageTitle = 'Organizer Hub · ' . APP_NAME;
$activePage = 'org_dashboard';
$orgActive = 'dashboard';

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
                    <p class="text-muted small mb-0">Managing society events for <strong>NIIBS Tech Club</strong>.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/pages/organizer/event_form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Submit New Event
                    </a>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>6</h3>
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
                            <h3>825</h3>
                            <p>Seats Capacity</p>
                        </div>
                        <div class="stat-card-icon stat-icon-info">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>LKR 45K</h3>
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
                            <h3>1</h3>
                            <p>Check-in Station</p>
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

            <!-- Managed Events Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h2 class="h5 fw-bold mb-0">My Submitted Events</h2>
                    <a href="events.php" class="small fw-bold">Manage All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Organizer Events">
                        <thead>
                            <tr>
                                <th scope="col">Event Title</th>
                                <th scope="col">Category</th>
                                <th scope="col">Date</th>
                                <th scope="col">Capacity</th>
                                <th scope="col">Price</th>
                                <th scope="col">Approval Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong class="text-dark">Introduction to Web Development</strong></td>
                                <td><span class="badge badge-category badge-workshop">Workshop</span></td>
                                <td>2026-11-27</td>
                                <td>40 seats</td>
                                <td>LKR 500.00</td>
                                <td><span class="badge badge-status badge-published">Published</span></td>
                                <td class="text-end">
                                    <a href="event_form.php?id=1" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <a href="checkin.php?event=1" class="btn btn-sm btn-outline-primary"><i class="bi bi-qr-code-scan"></i></a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong class="text-dark">Hackathon: Code for Change</strong></td>
                                <td><span class="badge badge-category badge-tech">Tech</span></td>
                                <td>2026-11-30</td>
                                <td>60 seats</td>
                                <td>LKR 1,000.00</td>
                                <td><span class="badge badge-status badge-published">Published</span></td>
                                <td class="text-end">
                                    <a href="event_form.php?id=5" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <a href="checkin.php?event=5" class="btn btn-sm btn-outline-primary"><i class="bi bi-qr-code-scan"></i></a>
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
