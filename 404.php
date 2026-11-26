<?php
/**
 * Custom 404 Error Page
 * Animated error page for Eventora
 */

http_response_code(404);
require_once __DIR__ . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Eventora</title>
    <link rel="icon" type="image/png" href="<?php echo APP_URL; ?>/assets/img/logo.png">
    <link href="<?php echo APP_URL; ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/vendor/bootstrap/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        .error-container {
            text-align: center;
            color: white;
            padding: 2rem;
            position: relative;
        }

        .error-code {
            font-size: 12rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0;
            position: relative;
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        .error-code span {
            display: inline-block;
            animation: bounce 1s ease-in-out infinite;
        }

        .error-code span:nth-child(1) { animation-delay: 0s; }
        .error-code span:nth-child(2) { animation-delay: 0.1s; }
        .error-code span:nth-child(3) { animation-delay: 0.2s; }

        .error-message {
            font-size: 2rem;
            font-weight: 600;
            margin: 1.5rem 0;
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        .error-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }

        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            font-size: 2rem;
            opacity: 0.2;
            animation: floatAround 15s linear infinite;
        }

        .floating-icon:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 20%; right: 15%; animation-delay: 2s; }
        .floating-icon:nth-child(3) { bottom: 30%; left: 20%; animation-delay: 4s; }
        .floating-icon:nth-child(4) { bottom: 15%; right: 10%; animation-delay: 6s; }
        .floating-icon:nth-child(5) { top: 50%; left: 5%; animation-delay: 8s; }
        .floating-icon:nth-child(6) { top: 60%; right: 25%; animation-delay: 10s; }

        .btn-home {
            padding: 12px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            background: white;
            color: #667eea;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out 0.7s both;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-home:hover,
        .btn-home:focus,
        .btn-home:active {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            background: #f8f9fa;
            color: #5a67d8;
        }

        .search-box {
            margin: 2rem auto;
            max-width: 400px;
            animation: fadeInUp 0.8s ease-out 0.9s both;
        }

        .search-box input {
            border-radius: 50px;
            padding: 12px 25px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .search-box button {
            border-radius: 50px;
            padding: 12px 25px;
            background: white;
            border: none;
            color: #667eea;
            font-weight: 600;
        }

        .search-box button:hover,
        .search-box button:focus,
        .search-box button:active {
            background: #f8f9fa;
            color: #5a67d8;
        }

        .helpful-links {
            margin-top: 2rem;
            animation: fadeInUp 0.8s ease-out 1.1s both;
        }

        .helpful-links a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-size: 0.95rem;
            transition: opacity 0.3s ease;
            opacity: 0.8;
        }

        .helpful-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes floatAround {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(50px, -50px) rotate(90deg);
            }
            50% {
                transform: translate(0, -100px) rotate(180deg);
            }
            75% {
                transform: translate(-50px, -50px) rotate(270deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 8rem;
            }
            .error-message {
                font-size: 1.5rem;
            }
            .error-description {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-icons">
        <i class="bi bi-calendar-event floating-icon"></i>
        <i class="bi bi-ticket-perforated floating-icon"></i>
        <i class="bi bi-people floating-icon"></i>
        <i class="bi bi-star floating-icon"></i>
        <i class="bi bi-heart floating-icon"></i>
        <i class="bi bi-geo-alt floating-icon"></i>
    </div>

    <div class="error-container">
        <div class="mb-2">
            <img src="<?php echo APP_URL; ?>/assets/img/logo.png" alt="Eventora Logo" width="64" height="64" class="object-fit-contain bg-white rounded-circle p-2 shadow">
        </div>
        <h1 class="error-code">
            <span>4</span><span>0</span><span>4</span>
        </h1>
        <h2 class="error-message">Oops! Page Not Found</h2>
        <p class="error-description">
            The page you're looking for seems to have wandered off to another event. 
            Let's get you back to discovering amazing campus experiences!
        </p>

        <form class="search-box" action="<?php echo APP_URL; ?>/" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search for events..." name="search">
                <button type="submit" class="btn">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

        <a href="<?php echo APP_URL; ?>/" class="btn btn-home">
            <i class="bi bi-house-door me-2"></i>Back to Home
        </a>

        <div class="helpful-links">
            <a href="<?php echo APP_URL; ?>/pages/auth/login.php">Log In</a>
            <a href="<?php echo APP_URL; ?>/pages/auth/register.php">Register</a>
            <a href="<?php echo APP_URL; ?>/#events">Browse Events</a>
        </div>
    </div>

    <script src="<?php echo APP_URL; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
