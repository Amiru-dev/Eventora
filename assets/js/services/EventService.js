/**
 * Eventora - EventService (AngularJS Data Access Layer)
 * 
 * Communicates with PHP JSON endpoints via $http.
 * FR-EVT-002: Loads event data over HTTP without full page reloads.
 */

angular.module('eventoraApp').factory('EventService', ['$http', function ($http) {
    var service = {};

    /**
     * Fetch published upcoming events list from PHP backend
     * @param {Object} params (optional search, category, sort params)
     * @return {Promise}
     */
    service.getEvents = function (params) {
        return $http.get('api/events.php', { params: params })
            .then(function (response) {
                if (response.data && response.data.success) {
                    return response.data.events;
                }
                return [];
            });
    };

    /**
     * Fetch single event details
     * @param {number} id
     * @return {Promise}
     */
    service.getEventDetail = function (id) {
        return $http.get('api/event_detail.php', { params: { id: id } })
            .then(function (response) {
                if (response.data && response.data.success) {
                    return response.data.event;
                }
                return null;
            });
    };

    return service;
}]);
