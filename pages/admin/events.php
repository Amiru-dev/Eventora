<?php
/**
 * Eventora - Admin Global Event Management & Approval
 * FR-EVT-007, FR-EVT-008, FR-EVT-010
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Event Approvals · ' . APP_NAME;
$activePage = 'admin_events';
$adminActive = 'events';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Global Event Approvals</h1>
                    <p class="text-muted small mb-0">Approve, reject, publish or suspend campus event submissions across all societies.</p>
                </div>
            </div>

            <!-- Global Events Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Event Approvals Table">
                        <thead>
                            <tr>
                                <th scope="col">Event Details</th>
                                <th scope="col">Organizer</th>
                                <th scope="col">Schedule</th>
                                <th scope="col">Capacity</th>
                                <th scope="col">Price</th>
                                <th scope="col">Approval Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Pending Event -->
                            <tr class="table-warning bg-opacity-10">
                                <td>
                                    <strong class="text-dark d-block">Cloud Computing & DevOps Masterclass</strong>
                                    <span class="badge badge-category badge-tech">Tech</span>
                                </td>
                                <td>NIIBS Tech Club</td>
                                <td>Dec 08, 2026</td>
                                <td>45 seats</td>
                                <td>LKR 750.00</td>
                                <td><span class="badge badge-status badge-pending">Pending Approval</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" title="Approve and Publish"><i class="bi bi-check-circle me-1"></i> Approve</button>
                                        <button class="btn btn-outline-danger" title="Reject with Reason"><i class="bi bi-x-circle me-1"></i> Reject</button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Published Event -->
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">Introduction to Web Development</strong>
                                    <span class="badge badge-category badge-workshop">Workshop</span>
                                </td>
                                <td>NIIBS Tech Club</td>
                                <td>Nov 27, 2026</td>
                                <td>40 seats</td>
                                <td>LKR 500.00</td>
                                <td><span class="badge badge-status badge-published">Published</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-warning" title="Suspend Event"><i class="bi bi-pause"></i> Suspend</button>
                                        <button class="btn btn-outline-secondary" title="Archive Event"><i class="bi bi-archive"></i></button>
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
