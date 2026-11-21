<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Waitlist Seat Offer - <?php echo htmlspecialchars($event['title'] ?? 'Eventora'); ?></title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .header { background: #ed6c02; color: #ffffff; padding: 24px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
    .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.9; }
    .content { padding: 30px; }
    .badge-offer { display: inline-block; background: #ed6c02; color: #ffffff; font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 12px; border-radius: 50px; margin-bottom: 16px; }
    .details-box { background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; padding: 18px 20px; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
    .row:last-child { margin-bottom: 0; }
    .label { color: #8d6e63; font-weight: 600; }
    .value { font-weight: 700; color: #3e2723; text-align: right; }
    .btn { display: inline-block; background: #2e7d32; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; margin: 20px 0 10px; text-align: center; }
    .footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 20px 30px; text-align: center; font-size: 12px; color: #888888; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Eventora</h1>
        <p>A Seat Has Opened Up for You!</p>
    </div>
    <div class="content">
        <span class="badge-offer">Waitlist Offer Active</span>
        <h2>Great news, <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>!</h2>
        <p>A seat has become available for <strong><?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></strong>, and as the next in the FIFO waitlist queue, the seat has been offered to you!</p>
        
        <div class="details-box">
            <div class="row">
                <span class="label">Event:</span>
                <span class="value"><?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></span>
            </div>
            <div class="row">
                <span class="label">Date & Time:</span>
                <span class="value"><?php echo htmlspecialchars($event['formatted_date'] ?? $event['start_date'] ?? 'TBA'); ?></span>
            </div>
            <div class="row">
                <span class="label">Seats Offered:</span>
                <span class="value"><?php echo (int)($waitlist['seats_requested'] ?? 1); ?> Seat(s)</span>
            </div>
            <div class="row">
                <span class="label">Offer Expiration Window:</span>
                <span class="value" style="color: #c62828;">30 Minutes</span>
            </div>
            <div class="row">
                <span class="label">Expires At:</span>
                <span class="value"><?php echo htmlspecialchars($waitlist['formatted_expiry'] ?? $waitlist['offer_expires_at'] ?? 'Soon'); ?></span>
            </div>
        </div>

        <p style="font-size: 13px; color: #d32f2f; font-weight: 600;">
            Important: You have 30 minutes to claim this seat. If you do not accept within 30 minutes, this offer will automatically expire and pass to the next student in the queue.
        </p>

        <div style="text-align: center;">
            <a href="<?php echo htmlspecialchars($actionUrl ?? (APP_URL . '/pages/student/waitlist.php')); ?>" class="btn">Accept & Claim Seat Now</a>
        </div>
    </div>
    <div class="footer">
        <p>This is an automated waitlist notification from <?php echo APP_NAME; ?>. Please do not reply directly to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> Eventora · Faculty of Computing and Information Technology · NIIBS</p>
    </div>
</div>
</body>
</html>
