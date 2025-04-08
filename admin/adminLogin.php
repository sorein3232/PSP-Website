<?php
session_start();
require_once "database.php";  // Include your database connection

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: appointmentAdmin.php");
    exit();
}

// Max attempts and lockout duration (in seconds)
$maxAttempts = 5;
$initialLockoutSeconds = 60; // 1 minute lockout initially

// Variables to store lockout information for JavaScript
$isLocked = false;
$lockoutSeconds = 0;

// Process the form when it's submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
    } else {
        // Check if the email exists and get account details
        $stmt = $conn->prepare("SELECT id, email, password, login_attempts, lock_until, lockout_count FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($adminId, $adminEmail, $hashedPassword, $loginAttempts, $lockUntil, $lockoutCount);
            $stmt->fetch();

            // Initialize lockoutCount if NULL
            $lockoutCount = $lockoutCount ?? 0;
            
            $currentTime = time();

            // Check if the account is locked
            if ($lockUntil && $currentTime < strtotime($lockUntil)) {
                $remainingLockout = strtotime($lockUntil) - $currentTime;
                $_SESSION['login_error'] = "⏳ Account is locked. Please wait:";
                $isLocked = true;
                $lockoutSeconds = $remainingLockout;
            } else {
                // Reset attempts if lockout time has passed
                if ($lockUntil && $currentTime >= strtotime($lockUntil)) {
                    $loginAttempts = 0;
                    $lockUntil = null;
                    // Do NOT reset lockout_count here - it should persist across lockout periods
                    $conn->query("UPDATE admin SET login_attempts = 0, lock_until = NULL WHERE email = '$email'");
                }

                // Verify the password
                if (password_verify($password, $hashedPassword)) {
                    // Reset attempts on successful login
                    // Also reset lockout_count on successful login (optional, can be removed if you want counts to persist)
                    $conn->query("UPDATE admin SET login_attempts = 0, lock_until = NULL, lockout_count = 0 WHERE email = '$email'");

                    // Store the admin's ID in the session
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['admin_email'] = $adminEmail;

                    // Redirect to the dashboard
                    header("Location: appointmentAdmin.php");
                    exit();
                } else {
                    $loginAttempts++;

                    if ($loginAttempts >= $maxAttempts) {
                        // Increment lockout count since we're about to lock the account again
                        $lockoutCount++;
                        
                        // Calculate lockout duration with exponential backoff based on lockout_count
                        // Each time they get locked out, the duration doubles
                        $lockDuration = $initialLockoutSeconds * pow(2, $lockoutCount - 1);
                        
                        // Cap the lockout at 24 hours if desired
                        // $lockDuration = min($lockDuration, 86400); // 86400 seconds = 24 hours
                        
                        $lockUntil = date("Y-m-d H:i:s", $currentTime + $lockDuration);
                        $conn->query("UPDATE admin SET login_attempts = $loginAttempts, lock_until = '$lockUntil', lockout_count = $lockoutCount WHERE email = '$email'");
                        $_SESSION['login_error'] = "⏳ Too many failed attempts. Account locked for:";
                        $isLocked = true;
                        $lockoutSeconds = $lockDuration;
                    } else {
                        $conn->query("UPDATE admin SET login_attempts = $loginAttempts WHERE email = '$email'");
                        $remainingAttempts = $maxAttempts - $loginAttempts;
                        $_SESSION['login_error'] = "❌ Invalid email or password. Attempts left: $remainingAttempts";
                    }
                }
            }
        } else {
            $_SESSION['login_error'] = "❌ No account found with that email!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminlogin.css">
    <title>Admin Login</title>
    <style>
        .alert-container {
            text-align: center;
            margin-top: 20px;
            color: red;
        }
        .countdown {
            font-weight: bold;
            margin-top: 5px;
        }
        .success-message {
            color: green;
        }
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #2c6ac7;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Welcome, Admin!</h1>
            <form action="adminLogin.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="login" type="submit" <?php echo ($isLocked) ? 'disabled' : ''; ?>>Login</button>
            </form>
            <a href="adminRecovery.php" class="forgot-password">Forgot password?</a>
        </div>
        
        <div class="alert-container">
            <?php
            if (isset($_SESSION['login_error'])) {
                echo "<p>" . $_SESSION['login_error'] . "</p>";
                if ($isLocked) {
                    echo '<div id="countdown" class="countdown"></div>';
                }
                unset($_SESSION['login_error']);
            }
            if (isset($_SESSION['login_success'])) {
                echo "<p class='success-message'>" . $_SESSION['login_success'] . "</p>";
                unset($_SESSION['login_success']);
            }
            ?>
        </div>
    </div>

    <?php if ($isLocked): ?>
    <script>
        // Set the countdown time
        let timeLeft = <?php echo $lockoutSeconds; ?>;
        
        // Update the countdown every second
        const countdownElement = document.getElementById('countdown');
        const loginButton = document.querySelector('button.login');
        
        function updateCountdown() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            // Format the time display (add leading zeros)
            let timeDisplay = '';
            
            if (hours > 0) {
                timeDisplay += String(hours).padStart(2, '0') + ':';
            }
            
            timeDisplay += String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            
            countdownElement.innerHTML = timeDisplay;
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                countdownElement.innerHTML = "You can now try again";
                loginButton.disabled = false;
            } else {
                timeLeft--;
            }
        }
        
        // Initial call to display time immediately
        updateCountdown();
        
        // Update the countdown every second
        const timerInterval = setInterval(updateCountdown, 1000);
    </script>
    <?php endif; ?>
</body>

</html>