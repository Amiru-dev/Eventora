<?php
/**
 * Eventora - Printable QR E-Ticket View
 * FR-TKT-001, FR-TKT-002, NFR-TKT-002, NFR-PRV-001
 * Displays opaque, non-sequential token with QR code preview & printable layout
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Electronic Ticket · ' . APP_NAME;
$activePage = 'student_bookings';

include __DIR__ . '/../../templates/header.php';
?>

<div class="container py-4">
    <!-- Top actions (hidden when printed) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="<?php echo APP_URL; ?>/pages/student/bookings.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Bookings
        </a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">
            <i class="bi bi-printer-fill me-1"></i> Print / Save Ticket (PDF)
        </button>
    </div>

    <!-- Official Printable E-Ticket Container -->
    <div class="ticket-container" id="printableTicket">
        <div class="ticket-header">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event-fill text-warning fs-3"></i>
                    <span class="fs-4 fw-extrabold text-white">Eventora</span>
                </div>
                <span class="badge bg-success text-white px-3 py-2 fs-6">CONFIRMED SEAT</span>
            </div>
            <h1 class="h3 fw-bold text-white mb-1">Introduction to Web Development</h1>
            <p class="text-white-50 small mb-0">NIIBS Tech Club · Faculty of Computing and Information Technology</p>
        </div>

        <div class="ticket-body">
            <div class="row g-4 align-items-center">
                <!-- Details Section -->
                <div class="col-md-7">
                    <div class="mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Student Attendee</small>
                        <strong class="fs-5 text-dark">Test Student</strong>
                        <div class="small text-muted">ITBSC/2025/EA/WD/003 · student@eventora.com</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Date & Time</small>
                            <span class="fw-bold text-dark">Fri, Nov 27, 2026</span>
                            <div class="small text-muted">10:00 AM – 01:00 PM</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Venue</small>
                            <span class="fw-bold text-dark">Lab 201</span>
                            <div class="small text-muted">IT Building, Main Campus</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Seats Reserved</small>
                            <span class="badge bg-primary fs-6">1 Seat</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Amount Paid</small>
                            <span class="fw-bold text-success fs-6">LKR 500.00</span>
                        </div>
                    </div>

                    <div class="p-2 bg-light rounded small text-muted border">
                        <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Check-in Notice:</strong> Present this QR code or token at the venue entrance. One-time entry only.
                    </div>
                </div>

                <!-- QR Code Box (NFR-SEC-007, FR-TKT-002) -->
                <div class="col-md-5">
                    <div class="ticket-qr-box">
                        <!-- Simulated QR Visual Box -->
                        <div class="p-3 bg-white d-inline-block border rounded mb-2 shadow-sm">
                            <svg class="d-block mx-auto" width="160" height="160" viewBox="0 0 100 100" fill="currentColor">
                                <path d="M0,0 h30 v30 h-30 z M5,5 v20 h20 v-20 z M10,10 h10 v10 h-10 z M70,0 h30 v30 h-30 z M75,5 v20 h20 v-20 z M80,10 h10 v10 h-10 z M0,70 h30 v30 h-30 z M5,75 v20 h20 v-20 z M10,80 h10 v10 h-10 z M40,10 h10 v20 h-10 z M50,40 h20 v10 h-20 z M35,50 h15 v20 h-15 z M70,50 h20 v20 h-20 z M60,80 h20 v10 h-20 z M40,80 h10 v15 h-10 z M85,75 h10 v20 h-10 z"/>
                            </svg>
                        </div>
                        <small class="text-muted d-block mb-1">Opaque Ticket Token (NFR-SEC-007)</small>
                        <span class="ticket-token-code">TKT-8f2a9c1e4b7d603a</span>
                        <div class="small text-muted mt-2">Ref: EVT-2026-9481</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
