<?php
/**
 * Eventora - Organizer Event Create / Edit Form
 * FR-EVT-006, FR-EVT-009 (Capacity validation rule), UC-03
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('organizer');

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $eventId !== null;
$pageTitle = ($isEdit ? 'Edit Event' : 'Create Event') . ' · ' . APP_NAME;
$activePage = 'org_events';
$orgActive = $isEdit ? 'events' : 'create_event';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_organizer.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0" style="max-width: 900px;">
            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="events.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h1 class="h3 fw-bold mb-0"><?php echo $isEdit ? 'Edit Event Details' : 'Create New Campus Event'; ?></h1>
                    <p class="text-muted small mb-0">Fill in event specifications. Once submitted, it will undergo administrative review.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form id="eventForm" action="events.php" method="POST" novalidate class="needs-validation">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title</label>
                            <input type="text" class="form-control form-control-lg" id="event_title" name="title" placeholder="e.g. Advanced Cybersecurity Workshop" required minlength="5">
                            <div class="invalid-feedback">Event title is required (min 5 characters).</div>
                        </div>

                        <!-- Category & Venue -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Event Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select category...</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Cultural">Cultural</option>
                                    <option value="Tech">Tech</option>
                                    <option value="Social">Social</option>
                                </select>
                                <div class="invalid-feedback">Category selection is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="venue" class="form-label">Campus Venue</label>
                                <input type="text" class="form-control" id="venue" name="venue" placeholder="e.g. Main Auditorium or Lab 201" required>
                                <div class="invalid-feedback">Venue location is required.</div>
                            </div>
                        </div>

                        <!-- Dates & Times -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date & Time (Asia/Colombo)</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                <div class="invalid-feedback">Valid start time is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                <div class="invalid-feedback">End time must be after start time.</div>
                            </div>
                        </div>

                        <!-- Pricing & Capacity -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">Ticket Price (LKR)</label>
                                <div class="input-group">
                                    <span class="input-group-text">LKR</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" placeholder="0.00" required>
                                </div>
                                <div class="form-text small">Enter 0.00 for free entry events.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="capacity" class="form-label">Total Room Capacity</label>
                                <input type="number" min="1" class="form-control" id="capacity" name="capacity" placeholder="50" required>
                                <div class="invalid-feedback">Capacity must be at least 1.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="max_per_student" class="form-label">Max Seats Per Student</label>
                                <input type="number" min="1" max="10" class="form-control" id="max_per_student" name="max_per_student" value="5" required>
                                <div class="form-text small">Default max 10 (BR-04).</div>
                            </div>
                        </div>

                        <!-- Waitlist Toggle -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="waitlist_enabled" name="waitlist_enabled" checked>
                            <label class="form-check-label fw-semibold" for="waitlist_enabled">
                                Enable Waitlist when capacity is reached (BR-03, BR-13)
                            </label>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Event Overview & Agenda</label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Provide full session details, prerequisites, speaker background and agenda..." required></textarea>
                            <div class="invalid-feedback">Event description is required.</div>
                        </div>

                        <!-- Submit Options -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="events.php" class="btn btn-outline-secondary">Discard</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-send-check me-1"></i> Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
