<?php
// config.php
// Database connection and global configuration file

// Start session if not started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');        // Usually 'localhost'
define('DB_NAME', 'tourism_system');   // Your database name
define('DB_USER', 'root');              // Your database username
define('DB_PASS', '');                  // Your database password

try {
    // Create PDO instance for database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If connection fails, stop script and display error message
    die("Database connection failed: " . $e->getMessage());
}

// You can add other global functions or config settings below

?>
