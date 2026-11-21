<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($subjectTitle ?? 'Eventora Notification'); ?></title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .header { background: <?php echo !empty($isApproved) ? '#1b5e20' : '#b71c1c'; ?>; color: #ffffff; padding: 24px 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
    .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.9; }
    .content { padding: 30px; }
    .badge { display: inline-block; background: <?php echo !empty($isApproved) ? '#2e7d32' : '#c62828'; ?>; color: #ffffff; font-size: 12px; font-weight: 700; text-transform: uppercase; padding: 4px 12px; border-radius: 50px; margin-bottom: 16px; }
    .reason-box { background: #ffebee; border-left: 4px solid #c62828; padding: 14px 18px; margin: 18px 0; font-size: 14px; color: #b71c1c; }
    .btn { display: inline-block; background: #1a237e; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 15px; margin: 20px 0 10px; text-align: center; }
    .footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 20px 30px; text-align: center; font-size: 12px; color: #888888; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Eventora</h1>
        <p>Administrative Decision Notification</p>
    </div>
    <div class="content">
        <span class="badge"><?php echo htmlspecialchars($statusBadge ?? 'Decision'); ?></span>
        <h2>Hello, <?php echo htmlspecialchars($name ?? 'User'); ?>!</h2>
        <p><?php echo htmlspecialchars($message ?? 'Your application status has been updated.'); ?></p>

        <?php if (!empty($reason)): ?>
            <div class="reason-box">
                <strong>Administrative Reason / Feedback:</strong><br>
                <?php echo nl2br(htmlspecialchars($reason)); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($actionUrl)): ?>
            <div style="text-align: center;">
                <a href="<?php echo htmlspecialchars($actionUrl); ?>" class="btn"><?php echo htmlspecialchars($actionText ?? 'Go to Dashboard'); ?></a>
            </div>
        <?php endif; ?>
    </div>
    <div class="footer">
        <p>This is an automated notification from <?php echo APP_NAME; ?> administration. Please do not reply directly to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> Eventora · Faculty of Computing and Information Technology · NIIBS</p>
    </div>
</div>
</body>
</html>
