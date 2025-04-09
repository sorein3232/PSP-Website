/**
 * idle_tracker.js
 * Tracks user activity with real-time countdown and updates the server to prevent session timeout
 */

(function() {
    // Get the path to the session timeout PHP file
    const scriptPath = document.currentScript.src.substring(0, document.currentScript.src.lastIndexOf('/') + 1);
    const sessionFile = scriptPath + 'session_timeout.php';
    
    // Events that indicate user activity
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click', 'touchend'];
    
    // Configuration - timeout from PHP or default to 900 seconds (15 minutes)
    const sessionTimeout = window.SESSION_TIMEOUT || 900;
    
    // Timer variables
    let lastActivity = Date.now();
    let activityDetected = false;
    let countdownTimer;
    let remainingTime = sessionTimeout;
    
    // Create countdown element
    function createCountdownElement() {
        const countdownContainer = document.createElement('div');
        countdownContainer.id = 'session-countdown';
        countdownContainer.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            z-index: 9999;
            display: none;
        `;
        
        const countdownText = document.createElement('div');
        countdownText.id = 'countdown-text';
        countdownText.innerHTML = 'Session expires in: <span id="countdown-time">15:00</span>';
        
        countdownContainer.appendChild(countdownText);
        document.body.appendChild(countdownContainer);
        
        return {
            container: countdownContainer,
            timeDisplay: document.getElementById('countdown-time')
        };
    }
    
    // Format time as MM:SS
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
    }
    
    // Update countdown display
    function updateCountdown() {
        const elapsedSeconds = Math.floor((Date.now() - lastActivity) / 1000);
        remainingTime = sessionTimeout - elapsedSeconds;
        
        // Update the countdown display
        if (remainingTime <= 300) { // Show countdown when 5 minutes or less remain
            countdown.container.style.display = 'block';
            countdown.timeDisplay.textContent = formatTime(remainingTime);
            
            // Make it red when less than 1 minute remains
            if (remainingTime <= 60) {
                countdown.timeDisplay.style.color = '#ff3333';
            } else {
                countdown.timeDisplay.style.color = 'white';
            }
        } else {
            countdown.container.style.display = 'none';
        }
        
        // Check if session has expired
        if (remainingTime <= 0) {
            clearInterval(countdownTimer);
            alert('Your session has expired due to inactivity.');
            
            // Determine redirect URL (This is a fallback; normally the PHP handles the redirect)
            const isAdminPath = window.location.pathname.includes('/admin/');
            const redirectUrl = isAdminPath ? 
                "/admin/timeout.php?reason=idle" : 
                "/frontend/logout.php?reason=idle";
            
            window.location.href = redirectUrl;
        }
    }
    
    // Track user activity
    function trackActivity() {
        activityDetected = true;
        lastActivity = Date.now();
        remainingTime = sessionTimeout;
        
        // Hide countdown when user is active
        if (countdown && countdown.container) {
            countdown.container.style.display = 'none';
        }
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
    
    // Create countdown element when DOM is loaded
    let countdown;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            countdown = createCountdownElement();
        });
    } else {
        countdown = createCountdownElement();
    }
    
    // Add event listeners for user activity
    activityEvents.forEach(event => {
        document.addEventListener(event, trackActivity, { passive: true });
    });
    
    // Start the countdown timer
    countdownTimer = setInterval(updateCountdown, 1000);
    
    // Set up periodic updates to the server (every minute)
    setInterval(updateServerActivity, 60000);
    
    // Initial activity update when page loads
    trackActivity();
    updateServerActivity();
    
    // Expose functions globally if needed
    window.idleTracker = {
        updateActivity: function() {
            trackActivity();
            updateServerActivity();
        },
        getRemainingTime: function() {
            return remainingTime;
        }
    };
})();