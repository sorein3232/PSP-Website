<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration
$idle_time = 5; // 15 minutes in seconds (15 * 60 = 900)
$redirect_url = "../frontend/login.php"; // Common redirect URL for both admins and users when session expires

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
 * Handles the logout process directly
 * Clears session data and redirects to login page
 */
function handleLogout($reason = 'idle') {
    global $redirect_url;
    
    // Clear session data
    session_unset();
    session_destroy();
    
    // Make sure no output has been sent before this point
    if (!headers_sent()) {
        // First show the alert and then redirect
        echo '<script>alert("Your session has expired due to inactivity."); window.location.href="'.$redirect_url.'?reason='.$reason.'";</script>';
        exit;
    } else {
        // JavaScript fallback if headers already sent
        echo '<script>alert("Your session has expired due to inactivity."); window.location.href="'.$redirect_url.'?reason='.$reason.'";</script>';
        exit;
    }
}

/**
 * Checks if the user is idle and handles logout if necessary
 * This runs automatically when the file is included
 */
function checkIdleTime() {
    global $idle_time;
    
    // Initialize last_activity timestamp if it doesn't exist
    if (!isset($_SESSION['last_activity'])) {
        updateLastActivity();
        return;
    }
    
    // Check if user has been idle for too long
    if ((time() - $_SESSION['last_activity']) > $idle_time) {
        handleLogout('idle');
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