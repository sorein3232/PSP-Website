<?php
/**
 * Session Idle Time Tracker
 * 
 * This file manages session timeout after 15 minutes of inactivity.
 * It can be included at the beginning of any PHP script to enforce
 * idle time detection and automatic logout.
 * 
 * How to use:
 * 1. Place this file in a common directory accessible by both admin and user pages
 * 2. Include this file at the top of any PHP page: include 'path/to/session_timeout.php';
 * 3. The script will automatically handle idle time detection and inject the needed JavaScript
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$idle_time = 15 * 60; // 15 minutes in seconds (15 * 60 = 900)
$admin_redirect = "/admin/timeout.php"; // Redirect URL for admins when session expires
$user_redirect = "/frontend/logout.php"; // Redirect URL for regular users when session expires

// Determine if the current script is running in admin directory
function isAdminPath() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    return (strpos($script_path, '/admin/') !== false);
}

/**
 * Updates the last activity timestamp
 * Call this function whenever user activity is detected
 */
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * Checks if the user is idle and redirects if necessary
 * This runs automatically when the file is included
 */
function checkIdleTime() {
    global $idle_time, $admin_redirect, $user_redirect;
    
    // Initialize last_activity timestamp if it doesn't exist
    if (!isset($_SESSION['last_activity'])) {
        updateLastActivity();
        return;
    }
    
    // Check if user has been idle for too long
    if ((time() - $_SESSION['last_activity']) > $idle_time) {
        // Determine if user is admin or regular user based on current path or session variable
        $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : isAdminPath();
        $redirect_url = $is_admin ? $admin_redirect : $user_redirect;
        
        // Clear session data
        session_unset();
        session_destroy();
        
        // Make sure no output has been sent before this point
        if (!headers_sent()) {
            // First show the alert and then redirect
            echo '<script>alert("Your session has expired due to inactivity."); window.location.href="'.$redirect_url.'?reason=idle";</script>';
            exit;
        } else {
            // JavaScript fallback if headers already sent
            echo '<script>alert("Your session has expired due to inactivity."); window.location.href="'.$redirect_url.'?reason=idle";</script>';
            exit;
        }
    }
}

/**
 * Reset idle timer on AJAX requests
 * Can be called via AJAX to update last activity without reloading the page
 */
function handleAjaxActivity() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_activity') {
        updateLastActivity();
        echo json_encode(['status' => 'success', 'timestamp' => $_SESSION['last_activity']]);
        exit;
    }
}

/**
 * Output JavaScript for idle time tracking
 * This automatically adds the necessary JavaScript to the page
 */
function outputIdleJavaScript() {
    // Path to the common directory where both JS and PHP files are located
    $file_path = __FILE__;
    $dir_path = dirname($file_path);
    $js_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $dir_path) . '/idle_tracker.js';
    
    echo "<script src='$js_path'></script>";
}

// Process AJAX requests if any
handleAjaxActivity();

// Check for idle time on every page load
checkIdleTime();

// Update last activity by default when this file is included
updateLastActivity();

// We'll handle the JavaScript output differently to avoid header issues
$outputJs = true;

// Register shutdown function only if we need to output JavaScript
if ($outputJs) {
    register_shutdown_function(function() {
        // Only output JS if we're not handling an AJAX request
        if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            outputIdleJavaScript();
        }
    });
}
?>