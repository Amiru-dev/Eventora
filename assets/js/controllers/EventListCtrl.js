/**
 * Eventora - EventListCtrl (AngularJS Event Discovery Controller)
 * 
 * Implements:
 * - FR-EVT-002: AngularJS loads data from PHP JSON endpoint without page reload
 * - FR-EVT-003: Search keyword, filter by category and date combined with AND logic
 * - FR-EVT-004: Sort events by date and price
 * - FR-EVT-005: Shows title, description, category, venue, date/time, price, capacity and availability
 */

angular.module('eventoraApp').controller('EventListCtrl', ['$scope', 'EventService', function ($scope, EventService) {
    // Model state
    $scope.events = [];
    $scope.isLoading = true;
    $scope.errorMessage = '';

    // Active filters
    $scope.searchQuery = '';
    $scope.selectedCategory = '';
    $scope.selectedDate = '';

    // Sorting state (FR-EVT-004)
    // Options: 'date_asc', 'date_desc', 'price_asc', 'price_desc'
    $scope.sortBy = 'date_asc';

    // Pre-defined category list for chip selection
    $scope.categories = ['Workshop', 'Seminar', 'Sports', 'Cultural', 'Tech'];

    /**
     * Load events from backend on controller init
     */
    $scope.loadEvents = function () {
        $scope.isLoading = true;
        $scope.errorMessage = '';

        EventService.getEvents()
            .then(function (events) {
                $scope.events = events;
                $scope.isLoading = false;
            })
            .catch(function (error) {
                console.error('Failed to load events:', error);
                $scope.errorMessage = 'Could not load campus events. Please check your connection.';
                $scope.isLoading = false;
            });
    };

    /**
     * Set active category filter chip
     */
    $scope.setCategory = function (category) {
        if ($scope.selectedCategory === category) {
            $scope.selectedCategory = ''; // Toggle off if already selected
        } else {
            $scope.selectedCategory = category;
        }
    };

    /**
     * Clear all active filters
     */
    $scope.resetFilters = function () {
        $scope.searchQuery = '';
        $scope.selectedCategory = '';
        $scope.selectedDate = '';
        $scope.sortBy = 'date_asc';
    };

    /**
     * Custom filter combining keyword, category and date with strict AND logic (FR-EVT-003)
     * @param {Object} event
     * @return {boolean}
     */
    $scope.customFilter = function (event) {
        // 1. Keyword search condition (matches Title, Description, Venue, or Organizer)
        if ($scope.searchQuery && $scope.searchQuery.trim() !== '') {
            var q = $scope.searchQuery.toLowerCase().trim();
            var titleMatch = event.title && event.title.toLowerCase().indexOf(q) !== -1;
            var descMatch = event.description && event.description.toLowerCase().indexOf(q) !== -1;
            var venueMatch = event.venue && event.venue.toLowerCase().indexOf(q) !== -1;
            var orgMatch = event.organizer_name && event.organizer_name.toLowerCase().indexOf(q) !== -1;

            if (!(titleMatch || descMatch || venueMatch || orgMatch)) {
                return false; // Failed keyword filter
            }
        }

        // 2. Category filter condition (AND logic)
        if ($scope.selectedCategory && $scope.selectedCategory !== '') {
            if (!event.category || event.category.toLowerCase() !== $scope.selectedCategory.toLowerCase()) {
                return false; // Failed category filter
            }
        }

        // 3. Date filter condition (AND logic)
        if ($scope.selectedDate && $scope.selectedDate !== '') {
            if (!event.start_date || event.start_date.substring(0, 10) !== $scope.selectedDate) {
                return false; // Failed date filter
            }
        }

        // All active filters passed (AND combined)
        return true;
    };

    /**
     * Determine sort expression and direction for AngularJS orderBy filter (FR-EVT-004)
     */
    $scope.getSortPredicate = function () {
        switch ($scope.sortBy) {
            case 'date_desc':
                return '-start_date';
            case 'price_asc':
                return 'price';
            case 'price_desc':
                return '-price';
            case 'date_asc':
            default:
                return 'start_date';
        }
    };

    // Initialize events on page load
    $scope.loadEvents();
}]);
