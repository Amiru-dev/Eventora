<?php
/**
 * Eventora - Organizer Venue Check-in Portal
 * FR-TKT-003, FR-TKT-004, FR-TKT-005, FR-TKT-006, FR-TKT-007, BR-16, UC-10
 * Fast attendee validation via camera QR scanner simulator or manual token lookup
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Venue Check-in Console · ' . APP_NAME;
$activePage = 'org_checkin';
$orgActive = 'checkin';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_organizer.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Venue Ticket Check-in</h1>
                    <p class="text-muted small mb-0">Scan attendee QR codes or enter opaque ticket tokens to authenticate entry.</p>
                </div>
                <div>
                    <select class="form-select form-select-sm" aria-label="Select active event for check-in">
                        <option value="1">Introduction to Web Development (Lab 201)</option>
                        <option value="5">Hackathon: Code for Change (Innovation Hub)</option>
                    </select>
                </div>
            </div>

            <div class="row g-4">
                <!-- Scanner & Validation Column -->
                <div class="col-lg-6">
                    <!-- Scanner Box -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h2 class="h5 fw-bold mb-0"><i class="bi bi-camera me-1"></i> QR Camera Scanner</h2>
                            <span class="badge bg-success-subtle text-success border">Camera Ready</span>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="p-4 bg-dark text-white rounded-3 mb-3 d-flex flex-column align-items-center justify-content-center" style="min-height: 220px; position: relative;">
                                <i class="bi bi-qr-code-scan display-3 text-warning mb-2" aria-hidden="true"></i>
                                <span class="small text-light">Point camera at attendee's printed or mobile ticket</span>
                                <div class="mt-2 spinner-grow spinner-grow-sm text-warning" role="status">
                                    <span class="visually-hidden">Scanning...</span>
                                </div>
                            </div>

                            <!-- Manual Token Input (FR-TKT-007) -->
                            <form id="tokenCheckinForm" class="text-start">
                                <label for="ticket_token" class="form-label">Or Enter Ticket Token Manually:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-upc"></i></span>
                                    <input type="text" id="ticket_token" class="form-control" placeholder="e.g. TKT-8f2a9c1e4b7d603a" required>
                                    <button class="btn btn-primary" type="submit">Verify Entry</button>
                                </div>
                                <div class="form-text small">Tokens are opaque 64-character or prefixed cryptographic hashes.</div>
                            </form>
                        </div>
                    </div>

                    <!-- Live Validation Feedback Banner Mock (UC-10: Valid / Already Used / Invalid) -->
                    <div class="card border-0 shadow-sm border-start border-4 border-success">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-card-icon stat-icon-success flex-shrink-0" style="width: 44px; height: 44px;">
                                    <i class="bi bi-check-lg fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong class="d-block text-success fw-bold">ENTRY APPROVED</strong>
                                    <div class="text-dark fw-semibold">Test Student · 1 Seat Confirmed</div>
                                    <small class="text-muted">Checked in at 09:58 AM by Staff (organizer@eventora.com)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendee Roster Column (FR-TKT-007) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h2 class="h5 fw-bold mb-0">Attendee Roster</h2>
                                <span class="badge bg-light text-muted border">8 / 40 Checked in</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Search by student name or ref...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom mb-0" aria-label="Attendee Roster">
                                <thead>
                                    <tr>
                                        <th scope="col">Student</th>
                                        <th scope="col">Token</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">Test Student</strong>
                                            <small class="text-muted">student@eventora.com</small>
                                        </td>
                                        <td><code>TKT-8f2a9c1e</code></td>
                                        <td><span class="badge bg-success-subtle text-success border">Checked In</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Used</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">Kasun Fernando</strong>
                                            <small class="text-muted">kasun.f@student.lk</small>
                                        </td>
                                        <td><code>TKT-3d71ba09</code></td>
                                        <td><span class="badge bg-warning-subtle text-warning border">Pending</span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-primary">Check In</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
