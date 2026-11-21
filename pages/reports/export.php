<?php
/**
 * Eventora - Secure CSV Export Handler
 * FR-REP-005, NFR-SEC-008
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/events.php';
require_once __DIR__ . '/../../includes/reports.php';

require_login();

$currentUser = current_user();
$type = trim($_GET['type'] ?? 'system_bookings');
$eventId = (int)($_GET['event_id'] ?? 0);

// Admin Exports
if ($type === 'system_bookings') {
    require_role('admin');
    export_bookings_csv();
}

// Organizer / Admin Scoped Exports
if ($type === 'org_bookings') {
    require_role(['organizer', 'admin']);
    $orgId = null;
    if ($currentUser['role'] === 'organizer') {
        $orgProfile = get_organizer_profile((int)$currentUser['user_id']);
        $orgId = (int)($orgProfile['profile_id'] ?? 0);
    }
    export_bookings_csv($orgId);
}

// Attendee Roster Export
if ($type === 'attendees') {
    require_role(['organizer', 'admin']);
    if ($eventId <= 0) {
        die('Invalid event ID specified.');
    }
    if ($currentUser['role'] === 'organizer' && !can_manage_event($eventId)) {
        die('Unauthorized: You can only export rosters for your own events.');
    }
    export_attendees_csv($eventId);
}

die('Invalid export type requested.');
