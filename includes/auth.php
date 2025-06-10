<?php
require_once 'db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user's role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Redirect to login page if not logged in
 */
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        // Store the requested URL for redirect after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login with full path
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Redirect user based on their role
 */
function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        return;
    }

    $role = getUserRole();
    $redirectPath = '';

    switch ($role) {
        case 'admin':
            $redirectPath = '/admin/dashboard.php';
            break;
        case 'driver':
        case 'chef':
        case 'manager':
            $redirectPath = '/personnel/dashboard.php'; // Fixed typo from 'personnel' to 'personnel'
            break;
        case 'tourist':
            $redirectPath = '/tourist/dashboard.php';
            break;
        default:
            $redirectPath = '/index.php';
    }

    // Check if we're already on the correct page to avoid infinite redirects
    if ($_SERVER['REQUEST_URI'] !== $redirectPath) {
        header('Location: ' . BASE_URL . $redirectPath);
        exit();
    }
}

// Only declare hasRole() if it doesn't already exist
if (!function_exists('hasRole')) {
    /**
     * Check if current user has specific role
     * @param string|array $requiredRole Role or array of roles to check
     * @return bool
     */
    function hasRole($requiredRole) {
        $userRole = getUserRole();
        
        if (!$userRole) return false;
        
        // For array of roles
        if (is_array($requiredRole)) {
            return in_array($userRole, $requiredRole);
        }
        
        // For single role
        return $userRole === $requiredRole;
    }
}

/**
 * Redirect if user doesn't have required role
 * @param string|array $requiredRole Role or array of roles required
 */
function requireRole($requiredRole) {
    redirectIfNotLoggedIn();
    
    if (!hasRole($requiredRole)) {
        header('Location: ' . BASE_URL . '/unauthorized.php');
        exit();
    }
}