<?php
/**
 * Eventora - Admin Organizer Approval Workflow
 * FR-ORG-001, FR-ORG-002, FR-ORG-006, FR-ORG-007, UC-03
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Organizer Approvals · ' . APP_NAME;
$activePage = 'admin_organizers';
$adminActive = 'organizers';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Club & Organizer Approvals</h1>
                    <p class="text-muted small mb-0">Review organizer access requests and manage existing organizer permissions.</p>
                </div>
            </div>

            <!-- Approvals Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Organizer Requests">
                        <thead>
                            <tr>
                                <th scope="col">Society / Club</th>
                                <th scope="col">Representative</th>
                                <th scope="col">Purpose / Bio</th>
                                <th scope="col">Requested</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Decision Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Pending row -->
                            <tr class="table-warning bg-opacity-10">
                                <td>
                                    <strong class="text-dark d-block">IEEE Student Branch</strong>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i> +94 71 998 8776</small>
                                </td>
                                <td>
                                    <div>Dilshan Silva</div>
                                    <small class="text-muted">dilshan@ieee.niibs.lk</small>
                                </td>
                                <td>
                                    <small class="text-secondary">Organizing engineering workshops and hackathons on campus.</small>
                                </td>
                                <td>2026-11-20</td>
                                <td><span class="badge badge-status badge-pending">Pending Review</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" title="Approve Organizer">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button class="btn btn-outline-danger" title="Reject Organizer">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Approved row -->
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">NIIBS Tech Club</strong>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i> +94 77 123 4567</small>
                                </td>
                                <td>
                                    <div>Test Organizer</div>
                                    <small class="text-muted">organizer@eventora.com</small>
                                </td>
                                <td>
                                    <small class="text-secondary">Official computing faculty student club.</small>
                                </td>
                                <td>2026-11-15</td>
                                <td><span class="badge badge-status badge-approved">Approved</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-warning" title="Suspend Organizer (FR-ORG-006)">
                                        <i class="bi bi-pause-circle me-1"></i> Suspend
                                    </button>
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
