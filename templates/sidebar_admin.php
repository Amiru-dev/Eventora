<?php
/**
 * Admin Dashboard Sidebar Partial
 */
$baseUrl = defined('APP_URL') ? APP_URL : '';
$adminActive = $adminActive ?? 'dashboard';
?>
<aside class="dashboard-sidebar" aria-label="Administrator Menu">
    <div class="d-flex align-items-center gap-2 px-3 pb-3 mb-3 border-bottom">
        <div class="stat-card-icon stat-icon-primary" style="width: 38px; height: 38px;">
            <i class="bi bi-shield-check fs-5"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-dark">Admin Console</h6>
            <small class="text-muted">Management & Evals</small>
        </div>
    </div>

    <div class="dashboard-sidebar-title">Main Navigation</div>
    <ul class="nav flex-column dashboard-nav mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo ($adminActive === 'dashboard') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard Overview
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($adminActive === 'events') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/events.php">
                <i class="bi bi-calendar4-week"></i> Manage All Events
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($adminActive === 'organizers') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/organizers.php">
                <i class="bi bi-person-badge"></i> Organizer Approvals
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($adminActive === 'promos') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/promo_codes.php">
                <i class="bi bi-tags"></i> Promo Codes & Rules
            </a>
        </li>
    </ul>

    <div class="dashboard-sidebar-title">System & Records</div>
    <ul class="nav flex-column dashboard-nav">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $baseUrl; ?>/index.php" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> View Public Site
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?php echo $baseUrl; ?>/pages/auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </li>
    </ul>
</aside>
