<?php
/**
 * Eventora - Admin Promo Codes Management
 * FR-PROMO-001, FR-PROMO-002, FR-PROMO-003, FR-PROMO-004, FR-PROMO-005, BR-11, BR-12
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/promos.php';

require_role('admin');

$currentUser = current_user();

// Handle Admin POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');
    $promoId = (int)($_POST['promo_id'] ?? 0);

    if ($action === 'create') {
        $result = create_promo_code($_POST, (int)$currentUser['user_id']);
        if ($result['success']) {
            set_flash('success', "Promo code '" . strtoupper(trim($_POST['code'])) . "' created successfully!");
        } else {
            set_flash('error', $result['error'] ?? 'Could not create promo code.');
        }
    } elseif ($action === 'toggle' && $promoId > 0) {
        $ok = toggle_promo_status($promoId);
        if ($ok) {
            set_flash('success', 'Promo code status updated.');
        } else {
            set_flash('error', 'Could not update promo code status.');
        }
    } elseif ($action === 'delete' && $promoId > 0) {
        $result = delete_promo_code($promoId);
        if ($result['success']) {
            if ($result['action'] === 'deactivated') {
                set_flash('info', 'Promo code has been deactivated (preserved for booking audit history).');
            } else {
                set_flash('success', 'Promo code deleted successfully.');
            }
        } else {
            set_flash('error', $result['error'] ?? 'Could not delete promo code.');
        }
    }

    redirect(APP_URL . '/pages/admin/promo_codes.php');
}

$promos = get_promo_codes();

$pageTitle = 'Promo Codes & Discounts · ' . APP_NAME;
$activePage = 'admin_promos';
$adminActive = 'promos';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">

            <?php echo flash_messages(); ?>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Promo Codes & Discounts</h1>
                    <p class="text-muted small mb-0">Configure discount vouchers, usage limits, minimum spend and expiration dates (FR-PROMO-001).</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newPromoModal">
                        <i class="bi bi-plus-lg me-1"></i> Create Promo Code
                    </button>
                </div>
            </div>

            <!-- Discount Stacking Rule Banner (BR-12) -->
            <div class="alert alert-light border shadow-sm p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill text-primary fs-5" aria-hidden="true"></i>
                <small class="text-secondary">
                    <strong>Business Rule BR-12 active:</strong> Discount stacking is disabled. The system automatically evaluates both bulk discount (10% for 5+ seats) and promo code discounts, and applies the single highest discount to the reservation.
                </small>
            </div>

            <!-- Promos Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0" aria-label="Promo Codes Table">
                        <thead>
                            <tr>
                                <th scope="col">Code</th>
                                <th scope="col">Type & Value</th>
                                <th scope="col">Min Spend</th>
                                <th scope="col">Usage Limit</th>
                                <th scope="col">Validity Window</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($promos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No promo codes created yet. Click "Create Promo Code" to get started.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promos as $p): 
                                    $isActive = ((int)$p['is_active'] === 1);
                                    $isExpired = (strtotime($p['valid_until']) < time());
                                    $hasStarted = (strtotime($p['valid_from']) <= time());

                                    $statusBadge = '<span class="badge badge-status badge-active">Active</span>';
                                    if (!$isActive) {
                                        $statusBadge = '<span class="badge bg-secondary">Inactive</span>';
                                    } elseif ($isExpired) {
                                        $statusBadge = '<span class="badge bg-danger">Expired</span>';
                                    } elseif (!$hasStarted) {
                                        $statusBadge = '<span class="badge bg-warning text-dark">Scheduled</span>';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary fs-6"><?php echo e($p['code']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($p['discount_type'] === 'percentage'): ?>
                                                <strong><?php echo (float)$p['discount_value']; ?>% Off</strong>
                                                <?php if ($p['max_discount'] !== null): ?>
                                                    <div class="small text-muted">Cap: <?php echo format_currency((float)$p['max_discount']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <strong>Fixed <?php echo format_currency((float)$p['discount_value']); ?> Off</strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $p['min_spend'] !== null ? format_currency((float)$p['min_spend']) : '<span class="text-muted">None</span>'; ?>
                                        </td>
                                        <td>
                                            <?php echo (int)$p['times_used']; ?> / <?php echo $p['total_use_limit'] !== null ? (int)$p['total_use_limit'] : '∞'; ?> uses
                                            <div class="small text-muted">(Limit <?php echo (int)$p['per_user_limit']; ?>/user)</div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>From: <?php echo format_date($p['valid_from'], 'M d, Y'); ?></div>
                                                <div>Until: <?php echo format_date($p['valid_until'], 'M d, Y'); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo $statusBadge; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <!-- Toggle Active/Inactive -->
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="promo_id" value="<?php echo (int)$p['promo_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-<?php echo $isActive ? 'warning' : 'success'; ?> btn-sm" title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="bi bi-<?php echo $isActive ? 'pause-circle' : 'play-circle'; ?>"></i>
                                                        <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>

                                                <!-- Delete / Deactivate -->
                                                <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Are you sure you want to delete promo code <?php echo e($p['code']); ?>?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="promo_id" value="<?php echo (int)$p['promo_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete promo code">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Creating Promo Code (FR-PROMO-001) -->
<div class="modal fade" id="newPromoModal" tabindex="-1" aria-labelledby="newPromoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="promo_codes.php" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newPromoModalLabel">Create New Promo Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="promo_code" class="form-label fw-semibold">Promo Code (Uppercase)</label>
                        <input type="text" class="form-control text-uppercase" id="promo_code" name="code" placeholder="e.g. CAMPUS25" required pattern="[A-Za-z0-9_-]{3,30}" title="3-30 alphanumeric characters">
                        <div class="form-text small">Students will enter this code at checkout.</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="discount_type" class="form-label fw-semibold">Discount Type</label>
                            <select class="form-select" id="discount_type" name="discount_type" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (LKR)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="discount_value" class="form-label fw-semibold">Discount Value</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="discount_value" name="discount_value" placeholder="10.00" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="min_spend" class="form-label fw-semibold">Min Spend (LKR)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="min_spend" name="min_spend" placeholder="0.00">
                            <div class="form-text small">Leave empty for none</div>
                        </div>
                        <div class="col-6">
                            <label for="max_discount" class="form-label fw-semibold">Max Cap (LKR)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="max_discount" name="max_discount" placeholder="Optional cap">
                            <div class="form-text small">For percentage only</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="total_limit" class="form-label fw-semibold">Global Uses Limit</label>
                            <input type="number" min="1" class="form-control" id="total_limit" name="total_use_limit" placeholder="e.g. 100">
                            <div class="form-text small">Leave blank for unlimited</div>
                        </div>
                        <div class="col-6">
                            <label for="per_user_limit" class="form-label fw-semibold">Per-User Limit</label>
                            <input type="number" min="1" class="form-control" id="per_user_limit" name="per_user_limit" value="1" required>
                            <div class="form-text small">Default: 1 use/student</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="valid_from" class="form-label fw-semibold">Valid From</label>
                            <input type="date" class="form-control" id="valid_from" name="valid_from" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label for="valid_until" class="form-label fw-semibold">Valid Until</label>
                            <input type="date" class="form-control" id="valid_until" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Promo Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
