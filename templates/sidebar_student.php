<?php
/**
 * Student Dashboard Sidebar Partial
 */
$baseUrl = defined('APP_URL') ? APP_URL : '';
$studentActive = $studentActive ?? 'dashboard';
?>
<aside class="dashboard-sidebar" aria-label="Student Menu">
    <div class="d-flex align-items-center gap-2 px-3 pb-3 mb-3 border-bottom">
        <div class="stat-card-icon stat-icon-primary" style="width: 38px; height: 38px;">
            <i class="bi bi-mortarboard fs-5"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-dark">Student Portal</h6>
            <small class="text-muted">Personal Hub</small>
        </div>
    </div>

    <div class="dashboard-sidebar-title">Activities</div>
    <ul class="nav flex-column dashboard-nav mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo ($studentActive === 'dashboard') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($studentActive === 'bookings') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/bookings.php">
                <i class="bi bi-ticket-perforated"></i> My Bookings & Tickets
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($studentActive === 'wishlist') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/wishlist.php">
                <i class="bi bi-heart"></i> Saved Wishlist
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($studentActive === 'waitlist') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/waitlist.php">
                <i class="bi bi-hourglass-split"></i> Waitlist Queue
            </a>
        </li>
    </ul>

    <div class="dashboard-sidebar-title">Actions</div>
    <ul class="nav flex-column dashboard-nav">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $baseUrl; ?>/index.php">
                <i class="bi bi-compass"></i> Browse Events
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-danger" href="<?php echo $baseUrl; ?>/pages/auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </li>
    </ul>
</aside>
