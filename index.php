<?php
/**
 * Eventora - Campus Event Booking System
 * Landing Page / Public Event List
 * 
 * This is the main entry point of the application.
 * Displays upcoming published events with search and filter capabilities.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Campus Event Booking System</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation will be added in Level 1 -->
    <div class="container mt-5">
        <div class="text-center">
            <h1><?php echo APP_NAME; ?></h1>
            <p class="lead">Campus Event Booking System</p>
            <p class="text-muted">Version <?php echo APP_VERSION; ?></p>
            <hr>
            <p>Project structure initialized. Building in progress...</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
