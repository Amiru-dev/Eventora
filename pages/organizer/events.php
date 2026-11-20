<?php
/**
 * Eventora - Organizer Events Management
 * FR-ORG-003, FR-ORG-005, FR-EVT-006
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('organizer');

$pageTitle = 'My Events · ' . APP_NAME;
$activePage = 'org_events';
$orgActive = 'events';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_organizer.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">My Managed Events</h1>
                    <p class="text-muted small mb-0">Create, edit and track approval lifecycle for events you organize.</p>
                </div>
                <div>
                    <a href="event_form.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i> Create New Event
                    </a>
                </div>
            </div>

            <!-- Events List -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Organizer Event List">
                        <thead>
                            <tr>
                                <th scope="col">Event</th>
                                <th scope="col">Venue & Schedule</th>
                                <th scope="col">Capacity / Available</th>
                                <th scope="col">Price (LKR)</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">Introduction to Web Development</strong>
                                    <span class="badge badge-category badge-workshop">Workshop</span>
                                </td>
                                <td>
                                    <div><i class="bi bi-geo-alt me-1 text-danger"></i> Lab 201, IT Building</div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Nov 27, 2026 · 10:00 AM</small>
                                </td>
                                <td>
                                    <strong>32</strong> / 40 seats left
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: 80%"></div>
                                    </div>
                                </td>
                                <td>LKR 500.00</td>
                                <td><span class="badge badge-status badge-published">Published</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="event_form.php?id=1" class="btn btn-outline-secondary" title="Edit Event"><i class="bi bi-pencil"></i></a>
                                        <a href="checkin.php?event=1" class="btn btn-outline-primary" title="Check-in Console"><i class="bi bi-qr-code-scan"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">AI and Machine Learning Seminar</strong>
                                    <span class="badge badge-category badge-seminar">Seminar</span>
                                </td>
                                <td>
                                    <div><i class="bi bi-geo-alt me-1 text-danger"></i> Main Auditorium</div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Dec 04, 2026 · 02:00 PM</small>
                                </td>
                                <td>
                                    <strong>200</strong> / 200 seats left
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td><span class="text-success fw-bold">FREE</span></td>
                                <td><span class="badge badge-status badge-published">Published</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="event_form.php?id=2" class="btn btn-outline-secondary" title="Edit Event"><i class="bi bi-pencil"></i></a>
                                        <a href="checkin.php?event=2" class="btn btn-outline-primary" title="Check-in Console"><i class="bi bi-qr-code-scan"></i></a>
                                    </div>
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
