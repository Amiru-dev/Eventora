<?php
/**
 * Eventora - Admin Promo Codes Management
 * FR-PROMO-001, FR-PROMO-002, BR-11, BR-12
 */
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Promo Codes & Discounts · ' . APP_NAME;
$activePage = 'admin_promos';
$adminActive = 'promos';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Promo Codes & Discounts</h1>
                    <p class="text-muted small mb-0">Configure discount vouchers, usage limits, minimum spend and expiration dates.</p>
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
                    <strong>Business Rule BR-12 active:</strong> Discount stacking is disabled. The system automatically computes both bulk and promo rates, and applies the single highest discount to the student reservation.
                </small>
            </div>

            <!-- Promos Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-custom mb-0" aria-label="Promo Codes Table">
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
                            <tr>
                                <td><span class="badge bg-primary fs-6">WELCOME10</span></td>
                                <td><strong>10% Off</strong> (Cap LKR 200)</td>
                                <td>LKR 500.00</td>
                                <td>1 / 100 uses (1 per user)</td>
                                <td>Active until 2027-02-18</td>
                                <td><span class="badge badge-status badge-active">Active</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-pause-circle"></i> Deactivate</button>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-secondary fs-6">FLAT100</span></td>
                                <td><strong>Fixed LKR 100 Off</strong></td>
                                <td>LKR 300.00</td>
                                <td>0 / 50 uses</td>
                                <td>Active until 2027-01-19</td>
                                <td><span class="badge badge-status badge-active">Active</span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-pause-circle"></i> Deactivate</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal for Creating Promo Code -->
<div class="modal fade" id="newPromoModal" tabindex="-1" aria-labelledby="newPromoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="newPromoModalLabel">Create New Promo Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createPromoForm">
                    <div class="mb-3">
                        <label for="promo_code" class="form-label">Code (Uppercase)</label>
                        <input type="text" class="form-control text-uppercase" id="promo_code" placeholder="e.g. CAMPUS20" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="discount_type" class="form-label">Discount Type</label>
                            <select class="form-select" id="discount_type" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (LKR)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="discount_value" class="form-label">Value</label>
                            <input type="number" step="0.01" class="form-control" id="discount_value" placeholder="10.00" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="min_spend" class="form-label">Min Spend (LKR)</label>
                            <input type="number" step="0.01" class="form-control" id="min_spend" placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label for="total_limit" class="form-label">Total Uses Limit</label>
                            <input type="number" class="form-control" id="total_limit" placeholder="100">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Save Promo Code</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
