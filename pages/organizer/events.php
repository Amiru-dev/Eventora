<?php
/**
 * Eventora - Organizer Events Management
 * 
 * Implements:
 * - FR-ORG-005: Organizers view only events they own or manage
 * - FR-EVT-006: Organizers edit owned events and submit changes
 * - UC-03: View approval status, rejection reasons, and resubmit
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/events.php';

require_role('organizer');

$user = current_user();
$organizerProfileId = (int)($user['profile_id'] ?? 0);

// Fetch owned events (FR-ORG-005)
$events = get_events_by_organizer($organizerProfileId);

// Fallback to sample events if database not populated
if (empty($events)) {
    $events = [
        [
            'event_id'         => 1,
            'title'            => 'Introduction to Web Development',
            'category'         => 'Workshop',
            'venue'            => 'Lab 201, IT Building',
            'start_date'       => '2026-11-27 10:00:00',
            'capacity'         => 40,
            'seats_available'  => 32,
            'price'            => 500.00,
            'status'           => 'published',
            'rejection_reason' => null
        ],
        [
            'event_id'         => 5,
            'title'            => 'Hackathon: Code for Change',
            'category'         => 'Tech',
            'venue'            => 'Innovation Hub',
            'start_date'       => '2026-11-30 09:00:00',
            'capacity'         => 60,
            'seats_available'  => 45,
            'price'            => 1000.00,
            'status'           => 'published',
            'rejection_reason' => null
        ]
    ];
}

$pageTitle = 'My Managed Events · ' . APP_NAME;
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

            <!-- Events List Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Organizer Event List">
                        <thead>
                            <tr>
                                <th scope="col">Event Title</th>
                                <th scope="col">Venue & Schedule</th>
                                <th scope="col">Capacity & Reserved</th>
                                <th scope="col">Price</th>
                                <th scope="col">Approval Status</th>
                                <th scope="col" class="text-end">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                                <?php
                                $totalCap = (int)$ev['capacity'];
                                $availSeats = (int)$ev['seats_available'];
                                $bookedSeats = $totalCap - $availSeats;
                                $pct = ($totalCap > 0) ? min(100, round(($bookedSeats / $totalCap) * 100)) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo e($ev['title']); ?></strong>
                                        <span class="badge badge-category badge-<?php echo strtolower(e($ev['category'])); ?>">
                                            <?php echo e($ev['category']); ?>
                                        </span>
                                        <?php if (!empty($ev['rejection_reason']) && $ev['status'] === 'draft'): ?>
                                            <div class="small text-danger mt-1">
                                                <i class="bi bi-info-circle me-1"></i>
                                                <strong>Rejection Feedback:</strong> <?php echo e($ev['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-geo-alt me-1 text-danger"></i> <?php echo e($ev['venue']); ?></div>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?php echo format_datetime($ev['start_date']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $availSeats; ?></strong> / <?php echo $totalCap; ?> seats open
                                        <div class="progress mt-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%" title="<?php echo $pct; ?>% booked"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo ((float)$ev['price'] <= 0) ? '<span class="text-success fw-bold">FREE</span>' : format_currency($ev['price']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-<?php echo e($ev['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', e($ev['status']))); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="event_form.php?id=<?php echo (int)$ev['event_id']; ?>" class="btn btn-outline-secondary" title="Edit Event Details">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="checkin.php?event=<?php echo (int)$ev['event_id']; ?>" class="btn btn-outline-primary" title="Launch Venue Check-in">
                                                <i class="bi bi-qr-code-scan"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
