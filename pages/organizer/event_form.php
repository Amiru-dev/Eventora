<?php
/**
 * Eventora - Organizer Event Create / Edit Form
 * 
 * Implements:
 * - FR-EVT-006: Organizers create and edit events they own
 * - FR-EVT-009: Reject capacity changes lower than seats already committed/held
 * - FR-AUTH-007 & BR-15: Ownership enforcement
 * - FR-VAL-002 & FR-VAL-003: Server-side validation and CSRF protection
 * - UC-03: Organizer event submission lifecycle
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/events.php';

require_role('organizer');

$user = current_user();
$organizerProfileId = (int)($user['profile_id'] ?? 0);

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit  = $eventId !== null && $eventId > 0;
$event   = null;
$errors  = [];

// If editing, verify ownership and load existing event record
if ($isEdit) {
    if (!can_manage_event($eventId)) {
        set_flash_message('danger', 'Access denied: You can only edit events belonging to your society (FR-AUTH-007).');
        redirect(APP_URL . '/pages/organizer/events.php');
    }
    $event = get_event_by_id($eventId);
    if (!$event) {
        set_flash_message('danger', 'Event not found.');
        redirect(APP_URL . '/pages/organizer/events.php');
    }
}

// Calculate already reserved / committed seats for capacity enforcement
$committedSeats = 0;
if ($isEdit && $event) {
    $committedSeats = (int)$event['capacity'] - (int)$event['seats_available'];
}

// Form defaults
$formData = [
    'title'            => $event['title'] ?? '',
    'category'         => $event['category'] ?? '',
    'venue'            => $event['venue'] ?? '',
    'start_date'       => $event ? date('Y-m-d\TH:i', strtotime($event['start_date'])) : '',
    'end_date'         => $event ? date('Y-m-d\TH:i', strtotime($event['end_date'])) : '',
    'price'            => $event ? (float)$event['price'] : 0.00,
    'capacity'         => $event['capacity'] ?? 50,
    'max_per_student'  => $event['max_per_student'] ?? 5,
    'waitlist_enabled' => $event ? (int)$event['waitlist_enabled'] : 1,
    'description'      => $event['description'] ?? ''
];

// Process Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid or expired. Please submit the form again.';
    } else {
        $postData = [
            'title'            => $_POST['title'] ?? '',
            'category'         => $_POST['category'] ?? '',
            'venue'            => $_POST['venue'] ?? '',
            'start_date'       => $_POST['start_date'] ?? '',
            'end_date'         => $_POST['end_date'] ?? '',
            'price'            => $_POST['price'] ?? 0.00,
            'capacity'         => $_POST['capacity'] ?? 0,
            'max_per_student'  => $_POST['max_per_student'] ?? 5,
            'waitlist_enabled' => isset($_POST['waitlist_enabled']) ? 1 : 0,
            'description'      => $_POST['description'] ?? ''
        ];

        // Keep form filled with entered data on error
        $formData = array_merge($formData, $postData);

        if ($isEdit) {
            $result = update_event($eventId, $organizerProfileId, $postData, false);
            if ($result['success']) {
                set_flash_message('success', 'Event "' . e($postData['title']) . '" was successfully updated and submitted for approval (FR-EVT-006).');
                redirect(APP_URL . '/pages/organizer/events.php');
            } else {
                $errors[] = $result['error'];
            }
        } else {
            $result = create_event($organizerProfileId, $postData);
            if ($result['success']) {
                set_flash_message('success', 'Event "' . e($postData['title']) . '" was created with status Pending Approval. An administrator will review it shortly.');
                redirect(APP_URL . '/pages/organizer/events.php');
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

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

            <!-- Error Banner -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <ul class="mb-0 small ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Committed Seats Notice for Edit Mode (FR-EVT-009) -->
            <?php if ($isEdit && $committedSeats > 0): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-shield-exclamation fs-4 text-warning" aria-hidden="true"></i>
                    <small>
                        <strong>Capacity Rule (FR-EVT-009):</strong> There are currently <strong><?php echo $committedSeats; ?> seats</strong> already booked by students for this event. You cannot set the new capacity lower than this number.
                    </small>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form id="eventForm" action="event_form.php<?php echo $isEdit ? '?id=' . $eventId : ''; ?>" method="POST" novalidate class="needs-validation">
                        <!-- CSRF Protection (FR-VAL-003) -->
                        <?php echo csrf_field(); ?>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="event_title" class="form-label">Event Title</label>
                            <input type="text" class="form-control form-control-lg" id="event_title" name="title" placeholder="e.g. Advanced Cybersecurity Workshop" value="<?php echo e($formData['title']); ?>" required minlength="5">
                            <div class="invalid-feedback">Event title is required (min 5 characters).</div>
                        </div>

                        <!-- Category & Venue -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Event Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select category...</option>
                                    <?php
                                    $cats = ['Workshop', 'Seminar', 'Sports', 'Cultural', 'Tech', 'Social'];
                                    foreach ($cats as $cat) {
                                        $sel = ($formData['category'] === $cat) ? 'selected' : '';
                                        echo "<option value=\"{$cat}\" {$sel}>{$cat}</option>";
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">Category selection is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="venue" class="form-label">Campus Venue</label>
                                <input type="text" class="form-control" id="venue" name="venue" placeholder="e.g. Main Auditorium or Lab 201" value="<?php echo e($formData['venue']); ?>" required>
                                <div class="invalid-feedback">Venue location is required.</div>
                            </div>
                        </div>

                        <!-- Dates & Times (Asia/Colombo) -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date & Time (Asia/Colombo)</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo e($formData['start_date']); ?>" required>
                                <div class="invalid-feedback">Valid start time is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo e($formData['end_date']); ?>" required>
                                <div class="invalid-feedback">End time must be after start time.</div>
                            </div>
                        </div>

                        <!-- Pricing & Capacity -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">Ticket Price (LKR)</label>
                                <div class="input-group">
                                    <span class="input-group-text">LKR</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" placeholder="0.00" value="<?php echo e($formData['price']); ?>" required>
                                </div>
                                <div class="form-text small">Enter 0.00 for free entry events.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="capacity" class="form-label">Total Room Capacity</label>
                                <input type="number" min="<?php echo max(1, $committedSeats); ?>" class="form-control" id="capacity" name="capacity" placeholder="50" value="<?php echo e($formData['capacity']); ?>" required>
                                <div class="invalid-feedback">Capacity must be at least <?php echo max(1, $committedSeats); ?> (committed seats limit).</div>
                            </div>
                            <div class="col-md-4">
                                <label for="max_per_student" class="form-label">Max Seats Per Student</label>
                                <input type="number" min="1" max="10" class="form-control" id="max_per_student" name="max_per_student" value="<?php echo e($formData['max_per_student']); ?>" required>
                                <div class="form-text small">Default max 10 (BR-04).</div>
                            </div>
                        </div>

                        <!-- Waitlist Toggle (BR-03, BR-13) -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="waitlist_enabled" name="waitlist_enabled" <?php echo !empty($formData['waitlist_enabled']) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="waitlist_enabled">
                                Enable FIFO Waitlist when event is sold out (BR-03, BR-13)
                            </label>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label">Event Overview & Agenda</label>
                            <textarea class="form-control" id="description" name="description" rows="5" placeholder="Provide full session details, prerequisites, speaker background and agenda..." required><?php echo e($formData['description']); ?></textarea>
                            <div class="invalid-feedback">Event description is required (min 20 characters).</div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="events.php" class="btn btn-outline-secondary">Discard</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-send-check me-1"></i> <?php echo $isEdit ? 'Update & Submit for Review' : 'Submit Event for Approval'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
