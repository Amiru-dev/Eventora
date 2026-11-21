<?php
/**
 * Eventora - Waitlist Offer Expiry Cron Job
 * FR-WAIT-005, BR-13
 *
 * Runs periodically (every 5 minutes) to:
 * 1. Expire waitlist offers that have passed their 30-minute window
 * 2. Auto-promote the next person in FIFO queue with a fresh offer
 *
 * Schedule with Windows Task Scheduler or crontab:
 *   Windows: schtasks /Create /SC MINUTE /MO 5 /TN "Eventora Waitlist Expiry" /TR "C:\xampp\php\php.exe C:\inetpub\wwwroot\Eventora\cron\expire_waitlist.php"
 *   Linux:   * / 5 * * * * php /var/www/html/Eventora/cron/expire_waitlist.php >> /var/log/eventora_waitlist.log 2>&1
 */

// Restrict to CLI only (security)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("403 Forbidden: This script is restricted to command-line execution.");
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/app.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/waitlist.php';

$start = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Eventora Waitlist Expiry Cron Started\n";

// Run the core expiry logic
$result = expire_waitlist_offers();

$elapsed = round(microtime(true) - $start, 3);
echo "[" . date('Y-m-d H:i:s') . "] Expired offers:   {$result['expired']}\n";
echo "[" . date('Y-m-d H:i:s') . "] Promoted to next: {$result['promoted']}\n";
echo "[" . date('Y-m-d H:i:s') . "] Completed in {$elapsed}s\n";
