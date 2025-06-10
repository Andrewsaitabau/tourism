<?php
/**
 * Tourism Management System Configuration File
 * 
 * This file contains all system configurations including:
 * - Database settings
 * - Application settings
 * - Security configurations
 * - Error handling
 */

// Strict types for better code quality
declare(strict_types=1);

// ==================== SESSION MANAGEMENT ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'tourism_system');
define('DB_USER', 'root'); // Change to a restricted user in production
define('DB_PASS', ''); // Use a strong password in production
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// ==================== APPLICATION SETTINGS ====================
define('APP_NAME', 'Tourism Management System');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true); // ALWAYS set to false in production
define('APP_ENV', 'development'); // 'production' or 'development'
define('APP_TIMEZONE', 'Asia/Kolkata'); // Set your appropriate timezone

// ==================== SECURITY SETTINGS ====================
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes in seconds

// ==================== PATH CONFIGURATION ====================
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('BASE_URL', $protocol . $host . $path);
define('APP_ROOT', dirname(__DIR__)); // Points to project root

// ==================== ERROR HANDLING ====================
if (APP_DEBUG && APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/error.log');
}

// ==================== TIMEZONE SETTINGS ====================
date_default_timezone_set(APP_TIMEZONE);

// ==================== DATABASE CONNECTION ====================
/**
 * Get PDO Database Connection
 * 
 * @return PDO
 * @throws PDOException If connection fails
 */
function getDBConnection(): PDO {
    try {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Set session variables for connection verification
        $_SESSION['db_connected'] = true;
        
        return $pdo;
    } catch (PDOException $e) {
        // Log error before displaying
        error_log("Database connection failed: " . $e->getMessage());
        
        if (APP_DEBUG) {
            die("Database connection failed: " . $e->getMessage());
        } else {
            die("A database error occurred. Please try again later.");
        }
    }
}

// Initialize database connection
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    // Handle connection failure gracefully
    error_log("System startup failed - DB connection: " . $e->getMessage());
    die("System is currently unavailable. Please try again later.");
}

// ==================== AUTHENTICATION FUNCTIONS ====================
/**
 * Check if user is authenticated
 * 
 * @return bool
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id'], $_SESSION['user_ip']) && 
           $_SESSION['user_ip'] === $_SERVER['REMOTE_ADDR'];
}

/**
 * Check if user has required role
 * 
 * @param string $requiredRole
 * @return bool
 */
function hasRole(string $requiredRole): bool {
    if (!isAuthenticated()) return false;
    
    // For hierarchical roles (admin > guide > vendor > tourist)
    $roleHierarchy = [
        'admin' => 4,
        'guide' => 3,
        'vendor' => 2,
        'tourist' => 1
    ];
    
    return isset($_SESSION['role'], $roleHierarchy[$_SESSION['role']]) && 
           $roleHierarchy[$_SESSION['role']] >= $roleHierarchy[$requiredRole];
}

// ==================== SECURITY INITIALIZATION ====================
// Regenerate session ID to prevent fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
}

// ==================== CUSTOM ERROR HANDLER ====================
set_exception_handler(function(Throwable $e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    if (APP_DEBUG) {
        die("An unexpected error occurred: " . $e->getMessage());
    } else {
        die("An unexpected error occurred. Please try again later.");
    }
});