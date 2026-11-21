<?php
/**
 * Eventora - System Audit Trail & Security Log
 * FR-AUD-001, NFR-AUD-001, NFR-SEC-001 to NFR-SEC-009
 * 
 * Provides campus administrators with a tamper-resistant, filterable audit log
 * recording user authentication, approval decisions, financial state transitions,
 * venue check-ins, and security anomalies.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/reports.php';
require_once __DIR__ . '/../../includes/audit.php';

require_role('admin');

$pageTitle = 'System Audit Trail · ' . APP_NAME;
$activePage = 'admin_audit';
$adminActive = 'audit';

// Pagination & Filter Parameters
$category  = trim($_GET['category'] ?? 'all');
$search    = trim($_GET['search'] ?? '');
$dateFrom  = trim($_GET['date_from'] ?? '');
$dateTo    = trim($_GET['date_to'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 25;
$offset    = ($page - 1) * $limit;

$filters = [
    'category'  => $category,
    'search'    => $search,
    'date_from' => $dateFrom,
    'date_to'   => $dateTo
];

$totalRecords = get_audit_log_count($filters);
$logs         = get_audit_logs($filters, $limit, $offset);
$totalPages   = max(1, ceil($totalRecords / $limit));
$categories   = get_audit_action_categories();

// Build query string for pagination & CSV export
$queryParams = array_filter([
    'category'  => $category !== 'all' ? $category : null,
    'search'    => $search ?: null,
    'date_from' => $dateFrom ?: null,
    'date_to'   => $dateTo ?: null
]);
$exportQueryString = http_build_query(array_merge($queryParams, ['type' => 'audit_logs']));

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_admin.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">System Audit Trail & Security Log</h1>
                    <p class="text-muted small mb-0">Immutable records of administrative decisions, authentication, payments and venue check-ins.</p>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <a href="<?php echo APP_URL; ?>/pages/reports/export.php?<?php echo e($exportQueryString); ?>" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Filtered CSV (FR-REP-005)
                    </a>
                </div>
            </div>

            <!-- Audit Filter Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="audit_log.php" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="filterCategory" class="form-label small fw-semibold text-muted mb-1">Activity Category</label>
                            <select name="category" id="filterCategory" class="form-select form-select-sm">
                                <?php foreach ($categories as $catKey => $catLabel): ?>
                                    <option value="<?php echo e($catKey); ?>" <?php echo $category === $catKey ? 'selected' : ''; ?>>
                                        <?php echo e($catLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterSearch" class="form-label small fw-semibold text-muted mb-1">Keyword Search</label>
                            <input type="text" name="search" id="filterSearch" class="form-control form-select-sm" 
                                   placeholder="Action, actor, email, IP..." value="<?php echo e($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="dateFrom" class="form-label small fw-semibold text-muted mb-1">From Date</label>
                            <input type="date" name="date_from" id="dateFrom" class="form-control form-select-sm" value="<?php echo e($dateFrom); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="dateTo" class="form-label small fw-semibold text-muted mb-1">To Date</label>
                            <input type="date" name="date_to" id="dateTo" class="form-control form-select-sm" value="<?php echo e($dateTo); ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-funnel me-1"></i> Filter
                            </button>
                            <a href="audit_log.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Records Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 fw-bold mb-0">Audit Trail Entries</h2>
                        <small class="text-muted">Showing page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo number_format($totalRecords); ?> total records)</small>
                    </div>
                    <span class="badge bg-light text-muted border">Traceable to Actor & IP (NFR-AUD-001)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0" aria-label="System Audit Trail">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 70px;">ID</th>
                                <th scope="col" style="width: 170px;">Timestamp</th>
                                <th scope="col">Actor</th>
                                <th scope="col">Action</th>
                                <th scope="col">Entity</th>
                                <th scope="col">Client IP</th>
                                <th scope="col" class="text-end">Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-journal-x fs-1 d-block text-muted mb-2"></i>
                                        No audit entries match the current filter criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $act = $log['action'];
                                        $badgeClass = 'bg-secondary';
                                        if (str_starts_with($act, 'auth.login_success')) {
                                            $badgeClass = 'bg-success';
                                        } elseif (str_starts_with($act, 'auth.login_failure') || str_starts_with($act, 'security.')) {
                                            $badgeClass = 'bg-danger';
                                        } elseif (str_contains($act, 'approved')) {
                                            $badgeClass = 'bg-success';
                                        } elseif (str_contains($act, 'rejected') || str_contains($act, 'cancelled')) {
                                            $badgeClass = 'bg-warning text-dark';
                                        } elseif (str_starts_with($act, 'payment.verified')) {
                                            $badgeClass = 'bg-primary';
                                        } elseif (str_starts_with($act, 'checkin.successful')) {
                                            $badgeClass = 'bg-info text-dark';
                                        } elseif (str_starts_with($act, 'checkin.duplicate')) {
                                            $badgeClass = 'bg-danger';
                                        } elseif (str_starts_with($act, 'promo.')) {
                                            $badgeClass = 'bg-purple text-white';
                                        }
                                    ?>
                                    <tr>
                                        <td><span class="text-muted fw-mono small">#<?php echo (int)$log['log_id']; ?></span></td>
                                        <td>
                                            <div class="small fw-semibold"><?php echo e(format_datetime($log['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['user_name'])): ?>
                                                <div class="fw-semibold text-dark"><?php echo e($log['user_name']); ?></div>
                                                <small class="text-muted d-block"><?php echo e($log['user_email']); ?></small>
                                                <?php if (!empty($log['user_role'])): ?>
                                                    <span class="badge badge-sm bg-light text-dark border mt-1"><?php echo e(ucfirst($log['user_role'])); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic small">System / Anonymous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $badgeClass; ?> p-2 fw-medium">
                                                <code><?php echo e($log['action']); ?></code>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['entity_type'])): ?>
                                                <span class="small fw-semibold text-dark"><?php echo e(ucfirst($log['entity_type'])); ?></span>
                                                <?php if (!empty($log['entity_id'])): ?>
                                                    <span class="text-muted small">#<?php echo (int)$log['entity_id']; ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code class="small text-muted"><?php echo e($log['ip_address'] ?? '127.0.0.1'); ?></code>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!empty($log['details'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#auditModal<?php echo (int)$log['log_id']; ?>">
                                                    <i class="bi bi-code-slash me-1"></i> Details
                                                </button>

                                                <!-- Audit Context Modal -->
                                                <div class="modal fade" id="auditModal<?php echo (int)$log['log_id']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo (int)$log['log_id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content text-start">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title h6 fw-bold" id="modalLabel<?php echo (int)$log['log_id']; ?>">
                                                                    <i class="bi bi-shield-check me-2 text-primary"></i>Audit Entry #<?php echo (int)$log['log_id']; ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-2">
                                                                    <strong>Action:</strong> <code><?php echo e($log['action']); ?></code>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <strong>Timestamp:</strong> <?php echo e(format_datetime($log['created_at'])); ?>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <strong>Actor:</strong> <?php echo e($log['user_name'] ?? 'System'); ?> (<?php echo e($log['user_email'] ?? 'N/A'); ?>)
                                                                </div>
                                                                <div class="mb-2">
                                                                    <strong>Client IP:</strong> <code><?php echo e($log['ip_address'] ?? '127.0.0.1'); ?></code>
                                                                </div>
                                                                <div class="mt-3">
                                                                    <strong>Payload Context:</strong>
                                                                    <pre class="bg-light p-3 rounded mt-1 border small mb-0" style="max-height: 200px; overflow-y: auto;"><code><?php 
                                                                        $raw = $log['details'];
                                                                        $decoded = json_decode($raw, true);
                                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                                            echo e(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                                                        } else {
                                                                            echo e($raw);
                                                                        }
                                                                    ?></code></pre>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></small>
                    <nav aria-label="Audit pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo e(http_build_query(array_merge($queryParams, ['page' => $page - 1]))); ?>">Previous</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo e(http_build_query(array_merge($queryParams, ['page' => $i]))); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo e(http_build_query(array_merge($queryParams, ['page' => $page + 1]))); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
