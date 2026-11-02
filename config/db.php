<?php
/**
 * Database Connection Configuration
 * Module: Web and Mobile Application Development (SE102.3)
 * NSBM Green University - Faculty of Computing
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Supports Heroku JawsDB/ClearDB, Remote Env Vars, or Local XAMPP Defaults)
$cloudDbUrl = getenv('JAWSDB_URL') ?: (getenv('CLEARDB_DATABASE_URL') ?: getenv('DATABASE_URL'));
if ($cloudDbUrl && strpos($cloudDbUrl, 'mysql://') === 0) {
    $dbParts = parse_url($cloudDbUrl);
    define('DB_HOST', $dbParts['host'] ?? 'localhost');
    define('DB_PORT', $dbParts['port'] ?? '3306');
    define('DB_USER', $dbParts['user'] ?? 'root');
    define('DB_PASS', isset($dbParts['pass']) ? $dbParts['pass'] : '');
    define('DB_NAME', ltrim($dbParts['path'] ?? '', '/'));
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
    define('DB_NAME', getenv('DB_NAME') ?: 'nsbm_newsdesk');
}
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/SE102.3-WEB-Final-Project-1Y2S');
define('SITE_NAME', 'NSBM NewsDesk');

/**
 * Get PDO Database Connection
 * Uses prepared statements and exception handling
 *
 * @return PDO|null
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            if (function_exists('syncExtendedSeedArticles')) {
                syncExtendedSeedArticles($pdo);
            }
        } catch (PDOException $e) {
            // Check if database simply does not exist yet
            if ($e->getCode() === 1049) {
                // Database doesn't exist, redirect or inform user to run setup.php
                $_SESSION['db_error'] = "Database 'nsbm_newsdesk' does not exist yet. Please run <a href='setup.php' class='alert-link'>setup.php</a> to initialize the database automatically.";
            } else {
                $_SESSION['db_error'] = "Database Connection Error: " . htmlspecialchars($e->getMessage());
            }
            return null;
        }
    }

    return $pdo;
}
