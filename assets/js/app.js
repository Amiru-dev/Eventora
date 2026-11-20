/**
 * Eventora - Main AngularJS Application Module
 * 
 * Provides module declaration and custom filters for campus event discovery.
 * FR-EVT-002: Client-side event catalogue rendering without page reloads.
 */

var eventoraApp = angular.module('eventoraApp', []);

// Custom filter to format Sri Lankan Rupee currency in AngularJS views
eventoraApp.filter('lkrCurrency', function () {
    return function (amount) {
        if (amount === undefined || amount === null) return 'LKR 0.00';
        var num = parseFloat(amount);
        if (isNaN(num) || num === 0) return 'FREE';
        return 'LKR ' + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };
});

// Custom filter for human-friendly date display
eventoraApp.filter('eventDate', function () {
    return function (dateString) {
        if (!dateString) return '';
        var d = new Date(dateString);
        if (isNaN(d.getTime())) return dateString;
        return d.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    };
});
