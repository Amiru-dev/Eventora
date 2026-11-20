<?php
/**
 * Eventora - Student Waitlist Page
 * FR-WAIT-001, FR-WAIT-002, FR-WAIT-003, BR-13 (FIFO order & 30-min offer expiry)
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Waitlist Queue · ' . APP_NAME;
$activePage = 'student_waitlist';
$studentActive = 'waitlist';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Waitlist Queue Positions</h1>
                    <p class="text-muted small mb-0">Track FIFO queue positions for high-demand sold-out campus events.</p>
                </div>
            </div>

            <!-- Waitlist Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Waitlist Queue">
                        <thead>
                            <tr>
                                <th scope="col">Event</th>
                                <th scope="col">Queue Position</th>
                                <th scope="col">Seats Requested</th>
                                <th scope="col">Joined On</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">Campus Photography Workshop</strong>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Nov 25, 2026</small>
                                </td>
                                <td>
                                    <span class="badge bg-primary fs-6 px-3"># 2 in queue</span>
                                </td>
                                <td>1 Seat</td>
                                <td>2026-11-20 14:15</td>
                                <td>
                                    <span class="badge badge-status badge-pending">Waiting for Release</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-box-arrow-left me-1"></i> Leave Waitlist
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="alert alert-light border mt-4 p-3 small text-muted">
                <i class="bi bi-info-circle text-primary me-1"></i>
                <strong>How the Waitlist works:</strong> When a registered student cancels or room capacity is expanded, the system automatically offers the seat to the next student in FIFO sequence. An email notification will be delivered and you will have <strong>30 minutes</strong> to claim the seat before it passes to the next person.
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
