<?php
/**
 * Eventora - Student Wishlist Page
 * FR-WISH-001, FR-WISH-002
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

require_role('student');

$pageTitle = 'Saved Wishlist · ' . APP_NAME;
$activePage = 'student_wishlist';
$studentActive = 'wishlist';

include __DIR__ . '/../../templates/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../../templates/sidebar_student.php'; ?>

    <main class="dashboard-content">
        <div class="container-fluid p-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Saved Events (Wishlist)</h1>
                    <p class="text-muted small mb-0">Events you have bookmarked to track availability and booking deadlines.</p>
                </div>
            </div>

            <!-- Wishlist Grid -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <!-- Item 1 -->
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge badge-category badge-tech">Tech</span>
                                <span class="badge bg-success-subtle text-success border">Available (60 seats)</span>
                            </div>
                            <h2 class="h5 fw-bold text-dark mb-2">Hackathon: Code for Change</h2>
                            <p class="small text-muted mb-3 flex-grow-1">24-hour campus software development challenge with cash prizes.</p>
                            <div class="small text-muted mb-3">
                                <div><i class="bi bi-calendar3 me-1 text-primary"></i> Nov 30, 2026</div>
                                <div><i class="bi bi-geo-alt me-1 text-danger"></i> Innovation Hub</div>
                                <div><i class="bi bi-tag me-1 text-secondary"></i> LKR 1,000.00</div>
                            </div>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <a href="<?php echo APP_URL; ?>/pages/event_detail.php?id=5" class="btn btn-primary btn-sm flex-grow-1">Book Now</a>
                                <button class="btn btn-outline-danger btn-sm" title="Remove from wishlist"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge badge-category badge-cultural">Cultural</span>
                                <span class="badge bg-success-subtle text-success border">Available (500 seats)</span>
                            </div>
                            <h2 class="h5 fw-bold text-dark mb-2">Cultural Night 2026</h2>
                            <p class="small text-muted mb-3 flex-grow-1">Annual campus performance showcase featuring all faculties.</p>
                            <div class="small text-muted mb-3">
                                <div><i class="bi bi-calendar3 me-1 text-primary"></i> Dec 20, 2026</div>
                                <div><i class="bi bi-geo-alt me-1 text-danger"></i> University Grounds</div>
                                <div><i class="bi bi-tag me-1 text-secondary"></i> LKR 200.00</div>
                            </div>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <a href="<?php echo APP_URL; ?>/pages/event_detail.php?id=4" class="btn btn-primary btn-sm flex-grow-1">Book Now</a>
                                <button class="btn btn-outline-danger btn-sm" title="Remove from wishlist"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
