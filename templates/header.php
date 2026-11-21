<?php
/**
 * Eventora - Common Header Template
 * Semantic HTML5 navigation, accessible links, responsive brand header
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$currentUser = current_user();
$currentRole = $currentUser['role'] ?? 'guest';
$pageTitle = $pageTitle ?? APP_NAME . ' - Campus Event Booking System';
$activePage = $activePage ?? '';
$baseUrl = defined('APP_URL') ? APP_URL : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Eventora - Campus Event Booking System for university clubs, seminars, workshops and student reservations.">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons (Local Offline Font) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/vendor/bootstrap/css/bootstrap-icons.min.css">
    <!-- Flaticon Uicons (For extended icons from Flaticon website: https://www.flaticon.com/uicons) -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-solid-rounded/css/uicons-solid-rounded.css">
    <!-- Eventora Custom CSS -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
</head>
<body>

    <!-- Accessible Skip Link -->
    <a href="#main-content" class="visually-hidden-focusable p-3 bg-primary text-white position-absolute start-0 top-0 z-3">Skip to main content</a>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-eventora sticky-top" aria-label="Main Navigation">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>/index.php">
                <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
                <span>Eventora</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($activePage === 'events') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/index.php">
                            <i class="bi bi-compass me-1" aria-hidden="true"></i> Explore Events
                        </a>
                    </li>
                    <?php if ($currentUser && $currentRole === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'student_bookings') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/bookings.php">
                                <i class="bi bi-ticket-perforated me-1" aria-hidden="true"></i> My Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'student_wishlist') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/wishlist.php">
                                <i class="bi bi-heart me-1" aria-hidden="true"></i> Wishlist
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'student_waitlist') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/student/waitlist.php">
                                <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i> Waitlist
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($currentUser && $currentRole === 'organizer'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'org_events') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/events.php">
                                <i class="bi bi-kanban me-1" aria-hidden="true"></i> Manage Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'org_checkin') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/organizer/checkin.php">
                                <i class="bi bi-qr-code-scan me-1" aria-hidden="true"></i> Venue Check-in
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($currentUser && $currentRole === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'admin_dashboard') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Admin Panel
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'admin_events') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/events.php">
                                <i class="bi bi-calendar-check me-1" aria-hidden="true"></i> Event Approvals
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activePage === 'admin_organizers') ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/pages/admin/organizers.php">
                                <i class="bi bi-people me-1" aria-hidden="true"></i> Organizers
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Sandbox Badge (NFR-USA-002, FR-PAY-009) -->
                <span class="badge bg-warning text-dark me-3 d-none d-md-inline-flex align-items-center gap-1" title="Payment gateway runs in test sandbox mode">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i> PayHere Sandbox
                </span>

                <!-- User Session Actions -->
                <div class="d-flex align-items-center gap-2">
                    <?php if ($currentUser): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                                <span><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
                                <span class="badge bg-secondary text-uppercase ms-1"><?php echo htmlspecialchars($currentRole); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenuButton">
                                <?php if ($currentRole === 'student'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/student/dashboard.php"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/student/bookings.php"><i class="bi bi-ticket-detailed me-2"></i>My Bookings</a></li>
                                <?php elseif ($currentRole === 'organizer'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/organizer/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Organizer Hub</a></li>
                                <?php elseif ($currentRole === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/admin/dashboard.php"><i class="bi bi-shield-check me-2"></i>Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo $baseUrl; ?>/pages/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $baseUrl; ?>/pages/auth/login.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Log In
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="registerMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                Register
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="registerMenuButton">
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/auth/register.php">
                                        <i class="bi bi-mortarboard me-2 text-primary"></i> As Student
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $baseUrl; ?>/pages/auth/register_organizer.php">
                                        <i class="bi bi-briefcase me-2 text-primary"></i> As Club Organizer
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Notifications Container (Accessible) -->
    <?php echo render_flash_messages(); ?>

    <!-- Main Content Container -->
    <main id="main-content">
