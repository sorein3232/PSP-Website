/**
 * idle_tracker.js
 * Tracks user activity and updates the server to prevent session timeout
 */

(function() {
    // Get the path to the session timeout PHP file
    // This dynamically determines the correct path back to the PHP file
    const scriptPath = document.currentScript.src.substring(0, document.currentScript.src.lastIndexOf('/') + 1);
    const sessionFile = scriptPath + 'session_timeout.php';
    
    // Events that indicate user activity
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
    
    // Idle timer configuration
    const updateInterval = 60000; // Update server every minute if there's activity
    let lastActivity = Date.now();
    let activityDetected = false;
    
    // Track user activity
    function trackActivity() {
        activityDetected = true;
        lastActivity = Date.now();
    }
    
    // Update the server about user activity
    function updateServerActivity() {
        if (activityDetected) {
            fetch(sessionFile, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=update_activity',
                credentials: 'same-origin' // Include cookies for session tracking
            })
            .then(response => response.json())
            .then(data => {
                console.log('Session activity updated');
                activityDetected = false;
            })
            .catch(error => {
                console.error('Error updating session activity:', error);
            });
        }
    }
    
    // Add event listeners for user activity
    activityEvents.forEach(event => {
        document.addEventListener(event, trackActivity, { passive: true });
    });
    
    // Set up periodic updates to the server
    setInterval(updateServerActivity, updateInterval);
    
    // Initial activity update when page loads
    trackActivity();
    updateServerActivity();
    
    // Optional: You can expose functions globally if needed
    window.idleTracker = {
        updateActivity: function() {
            trackActivity();
            updateServerActivity();
        }
    };
})();