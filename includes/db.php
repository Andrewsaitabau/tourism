<?php
/**
 * Database Connection Handler
 * 
 * Establishes a secure PDO connection to MySQL database
 * with proper error handling and configuration
 */

declare(strict_types=1); // Enable strict type checking

// Database configuration constants
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'tourism_system');
if (!defined('DB_USER')) define('DB_USER', 'root'); // Replace with a restricted user in production
if (!defined('DB_PASS')) define('DB_PASS', '');     // Use a strong password in production
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('DB_COLLATION')) define('DB_COLLATION', 'utf8mb4_unicode_ci');
if (!defined('DB_TIMEOUT')) define('DB_TIMEOUT', 5); // Connection timeout in seconds

// PDO Connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    PDO::ATTR_PERSISTENT         => false,                  // Disable persistent connections
    PDO::ATTR_TIMEOUT            => DB_TIMEOUT,             // Connection timeout
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,        // Disable for local dev, enable for production
];

// Construct DSN (Data Source Name)
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

try {
    // Establish database connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Set additional MySQL session variables if needed
    $pdo->exec("SET time_zone = '+00:00';");
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
    
} catch (PDOException $e) {
    // Log the error securely
    error_log('Database connection failed: ' . $e->getMessage());
    
    // Display user-friendly message (don't expose system details in production)
    if (php_sapi_name() === 'cli') {
        die("Database connection failed. Please check your configuration.\n");
    } else {
        header('HTTP/1.1 503 Service Unavailable');
        die('<h1>Service Temporarily Unavailable</h1><p>We are experiencing technical difficulties. Please try again later.</p>');
    }
}

// Connection verification (optional)
function is_db_connected(PDO $connection): bool {
    try {
        return (bool)$connection->query('SELECT 1');
    } catch (PDOException $e) {
        return false;
    }
}

// Register connection in global scope (if needed)
if (!isset($GLOBALS['db'])) {
    $GLOBALS['db'] = $pdo;
}
