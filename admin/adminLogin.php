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

// Process the form when it's submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all fields.";
    } else {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT id, email, password, login_attempts, lock_until FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($adminId, $adminEmail, $hashedPassword, $loginAttempts, $lockUntil);
            $stmt->fetch();
            
            $currentTime = time();
            
            // Check if the account is locked
            if ($lockUntil && $currentTime < strtotime($lockUntil)) {
                $remainingLockout = strtotime($lockUntil) - $currentTime;
                $_SESSION['lockout_end_time'] = strtotime($lockUntil) * 1000; // Convert to milliseconds for JavaScript
                $_SESSION['login_error'] = "Account is locked. Try again in:";
                $_SESSION['show_countdown'] = true;
            } else {
                // Reset attempts if lockout time has passed
                if ($lockUntil && $currentTime >= strtotime($lockUntil)) {
                    $loginAttempts = 0;
                    $lockUntil = null;
                    $conn->query("UPDATE admin SET login_attempts = 0, lock_until = NULL WHERE email = '$email'");
                }
                
                // Verify the password
                if (password_verify($password, $hashedPassword)) {
                    // Reset attempts on successful login
                    $conn->query("UPDATE admin SET login_attempts = 0, lock_until = NULL WHERE email = '$email'");
                    
                    // Store the admin's ID in the session
                    $_SESSION['admin_id'] = $adminId;
                    $_SESSION['admin_email'] = $adminEmail;
                    
                    // Redirect to the dashboard
                    header("Location: appointmentAdmin.php");
                    exit();
                } else {
                    // Increment login attempts
                    $loginAttempts++;
                    
                    if ($loginAttempts >= $maxAttempts) {
                        $lockDuration = $initialLockoutSeconds * pow(2, floor($loginAttempts / $maxAttempts));
                        $lockUntil = date("Y-m-d H:i:s", $currentTime + $lockDuration);
                        $conn->query("UPDATE admin SET login_attempts = $loginAttempts, lock_until = '$lockUntil' WHERE email = '$email'");
                        
                        $_SESSION['lockout_end_time'] = ($currentTime + $lockDuration) * 1000; // Convert to milliseconds for JavaScript
                        $_SESSION['login_error'] = "Too many failed attempts. Account locked for:";
                        $_SESSION['show_countdown'] = true;
                    } else {
                        $conn->query("UPDATE admin SET login_attempts = $loginAttempts WHERE email = '$email'");
                        $remainingAttempts = $maxAttempts - $loginAttempts;
                        $_SESSION['login_error'] = "Invalid password! Attempts left: $remainingAttempts";
                    }
                }
            }
        } else {
            $_SESSION['login_error'] = "No account found with that email!";
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
        .countdown {
            color: red;
            font-weight: bold;
            margin-top: 10px;
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
                <button class="login" type="submit">Login</button>
                <?php if (isset($_SESSION['login_error'])): ?>
                    <p style="color:red; margin-top: 15px;">
                        <?php echo $_SESSION['login_error']; ?>
                        <?php if (isset($_SESSION['show_countdown'])): ?>
                            <span id="countdown" class="countdown"></span>
                        <?php endif; ?>
                    </p>
                    <?php 
                    if (isset($_SESSION['show_countdown'])) {
                        unset($_SESSION['show_countdown']);
                    } else {
                        unset($_SESSION['login_error']);
                    }
                    ?>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (isset($_SESSION['lockout_end_time'])): ?>
    <script>
        // Set the date we're counting down to
        var countDownDate = <?php echo $_SESSION['lockout_end_time']; ?>;
        
        // Update the countdown every 1 second
        var x = setInterval(function() {
            // Get the current time in milliseconds
            var now = new Date().getTime();
            
            // Calculate the remaining time
            var distance = countDownDate - now;
            
            // Calculate seconds
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Display the countdown
            if (distance > 0) {
                document.getElementById("countdown").innerHTML = seconds + " seconds";
            } else {
                // When countdown is finished
                clearInterval(x);
                document.getElementById("countdown").innerHTML = "You can try again now";
                // Reload the page after 2 seconds to clear the message
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }
        }, 1000);
    </script>
    <?php 
        unset($_SESSION['lockout_end_time']);
        unset($_SESSION['login_error']);
    ?>
    <?php endif; ?>
</body>

</html>