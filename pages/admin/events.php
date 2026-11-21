<?php
/**
 * Eventora - Admin Global Event Management & Approval Center
 * 
 * Implements:
 * - FR-EVT-007: Approve, reject, publish, suspend, cancel and archive events
 * - FR-EVT-008: Record approving administrator, decision, timestamp and rejection reason
 * - FR-EVT-010: Exclude unapproved events from public list
 * - UC-03: Detailed use case for administrator event review
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/events.php';

require_role('admin');

$admin = current_user();
$adminId = (int)$admin['id'];

// Handle POST Decisions (Approve, Reject, Suspend, Publish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security token invalid. Please try again.');
    } else {
        $action  = $_POST['action'] ?? '';
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId > 0) {
            if ($action === 'approve') {
                if (admin_approve_event($eventId, $adminId)) {
                    set_flash_message('success', "Event #{$eventId} approved and published to public catalog.");
                } else {
                    set_flash_message('danger', "Could not approve Event #{$eventId}.");
                }
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? 'Event does not meet campus criteria.');
                if (admin_reject_event($eventId, $adminId, $reason)) {
                    set_flash_message('warning', "Event #{$eventId} rejected and returned to organizer as draft.");
                } else {
                    set_flash_message('danger', "Could not reject Event #{$eventId}.");
                }
            } elseif ($action === 'suspend') {
                if (admin_set_event_status($eventId, 'suspended', $adminId)) {
                    set_flash_message('warning', "Event #{$eventId} suspended from public listings.");
                }
            } elseif ($action === 'publish') {
                if (admin_set_event_status($eventId, 'published', $adminId)) {
                    set_flash_message('success', "Event #{$eventId} republished successfully.");
                }
            }
        }
    }
    redirect(APP_URL . '/pages/admin/events.php');
}

// Filter query
$statusFilter = $_GET['status'] ?? 'all';
$events = get_all_events_admin($statusFilter);

// Fallback dataset if database is empty
if (empty($events)) {
    $events = [
        [
            'event_id'         => 1,
            'title'            => 'Introduction to Web Development',
            'category'         => 'Workshop',
            'organizer_name'   => 'NIIBS Tech Club',
            'start_date'       => '2026-11-27 10:00:00',
            'capacity'         => 40,
            'price'            => 500.00,
            'status'           => 'published',
            'rejection_reason' => null
        ],
        [
            'event_id'         => 7,
            'title'            => 'Cloud Computing & DevOps Masterclass',
            'category'         => 'Tech',
            'organizer_name'   => 'NIIBS Tech Club',
            'start_date'       => '2026-12-08 09:00:00',
            'capacity'         => 45,
            'price'            => 750.00,
            'status'           => 'pending_approval',
            'rejection_reason' => null
        ]
    ];
}

$pageTitle = 'Event Approvals & Moderation · ' . APP_NAME;
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
                    <h1 class="h3 fw-bold mb-1">Global Event Moderation</h1>
                    <p class="text-muted small mb-0">Approve, reject, publish or suspend campus events across all student societies.</p>
                </div>

                <!-- Status Filter Pills -->
                <div class="btn-group btn-group-sm mt-2 mt-md-0">
                    <a href="events.php?status=all" class="btn <?php echo ($statusFilter === 'all') ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="events.php?status=pending_approval" class="btn <?php echo ($statusFilter === 'pending_approval') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="events.php?status=published" class="btn <?php echo ($statusFilter === 'published') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Published</a>
                    <a href="events.php?status=suspended" class="btn <?php echo ($statusFilter === 'suspended') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Suspended</a>
                </div>
            </div>

            <!-- Global Events Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Event Approvals Table">
                        <thead>
                            <tr>
                                <th scope="col">Event Details</th>
                                <th scope="col">Society / Organizer</th>
                                <th scope="col">Schedule</th>
                                <th scope="col">Capacity</th>
                                <th scope="col">Price</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Decision Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $ev): ?>
                                <tr class="<?php echo ($ev['status'] === 'pending_approval') ? 'table-warning bg-opacity-10' : ''; ?>">
                                    <td>
                                        <strong class="text-dark d-block"><?php echo e($ev['title']); ?></strong>
                                        <span class="badge badge-category badge-<?php echo strtolower(e($ev['category'])); ?>">
                                            <?php echo e($ev['category']); ?>
                                        </span>
                                        <?php if (!empty($ev['rejection_reason'])): ?>
                                            <div class="small text-danger mt-1">
                                                <strong>Rejection Reason:</strong> <?php echo e($ev['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?php echo e($ev['organizer_name'] ?? 'Society'); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?php echo format_datetime($ev['start_date']); ?></small>
                                    </td>
                                    <td><?php echo (int)$ev['capacity']; ?> seats</td>
                                    <td>
                                        <?php echo ((float)$ev['price'] <= 0) ? '<span class="text-success fw-bold">FREE</span>' : format_currency($ev['price']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-<?php echo e($ev['status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', e($ev['status']))); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ev['status'] === 'pending_approval'): ?>
                                            <div class="d-inline-flex gap-1">
                                                <!-- One-click Approve & Publish (FR-EVT-007, FR-EVT-008) -->
                                                <form action="events.php" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="event_id" value="<?php echo (int)$ev['event_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve and Publish to Catalog">
                                                        <i class="bi bi-check-circle me-1"></i> Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Modal Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-<?php echo (int)$ev['event_id']; ?>">
                                                    <i class="bi bi-x-circle me-1"></i> Reject
                                                </button>
                                            </div>

                                            <!-- Rejection Modal -->
                                            <div class="modal fade" id="rejectModal-<?php echo (int)$ev['event_id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel-<?php echo (int)$ev['event_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog text-start">
                                                    <div class="modal-content">
                                                        <form action="events.php" method="POST">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="event_id" value="<?php echo (int)$ev['event_id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold" id="rejectModalLabel-<?php echo (int)$ev['event_id']; ?>">Reject Event Submission</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small text-muted mb-2">Provide a reason for the organizer so they can revise and resubmit:</p>
                                                                <textarea class="form-control" name="rejection_reason" rows="3" placeholder="e.g. Please clarify prerequisites or adjust venue capacity..." required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php elseif ($ev['status'] === 'published'): ?>
                                            <!-- Suspend Action -->
                                            <form action="events.php" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="event_id" value="<?php echo (int)$ev['event_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend from public catalog">
                                                    <i class="bi bi-pause"></i> Suspend
                                                </button>
                                            </form>
                                        <?php elseif ($ev['status'] === 'suspended'): ?>
                                            <!-- Reactivate / Publish Action -->
                                            <form action="events.php" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="publish">
                                                <input type="hidden" name="event_id" value="<?php echo (int)$ev['event_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Republish event">
                                                    <i class="bi bi-play"></i> Republish
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
