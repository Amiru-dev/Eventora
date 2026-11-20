<?php
/**
 * Eventora - Public Homepage & Event Catalog
 * FR-EVT-001, FR-EVT-003, FR-EVT-004, FR-EVT-005
 * Semantic HTML5, accessible markup, responsive grid (360px to desktop)
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = 'Explore Campus Events · ' . APP_NAME;
$activePage = 'events';

// Sample events data matching seed dataset for Level 1 static UI presentation
$sampleEvents = [
    [
        'id' => 1,
        'title' => 'Introduction to Web Development',
        'category' => 'Workshop',
        'venue' => 'Lab 201, IT Building',
        'date' => '2026-11-27',
        'time' => '10:00 AM - 01:00 PM',
        'price' => 500.00,
        'capacity' => 40,
        'available' => 40,
        'organizer' => 'NIIBS Tech Club',
        'description' => 'Learn the basics of HTML, CSS and JavaScript in this hands-on workshop. Suitable for beginners with no prior coding experience.',
        'icon' => 'bi-code-slash',
        'is_free' => false
    ],
    [
        'id' => 2,
        'title' => 'AI and Machine Learning Seminar',
        'category' => 'Seminar',
        'venue' => 'Main Auditorium',
        'date' => '2026-12-04',
        'time' => '02:00 PM - 04:00 PM',
        'price' => 0.00,
        'capacity' => 200,
        'available' => 200,
        'organizer' => 'Computing Faculty',
        'description' => 'An introductory seminar on artificial intelligence and machine learning concepts, featuring guest speakers from the industry.',
        'icon' => 'bi-cpu',
        'is_free' => true
    ],
    [
        'id' => 3,
        'title' => 'Inter-Faculty Cricket Tournament',
        'category' => 'Sports',
        'venue' => 'University Cricket Ground',
        'date' => '2026-12-11',
        'time' => '08:30 AM - 05:00 PM',
        'price' => 300.00,
        'capacity' => 100,
        'available' => 100,
        'organizer' => 'Sports Council',
        'description' => 'Annual inter-faculty cricket tournament. Register your team and compete for the prestigious championship trophy.',
        'icon' => 'bi-trophy',
        'is_free' => false
    ],
    [
        'id' => 4,
        'title' => 'Cultural Night 2026',
        'category' => 'Cultural',
        'venue' => 'University Grounds',
        'date' => '2026-12-20',
        'time' => '06:00 PM - 11:00 PM',
        'price' => 200.00,
        'capacity' => 500,
        'available' => 500,
        'organizer' => 'Arts & Culture Club',
        'description' => 'Celebrate diversity with musical performances, food stalls and traditional exhibitions from students across all faculties.',
        'icon' => 'bi-music-note-beamed',
        'is_free' => false
    ],
    [
        'id' => 5,
        'title' => 'Hackathon: Code for Change',
        'category' => 'Tech',
        'venue' => 'Innovation Hub',
        'date' => '2026-11-30',
        'time' => '09:00 AM - 09:00 AM (24h)',
        'price' => 1000.00,
        'capacity' => 60,
        'available' => 60,
        'organizer' => 'IEEE Student Branch',
        'description' => 'A 24-hour hackathon where teams build software solutions for real-world campus problems. Cash prizes for top 3 teams.',
        'icon' => 'bi-laptop',
        'is_free' => false
    ],
    [
        'id' => 6,
        'title' => 'Campus Photography Workshop',
        'category' => 'Workshop',
        'venue' => 'Room 305, Arts Building',
        'date' => '2026-11-25',
        'time' => '01:00 PM - 05:00 PM',
        'price' => 750.00,
        'capacity' => 25,
        'available' => 25,
        'organizer' => 'Media Guild',
        'description' => 'Master manual camera controls, lighting and street composition with professional mentor feedback.',
        'icon' => 'bi-camera',
        'is_free' => false
    ]
];

include __DIR__ . '/templates/header.php';
?>

<!-- Hero Banner Section -->
<section class="hero-section" aria-labelledby="hero-title">
    <div class="container text-center position-relative z-1">
        <div class="hero-badge">
            <i class="bi bi-stars" aria-hidden="true"></i> NIIBS Campus Events Hub
        </div>
        <h1 id="hero-title" class="display-4 fw-extrabold text-white mb-3">Discover, Book & Experience</h1>
        <p class="lead text-light mb-4 mx-auto" style="max-width: 650px; opacity: 0.95;">
            Join workshops, seminars, sports tournaments and cultural celebrations happening across campus. Instant booking with official QR electronic tickets.
        </p>

        <!-- Search & Filter Bar -->
        <div class="card p-2 p-md-3 shadow-lg mx-auto text-start border-0" style="max-width: 820px; border-radius: 1rem;">
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <label for="search-input" class="visually-hidden">Search Events</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </span>
                        <input type="text" id="search-input" name="q" class="form-control border-start-0 ps-0" placeholder="Search by title, topic or venue...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label for="category-select" class="visually-hidden">Category</label>
                    <select id="category-select" name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Sports">Sports</option>
                        <option value="Cultural">Cultural</option>
                        <option value="Tech">Tech</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="sort-select" class="visually-hidden">Sort</label>
                    <select id="sort-select" name="sort" class="form-select">
                        <option value="date_asc">Date (Soonest)</option>
                        <option value="price_asc">Price (Low to High)</option>
                        <option value="price_desc">Price (High to Low)</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Events Catalog Section -->
<section class="py-5" aria-labelledby="catalog-heading">
    <div class="container">
        <!-- Section Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 id="catalog-heading" class="h3 fw-bold mb-1">Upcoming Campus Events</h2>
                <p class="text-muted small mb-0">Browse approved and active university events open for student reservation.</p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                <span class="badge bg-white border text-muted p-2">
                    <i class="bi bi-check2-circle text-success me-1" aria-hidden="true"></i> Showing <?php echo count($sampleEvents); ?> events
                </span>
            </div>
        </div>

        <!-- Event Cards Grid (NFR-USA-001: 360px to Desktop responsive) -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($sampleEvents as $event): ?>
                <div class="col">
                    <article class="event-card" aria-labelledby="event-title-<?php echo $event['id']; ?>">
                        <!-- Card Media -->
                        <div class="event-card-img-wrapper">
                            <i class="bi <?php echo $event['icon']; ?>" aria-hidden="true"></i>
                            <span class="event-card-badge badge-category badge-<?php echo strtolower($event['category']); ?>">
                                <?php echo htmlspecialchars($event['category']); ?>
                            </span>
                            <div class="event-price-tag">
                                <?php if ($event['is_free']): ?>
                                    <span class="text-success fw-bold">FREE</span>
                                <?php else: ?>
                                    LKR <?php echo number_format($event['price'], 2); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="event-card-body">
                            <h3 id="event-title-<?php echo $event['id']; ?>" class="event-card-title">
                                <a href="pages/event_detail.php?id=<?php echo $event['id']; ?>" class="stretched-link text-decoration-none">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </a>
                            </h3>

                            <div class="event-card-meta">
                                <div>
                                    <i class="bi bi-calendar3 text-primary" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($event['date']); ?> · <?php echo htmlspecialchars($event['time']); ?></span>
                                </div>
                                <div>
                                    <i class="bi bi-geo-alt text-danger" aria-hidden="true"></i>
                                    <span><?php echo htmlspecialchars($event['venue']); ?></span>
                                </div>
                                <div>
                                    <i class="bi bi-building text-secondary" aria-hidden="true"></i>
                                    <span>By <?php echo htmlspecialchars($event['organizer']); ?></span>
                                </div>
                            </div>

                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </p>
                        </div>

                        <!-- Card Footer -->
                        <div class="event-card-footer">
                            <div class="small">
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-people-fill text-muted me-1" aria-hidden="true"></i>
                                    <strong><?php echo $event['available']; ?></strong> / <?php echo $event['capacity']; ?> seats left
                                </span>
                            </div>
                            <span class="btn btn-outline-primary btn-sm position-relative z-2">
                                Details & Book <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </span>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promotion Banner (BR-05 Bulk Discount Callout) -->
<section class="py-4 bg-light border-top border-bottom">
    <div class="container">
        <div class="row align-items-center justify-content-between g-3">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-card-icon stat-icon-warning flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="bi bi-tag-fill fs-4" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4 class="h5 fw-bold mb-1">Student Group Discount: 10% Off on 5+ Seats!</h4>
                        <p class="text-muted small mb-0">Booking with your project team or batchmates? Bulk discount applies automatically on eligible ticket totals at checkout.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge bg-primary p-2 fs-6">Use code: <strong>WELCOME10</strong></span>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/templates/footer.php'; ?>
