<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Event Reminder - <?php echo htmlspecialchars($event['title'] ?? 'Eventora'); ?></title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .header { background: #0288d1; color: #ffffff; padding: 24px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
    .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.85; }
    .content { padding: 30px; }
    .badge-reminder { display: inline-block; background: #0288d1; color: #ffffff; font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 12px; border-radius: 50px; margin-bottom: 16px; }
    .details-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 18px 20px; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
    .row:last-child { margin-bottom: 0; }
    .label { color: #6c757d; font-weight: 600; }
    .value { font-weight: 700; color: #212529; text-align: right; }
    .btn { display: inline-block; background: #0288d1; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; margin: 20px 0 10px; text-align: center; }
    .footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 20px 30px; text-align: center; font-size: 12px; color: #888888; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Eventora</h1>
        <p>Upcoming Event Reminder (24-Hour Notice)</p>
    </div>
    <div class="content">
        <span class="badge-reminder">Starts in 24 Hours</span>
        <h2>Hello, <?php echo htmlspecialchars($user['name'] ?? 'Student'); ?>!</h2>
        <p>This is a quick reminder that your registered event <strong><?php echo htmlspecialchars($event['title'] ?? 'Event'); ?></strong> takes place tomorrow!</p>
        
        <div class="details-box">
            <div class="row">
                <span class="label">Date & Time:</span>
                <span class="value"><?php echo htmlspecialchars($event['formatted_date'] ?? $event['start_date'] ?? 'Tomorrow'); ?></span>
            </div>
            <div class="row">
                <span class="label">Venue:</span>
                <span class="value"><?php echo htmlspecialchars($event['venue'] ?? 'Campus Venue'); ?></span>
            </div>
            <div class="row">
                <span class="label">Seats Reserved:</span>
                <span class="value"><?php echo (int)($booking['seats'] ?? 1); ?> Seat(s)</span>
            </div>
            <div class="row">
                <span class="label">Booking Reference:</span>
                <span class="value"><?php echo htmlspecialchars($booking['booking_ref'] ?? 'N/A'); ?></span>
            </div>
        </div>

        <p>Please arrive 10-15 minutes prior to start time with your electronic QR ticket ready on your phone or printed out.</p>

        <div style="text-align: center;">
            <a href="<?php echo htmlspecialchars($ticketUrl ?? (APP_URL . '/pages/student/bookings.php')); ?>" class="btn">Access Your E-Ticket</a>
        </div>
    </div>
    <div class="footer">
        <p>This is an automated 24-hour reminder from <?php echo APP_NAME; ?>. Please do not reply directly to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> Eventora · Faculty of Computing and Information Technology · NIIBS</p>
    </div>
</div>
</body>
</html>
