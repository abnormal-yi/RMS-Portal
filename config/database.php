<?php
/*----------------------------------------------------------------------
  database.php  —  Database connection configuration
  Loads environment variables (or falls back to defaults), creates a PDO
  connection to MySQL, and exposes a helper function db() for reuse.
----------------------------------------------------------------------*/

// Start the PHP session so login state can be persisted across pages.
session_start();

// Database credentials — read from environment variables where possible.
// Set these env vars in your hosting panel, or edit defaults below.
// For Apache: SetEnv DB_HOST "your_host" in .htaccess or httpd.conf
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'rms_portal';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

// Attempt to connect to MySQL with a PDO connection that uses:
//   - Exception mode for error handling
//   - Associative arrays as the default fetch style
//   - Real prepared statements (emulated prepares off)
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * db()  —  Return the global PDO instance so other files can run queries.
 * Call this from any include to get a ready-to-use database handle.
 */
function db(): PDO {
    global $pdo;
    return $pdo;
}
