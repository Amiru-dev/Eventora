<?php
/**
 * Eventora - Common Footer Template
 * Accessible footer with campus info, links and script imports
 */
$baseUrl = defined('APP_URL') ? APP_URL : '';
?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top mt-auto py-4" role="contentinfo">
        <div class="container">
            <div class="row g-4 justify-content-between align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                        <i class="bi bi-calendar-event-fill text-primary fs-4" aria-hidden="true"></i>
                        <span class="fw-bold fs-5 text-dark">Eventora</span>
                        <span class="badge bg-light text-muted border">v1.0</span>
                    </div>
                    <p class="text-muted small mb-0">
                        Campus Event Booking System · Faculty of Computing and Information Technology | NIIBS
                    </p>
                    <p class="text-muted small">
                        All dates displayed in <strong>Asia/Colombo (GMT+5:30)</strong>. Currency in <strong>LKR</strong>.
                    </p>
                </div>
                <div class="col-md-5 text-center text-md-end">
                    <ul class="list-inline mb-2 small">
                        <li class="list-inline-item"><a href="<?php echo $baseUrl; ?>/index.php" class="text-muted">Browse Events</a></li>
                        <li class="list-inline-item text-muted">·</li>
                        <li class="list-inline-item"><a href="<?php echo $baseUrl; ?>/pages/auth/login.php" class="text-muted">Student Portal</a></li>
                        <li class="list-inline-item text-muted">·</li>
                        <li class="list-inline-item"><a href="<?php echo $baseUrl; ?>/pages/auth/register_organizer.php" class="text-muted">Host an Event</a></li>
                        <li class="list-inline-item text-muted">·</li>
                        <li class="list-inline-item"><a href="<?php echo $baseUrl; ?>/pages/admin/dashboard.php" class="text-muted">Admin</a></li>
                    </ul>
                    <div class="text-muted small">
                        &copy; <?php echo date('Y'); ?> Eventora. Academic Project by A. U. Vidanapathirana.
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="<?php echo $baseUrl; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- AngularJS 1.8 JS (for client-side discovery) -->
    <script src="<?php echo $baseUrl; ?>/assets/vendor/angularjs/angular.min.js"></script>
    <!-- Eventora Client-Side Validation & Business Rules Engine (FR-VAL-001) -->
    <script src="<?php echo $baseUrl; ?>/assets/js/validation.js"></script>
</body>
</html>
