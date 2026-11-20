<?php
/**
 * Eventora - Public Homepage & Event Catalog
 * 
 * Implements:
 * - FR-EVT-001: Shows approved, published and upcoming events ordered by start date
 * - FR-EVT-002: AngularJS loads data from PHP JSON endpoint without page reload
 * - FR-EVT-003: Search keyword, category and date filters combined using AND logic
 * - FR-EVT-004: Sort by date and price
 * - FR-EVT-005: Event cards show title, description, category, venue, schedule, price, capacity and availability
 * - FR-EVT-010: Only published events visible
 * - BR-05: Group discount highlight
 * - NFR-LOC-001: LKR currency display and Asia/Colombo schedule
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Explore Campus Events · ' . APP_NAME;
$activePage = 'events';

include __DIR__ . '/templates/header.php';
?>

<!-- Wrap content in AngularJS Application & Controller -->
<div ng-app="eventoraApp" ng-controller="EventListCtrl" class="pb-5">

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

            <!-- Search & Filter Controls (FR-EVT-003, FR-EVT-004) -->
            <div class="card p-3 p-md-4 shadow-lg mx-auto text-start border-0" style="max-width: 880px; border-radius: 1rem;">
                <div class="row g-2 align-items-center">
                    <!-- Keyword Search (Title, Description, Venue, Organizer) -->
                    <div class="col-md-5">
                        <label for="search-input" class="visually-hidden">Search Events</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input type="text"
                                   id="search-input"
                                   ng-model="searchQuery"
                                   class="form-control border-start-0 ps-0"
                                   placeholder="Search by topic, speaker or venue..."
                                   aria-label="Search events">
                            <button class="btn btn-outline-secondary border-start-0"
                                    type="button"
                                    ng-if="searchQuery"
                                    ng-click="searchQuery = ''"
                                    title="Clear search">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Date Filter (FR-EVT-003) -->
                    <div class="col-6 col-md-3">
                        <label for="date-filter" class="visually-hidden">Filter by Date</label>
                        <input type="date"
                               id="date-filter"
                               ng-model="selectedDate"
                               class="form-control"
                               title="Filter by event start date">
                    </div>

                    <!-- Sort Dropdown (FR-EVT-004) -->
                    <div class="col-6 col-md-3">
                        <label for="sort-select" class="visually-hidden">Sort Events</label>
                        <select id="sort-select" ng-model="sortBy" class="form-select">
                            <option value="date_asc">Date (Soonest)</option>
                            <option value="date_desc">Date (Latest)</option>
                            <option value="price_asc">Price (Lowest)</option>
                            <option value="price_desc">Price (Highest)</option>
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <div class="col-12 col-md-1">
                        <button type="button"
                                class="btn btn-outline-secondary w-100 p-2"
                                ng-click="resetFilters()"
                                title="Reset all filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>

                <!-- Category Chips (FR-EVT-003: AND combined) -->
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                    <span class="small fw-bold text-muted me-1">Categories:</span>
                    <button type="button"
                            class="btn btn-sm"
                            ng-class="selectedCategory === '' ? 'btn-primary' : 'btn-light border'"
                            ng-click="setCategory('')">
                        All
                    </button>
                    <button type="button"
                            class="btn btn-sm"
                            ng-repeat="cat in categories"
                            ng-class="selectedCategory === cat ? 'btn-primary' : 'btn-light border'"
                            ng-click="setCategory(cat)">
                        {{ cat }}
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Catalog Section -->
    <section class="py-5" aria-labelledby="catalog-heading">
        <div class="container">
            <!-- Section Header & Result Count -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 id="catalog-heading" class="h3 fw-bold mb-1">Upcoming Campus Events</h2>
                    <p class="text-muted small mb-0">Browse approved university activities open for student seat reservation.</p>
                </div>
                <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                    <span class="badge bg-white border text-muted p-2 shadow-sm">
                        <i class="bi bi-funnel text-primary me-1" aria-hidden="true"></i>
                        Showing <strong>{{ (events | filter:customFilter).length }}</strong> of {{ events.length }} events
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" ng-click="loadEvents()" title="Refresh events list">
                        <i class="bi bi-arrow-clockwise" ng-class="{'spin': isLoading}"></i>
                    </button>
                </div>
            </div>

            <!-- Loading Spinner (FR-EVT-002) -->
            <div ng-show="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading events...</span>
                </div>
                <p class="text-muted mt-3">Loading campus event catalog...</p>
            </div>

            <!-- Error Banner -->
            <div ng-show="errorMessage" class="alert alert-danger shadow-sm text-center py-4">
                <i class="bi bi-exclamation-triangle-fill fs-3 text-danger d-block mb-2"></i>
                <p class="mb-2 fw-semibold">{{ errorMessage }}</p>
                <button class="btn btn-outline-danger btn-sm" ng-click="loadEvents()">Retry Loading</button>
            </div>

            <!-- Empty Search State -->
            <div ng-show="!isLoading && !errorMessage && (events | filter:customFilter).length === 0" class="text-center py-5 bg-white rounded-3 border shadow-sm p-4">
                <i class="bi bi-calendar-x display-3 text-muted mb-3 d-inline-block"></i>
                <h3 class="h5 fw-bold text-dark">No Events Match Your Filters</h3>
                <p class="text-muted small mb-3">Try adjusting your keyword query, category selection or date filter.</p>
                <button class="btn btn-primary btn-sm" ng-click="resetFilters()">Clear Filters</button>
            </div>

            <!-- AngularJS Event Cards Grid (FR-EVT-001, FR-EVT-005, NFR-USA-001) -->
            <div ng-show="!isLoading && !errorMessage" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <div class="col" ng-repeat="event in filteredEvents = (events | filter:customFilter | orderBy:getSortPredicate()) track by event.event_id">
                    <article class="event-card" aria-labelledby="event-title-{{ event.event_id }}">
                        <!-- Card Media -->
                        <div class="event-card-img-wrapper">
                            <i class="bi {{ event.icon || 'bi-calendar-event' }}" aria-hidden="true"></i>
                            <span class="event-card-badge badge-category badge-{{ event.category | lowercase }}">
                                {{ event.category }}
                            </span>
                            <div class="event-price-tag">
                                <span ng-if="event.is_free" class="text-success fw-bold">FREE</span>
                                <span ng-if="!event.is_free">{{ event.price_formatted }}</span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="event-card-body">
                            <h3 id="event-title-{{ event.event_id }}" class="event-card-title">
                                <a ng-href="pages/event_detail.php?id={{ event.event_id }}" class="stretched-link text-decoration-none">
                                    {{ event.title }}
                                </a>
                            </h3>

                            <div class="event-card-meta">
                                <div>
                                    <i class="bi bi-calendar3 text-primary" aria-hidden="true"></i>
                                    <span>{{ event.start_formatted }}</span>
                                </div>
                                <div>
                                    <i class="bi bi-geo-alt text-danger" aria-hidden="true"></i>
                                    <span>{{ event.venue }}</span>
                                </div>
                                <div>
                                    <i class="bi bi-building text-secondary" aria-hidden="true"></i>
                                    <span>By {{ event.organizer_name }}</span>
                                </div>
                            </div>

                            <p class="card-text text-muted small flex-grow-1">
                                {{ event.description }}
                            </p>
                        </div>

                        <!-- Card Footer -->
                        <div class="event-card-footer">
                            <div class="small">
                                <span class="badge" ng-class="event.is_sold_out ? 'badge-soldout' : 'bg-light text-dark border'">
                                    <i class="bi" ng-class="event.is_sold_out ? 'bi-exclamation-circle' : 'bi-people-fill text-muted'" aria-hidden="true"></i>
                                    <span ng-if="!event.is_sold_out"><strong>{{ event.seats_available }}</strong> / {{ event.capacity }} seats</span>
                                    <span ng-if="event.is_sold_out">Sold Out (Waitlist Available)</span>
                                </span>
                            </div>
                            <span class="btn btn-outline-primary btn-sm position-relative z-2">
                                Details & Book <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- Promotion Callout Banner (BR-05) -->
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
                            <p class="text-muted small mb-0">Booking with your project team or batchmates? Bulk discount applies automatically on eligible ticket totals at checkout (BR-05).</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <span class="badge bg-primary p-2 fs-6">Use code: <strong>WELCOME10</strong></span>
                </div>
            </div>
        </div>
    </section>

</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
