<?php
/**
 * Organizer Dashboard Sidebar Partial
 */
$baseUrl = defined('APP_URL') ? APP_URL : '';
$orgActive = $orgActive ?? 'dashboard';
?>
<aside class="dashboard-sidebar" aria-label="Organizer Menu">
    <div class="d-flex align-items-center gap-2 px-3 pb-3 mb-3 border-bottom">
        <div class="stat-card-icon stat-icon-info" style="width: 38px; height: 38px;">
            <i class="bi bi-briefcase fs-5"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-dark">Organizer Hub</h6>
            <small class="text-muted">Event Operations</small>
        </div>
    </div>

    <div class="dashboard-sidebar-title">Manage</div>
    <ul class="nav flex-column dashboard-nav mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo ($orgActive === 'dashboard') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/dashboard.php">
                <i class="bi bi-grid-1x2"></i> Overview
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($orgActive === 'events') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/events.php">
                <i class="bi bi-calendar-check"></i> My Events
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($orgActive === 'create_event') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/event_form.php">
                <i class="bi bi-plus-circle"></i> Create New Event
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($orgActive === 'checkin') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/checkin.php">
                <i class="bi bi-qr-code-scan"></i> Ticket Check-in
            </a>
        </li>
    </ul>

    <div class="dashboard-sidebar-title">Shortcuts</div>
    <ul class="nav flex-column dashboard-nav">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $baseUrl; ?>/index.php" target="_blank">
                <i class="bi bi-box-arrow-up-right"></i> Public Event Catalog
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?php echo $baseUrl; ?>/pages/auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </li>
    </ul>
</aside>
