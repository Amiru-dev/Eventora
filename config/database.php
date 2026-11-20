<?php
/**
 * Database Configuration
 * 
 * PDO connection to the shared MySQL database.
 * All Eventora tables reside in this single database.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'eventora');
define('DB_USER', 'root');
define('DB_PASS', '');         // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton pattern)
 *
 * @return PDO
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error instead of displaying it
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
