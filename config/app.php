<?php
/**
 * Application Configuration
 *
 * Global constants and settings for Eventora.
 */

// Load environment variables
require_once __DIR__ . '/env.php';

// --- Application ---
define('APP_NAME', 'Eventora');
define('APP_VERSION', '1.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/Eventora');

// --- Timezone & Locale ---
$timezone = getenv('APP_TIMEZONE') ?: 'Asia/Colombo';
date_default_timezone_set($timezone);
define('APP_TIMEZONE', $timezone);
define('APP_CURRENCY', getenv('APP_CURRENCY') ?: 'LKR');
define('APP_CURRENCY_DECIMALS', 2);

// --- Session ---
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// --- Booking Rules ---
define('MAX_SEATS_PER_STUDENT', 10);         // BR-04: Max seats per student per event
define('BULK_DISCOUNT_MIN_SEATS', 5);        // BR-05: Minimum seats for bulk discount
define('BULK_DISCOUNT_PERCENTAGE', 0.10);    // BR-05: 10% bulk discount
define('SEAT_HOLD_MINUTES', 10);             // BR-08: Pending payment hold duration
define('DISCOUNT_STACKING', false);          // BR-12: No stacking — apply best single discount

// --- Waitlist Rules ---
define('WAITLIST_OFFER_MINUTES', 30);        // BR-13: Waitlist offer expiry time

// --- Review Rules ---
define('REVIEW_MIN_RATING', 1);              // FR-REV-003
define('REVIEW_MAX_RATING', 5);              // FR-REV-003
define('REVIEW_MAX_LENGTH', 1000);           // FR-REV-003: Max review text length

// --- Reminder ---
define('REMINDER_HOURS_BEFORE', 24);         // FR-MAIL-002: Default reminder interval

// --- Pagination ---
define('EVENTS_PER_PAGE', 12);
define('BOOKINGS_PER_PAGE', 15);
define('REPORTS_PER_PAGE', 25);

// --- File Paths ---
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('ASSETS_PATH', ROOT_PATH . '/assets');
