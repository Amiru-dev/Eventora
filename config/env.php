<?php
/**
 * Environment Configuration Loader
 *
 * Loads environment variables from .env file in the project root.
 * This file should be included before any other config files.
 */

if (!function_exists('loadEnv')) {
    /**
     * Load environment variables from .env file
     *
     * @param string $path Path to the .env file directory
     * @return void
     */
    function loadEnv(string $path = __DIR__ . '/..'): void
    {
        $envFile = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }

                // Set environment variable
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Load environment variables immediately
loadEnv();
