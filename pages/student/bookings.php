<?php
/**
 * Eventora - Student Bookings Management
 * FR-BKG-007, FR-BKG-008
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$pageTitle = 'My Bookings · ' . APP_NAME;
$activePage = 'student_bookings';
$studentActive = 'bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">My Bookings & Reservations</h1>
                    <p class="text-muted small mb-0">View all your registered event seats, download tickets, or cancel eligible bookings.</p>
                </div>
            </div>

            <!-- Bookings List Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Bookings List">
                        <thead>
                            <tr>
                                <th scope="col">Booking Ref</th>
                                <th scope="col">Event Details</th>
                                <th scope="col">Seats</th>
                                <th scope="col">Pricing Breakdown</th>
                                <th scope="col">Payment / Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">EVT-2026-9481</strong>
                                    <small class="text-muted">Booked 2026-11-20</small>
                                </td>
                                <td>
                                    <strong class="text-dark d-block">Introduction to Web Development</strong>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Nov 27, 2026 · 10:00 AM</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">1 Seat</span></td>
                                <td>
                                    <div class="small">Subtotal: LKR 500.00</div>
                                    <strong class="text-primary">Total: LKR 500.00</strong>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-confirmed mb-1 d-inline-block">Confirmed</span>
                                    <small class="text-success d-block"><i class="bi bi-check-circle"></i> PayHere Verified</small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="ticket.php?ref=EVT-2026-9481" class="btn btn-outline-primary" title="View E-Ticket">
                                            <i class="bi bi-qr-code me-1"></i> Ticket
                                        </a>
                                        <button class="btn btn-outline-danger" title="Cancel Booking">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong class="text-dark d-block">EVT-2026-1022</strong>
                                    <small class="text-muted">Booked 2026-11-19</small>
                                </td>
                                <td>
                                    <strong class="text-dark d-block">AI and Machine Learning Seminar</strong>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> Dec 04, 2026 · 02:00 PM</small>
                                </td>
                                <td><span class="badge bg-light text-dark border">2 Seats</span></td>
                                <td>
                                    <strong class="text-success">Free Entry</strong>
                                </td>
                                <td>
                                    <span class="badge badge-status badge-confirmed mb-1 d-inline-block">Confirmed</span>
                                    <small class="text-muted d-block">Free Confirmation</small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="ticket.php?ref=EVT-2026-1022" class="btn btn-outline-primary" title="View E-Ticket">
                                            <i class="bi bi-qr-code me-1"></i> Ticket
                                        </a>
                                        <button class="btn btn-outline-danger" title="Cancel Booking">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
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
