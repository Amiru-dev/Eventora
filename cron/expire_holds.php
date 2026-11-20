<?php
/**
 * Eventora - Scheduled Task: Expire Abandoned Seat Holds
 * 
 * Implements:
 * - FR-BKG-010: Automatically expire abandoned pending-payment holds and release held seats
 * - BR-08: Pending-payment seat holds expire after 10 minutes; expiry releases seats in transaction
 * - NFR-REL-001: Transactional updates
 * 
 * Can be run via Windows Task Scheduler, cron or manual evaluation.
 * Schedule: Every 1 minute (* * * * *)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/bookings.php';

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] Running seat-hold expiration task...\n";

$releasedCount = expire_abandoned_seat_holds();

echo "[{$timestamp}] Finished: {$releasedCount} expired seat hold(s) released back to room capacity.\n";
