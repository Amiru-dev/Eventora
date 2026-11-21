<?php
/**
 * Eventora - Admin Organizer Approval Workflow
 * 
 * Implements:
 * - FR-AUTH-004: Organizer creates pending profile; admin approves or rejects
 * - FR-ORG-001 & FR-ORG-002: Multiple organizers, approval decision recording
 * - FR-ORG-006: Suspend organizer and revoke event creation access
 * - FR-ORG-007: Preservation of approval history
 * - UC-03: Detailed use case for organizer approval
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/events.php';

require_role('admin');

$admin = current_user();
$adminId = (int)$admin['id'];

// Handle Decision POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Security token invalid. Please resubmit.');
    } else {
        $action    = $_POST['action'] ?? '';
        $profileId = (int)($_POST['profile_id'] ?? 0);

        if ($profileId > 0) {
            if ($action === 'approve') {
                if (admin_approve_organizer($profileId, $adminId)) {
                    set_flash_message('success', "Organizer profile #{$profileId} approved! The society representative now has event creation access.");
                } else {
                    set_flash_message('danger', "Could not approve organizer profile #{$profileId}.");
                }
            } elseif ($action === 'reject') {
                $reason = trim($_POST['rejection_reason'] ?? 'Application does not meet campus society criteria.');
                if (admin_reject_organizer($profileId, $adminId, $reason)) {
                    set_flash_message('warning', "Organizer application #{$profileId} rejected with recorded reason.");
                } else {
                    set_flash_message('danger', "Could not reject organizer #{$profileId}.");
                }
            } elseif ($action === 'suspend') {
                if (admin_suspend_organizer($profileId)) {
                    set_flash_message('warning', "Organizer #{$profileId} suspended. Event management privileges have been revoked.");
                }
            }
        }
    }
    redirect(APP_URL . '/pages/admin/organizers.php');
}

$statusFilter = $_GET['status'] ?? 'all';
$organizers = get_all_organizers($statusFilter);

// Fallback dataset if database is empty
if (empty($organizers)) {
    $organizers = [
        [
            'profile_id'       => 1,
            'organization'     => 'NIIBS Tech Club',
            'phone'            => '+94 77 123 4567',
            'bio'              => 'Official student society promoting software engineering and web standards.',
            'user_name'        => 'Test Organizer',
            'user_email'       => 'organizer@eventora.com',
            'approval_status'  => 'approved',
            'rejection_reason' => null,
            'created_at'       => '2026-11-15 10:00:00'
        ],
        [
            'profile_id'       => 2,
            'organization'     => 'IEEE Student Branch',
            'phone'            => '+94 71 998 8776',
            'bio'              => 'Hosting technology talks, engineering symposiums and hackathons.',
            'user_name'        => 'Dilshan Silva',
            'user_email'       => 'dilshan@ieee.niibs.lk',
            'approval_status'  => 'pending',
            'rejection_reason' => null,
            'created_at'       => '2026-11-20 12:30:00'
        ]
    ];
}

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
                    <h1 class="h3 fw-bold mb-1">Club & Society Organizers</h1>
                    <p class="text-muted small mb-0">Review organizer access requests and manage existing society permissions.</p>
                </div>

                <div class="btn-group btn-group-sm mt-2 mt-md-0">
                    <a href="organizers.php?status=all" class="btn <?php echo ($statusFilter === 'all') ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                    <a href="organizers.php?status=pending" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                    <a href="organizers.php?status=approved" class="btn <?php echo ($statusFilter === 'approved') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Approved</a>
                    <a href="organizers.php?status=suspended" class="btn <?php echo ($statusFilter === 'suspended') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Suspended</a>
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
                                <th scope="col">Registered</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Decision Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($organizers as $org): ?>
                                <tr class="<?php echo ($org['approval_status'] === 'pending') ? 'table-warning bg-opacity-10' : ''; ?>">
                                    <td>
                                        <strong class="text-dark d-block"><?php echo e($org['organization']); ?></strong>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i> <?php echo e($org['phone']); ?></small>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold"><?php echo e($org['user_name']); ?></div>
                                        <small class="text-muted"><?php echo e($org['user_email']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?php echo e($org['bio']); ?></small>
                                        <?php if (!empty($org['rejection_reason'])): ?>
                                            <div class="small text-danger mt-1">
                                                <strong>Rejection Reason:</strong> <?php echo e($org['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo format_datetime($org['created_at'], 'M d, Y'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-status badge-<?php echo e($org['approval_status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', e($org['approval_status']))); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($org['approval_status'] === 'pending'): ?>
                                            <div class="d-inline-flex gap-1">
                                                <!-- One-click Approve -->
                                                <form action="organizers.php" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="profile_id" value="<?php echo (int)$org['profile_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve Organizer">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Modal Trigger -->
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectOrgModal-<?php echo (int)$org['profile_id']; ?>">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </div>

                                            <!-- Rejection Modal -->
                                            <div class="modal fade" id="rejectOrgModal-<?php echo (int)$org['profile_id']; ?>" tabindex="-1" aria-labelledby="rejectOrgLabel-<?php echo (int)$org['profile_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog text-start">
                                                    <div class="modal-content">
                                                        <form action="organizers.php" method="POST">
                                                            <?php echo csrf_field(); ?>
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="profile_id" value="<?php echo (int)$org['profile_id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold" id="rejectOrgLabel-<?php echo (int)$org['profile_id']; ?>">Reject Organizer Profile</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p class="small text-muted mb-2">Provide a reason for rejecting this organization profile:</p>
                                                                <textarea class="form-control" name="rejection_reason" rows="3" placeholder="e.g. Society registration documents pending verification..." required></textarea>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php elseif ($org['approval_status'] === 'approved'): ?>
                                            <!-- Suspend Action (FR-ORG-006) -->
                                            <form action="organizers.php" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="profile_id" value="<?php echo (int)$org['profile_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Suspend Organizer Privileges">
                                                    <i class="bi bi-pause-circle me-1"></i> Suspend
                                                </button>
                                            </form>
                                        <?php elseif ($org['approval_status'] === 'suspended'): ?>
                                            <!-- Reactivate Action -->
                                            <form action="organizers.php" method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="profile_id" value="<?php echo (int)$org['profile_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore Organizer Access">
                                                    <i class="bi bi-play-circle me-1"></i> Restore
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
