<?php
session_start();
require_once "database.php";  // Include your database connection

// Set recovery email address (you might want to store this in a config file in production)
$recoveryEmail = "luiszara321@gmail.com";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Step 1: Initial recovery request
    if (isset($_POST['email']) && !isset($_POST['verification_code'])) {
        $email = trim($_POST['email']);
        
        // Check if the email exists in admin table
        $stmt = $conn->prepare("SELECT id, email FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            // Generate a random 6-digit verification code
            $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
            
            // Set expiration time (30 minutes from now)
            $expiryTime = date('Y-m-d H:i:s', time() + 1800);
            
            // Store the code and expiry time in the database
            $updateStmt = $conn->prepare("UPDATE admin SET recovery_code = ?, recovery_code_expires = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $verificationCode, $expiryTime, $email);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Store the admin email in session for the next step
            $_SESSION['recovery_email'] = $email;
            
            // Send email to the recovery address
            $subject = "Admin Account Recovery Code";
            $message = "A recovery was requested for admin account: $email\n\n";
            $message .= "Your verification code is: $verificationCode\n\n";
            $message .= "This code will expire in 30 minutes.\n\n";
            $message .= "If you did not request this recovery, please contact the system administrator immediately.";
            $headers = "From: noreply@yourwebsite.com";
            
            // Send the email to the fixed recovery email address
            mail($recoveryEmail, $subject, $message, $headers);
            
            // Redirect to verification code page
            $_SESSION['recovery_message'] = "A verification code has been sent to the recovery email. Please enter it below.";
            header("Location: adminRecovery.php?step=verify");
            exit();
        } else {
            $_SESSION['recovery_error'] = "No admin account found with that email.";
        }
        
        $stmt->close();
    }
    
    // Step 2: Verify the code
    elseif (isset($_POST['verification_code']) && isset($_SESSION['recovery_email'])) {
        $code = trim($_POST['verification_code']);
        $email = $_SESSION['recovery_email'];
        
        // Check if the code is valid and not expired
        $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ? AND recovery_code = ? AND recovery_code_expires > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            // Code is valid, allow password reset
            $stmt->bind_result($adminId);
            $stmt->fetch();
            
            // Generate a temporary token for the password reset form
            $resetToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            
            // Store the token in the database
            $updateStmt = $conn->prepare("UPDATE admin SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $resetToken, $tokenExpiry, $adminId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Redirect to password reset form
            $_SESSION['reset_token'] = $resetToken;
            header("Location: adminRecovery.php?step=reset");
            exit();
        } else {
            $_SESSION['recovery_error'] = "Invalid or expired verification code.";
        }
        
        $stmt->close();
    }
    
    // Step 3: Reset the password
    elseif (isset($_POST['new_password']) && isset($_POST['confirm_password']) && isset($_SESSION['reset_token'])) {
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        $resetToken = $_SESSION['reset_token'];
        
        // Validate passwords
        if (strlen($newPassword) < 8) {
            $_SESSION['recovery_error'] = "Password must be at least 8 characters long.";
            header("Location: adminRecovery.php?step=reset");
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['recovery_error'] = "Passwords do not match.";
            header("Location: adminRecovery.php?step=reset");
            exit();
        }
        
        // Check if the token is valid and not expired
        $stmt = $conn->prepare("SELECT id FROM admin WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("s", $resetToken);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($adminId);
            $stmt->fetch();
            
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update the password and clear recovery fields
            $updateStmt = $conn->prepare("UPDATE admin SET 
                password = ?, 
                recovery_code = NULL, 
                recovery_code_expires = NULL,
                reset_token = NULL,
                reset_token_expires = NULL,
                login_attempts = 0,
                lock_until = NULL,
                lockout_count = 0
                WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $adminId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Log the password reset
            $logStmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, activity_type, ip_address, timestamp) VALUES (?, 'password_reset', ?, NOW())");
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $logStmt->bind_param("is", $adminId, $ipAddress);
            $logStmt->execute();
            $logStmt->close();
            
            // Clear session data
            unset($_SESSION['recovery_email']);
            unset($_SESSION['reset_token']);
            
            // Redirect to login with success message
            $_SESSION['login_success'] = "Password has been reset successfully. You can now log in with your new password.";
            header("Location: adminLogin.php");
            exit();
        } else {
            $_SESSION['recovery_error'] = "Invalid or expired reset token. Please restart the recovery process.";
            header("Location: adminRecovery.php");
            exit();
        }
        
        $stmt->close();
    }
}

// Determine which form to display based on the step
$step = isset($_GET['step']) ? $_GET['step'] : 'request';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminlogin.css">
    <title>Admin Account Recovery</title>
    <style>
        .recovery-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .recovery-box {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .alert-container {
            text-align: center;
            margin-top: 20px;
            color: red;
        }
        .success-message {
            color: green;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="recovery-box">
            <h1>Admin Account Recovery</h1>
            
            <?php if ($step === 'request'): ?>
                <!-- Step 1: Request recovery form -->
                <p>Enter your admin email address to begin the recovery process.</p>
                <form action="adminRecovery.php" method="POST">
                    <input type="email" name="email" placeholder="Admin Email Address" required>
                    <button type="submit">Request Recovery</button>
                </form>
                
            <?php elseif ($step === 'verify'): ?>
                <!-- Step 2: Verification code form -->
                <p>A verification code has been sent to the recovery email address.</p>
                <form action="adminRecovery.php?step=verify" method="POST">
                    <input type="text" name="verification_code" placeholder="Enter Verification Code" required>
                    <button type="submit">Verify Code</button>
                </form>
                
            <?php elseif ($step === 'reset'): ?>
                <!-- Step 3: Password reset form -->
                <p>Create a new password for your admin account.</p>
                <form action="adminRecovery.php?step=reset" method="POST">
                    <input type="password" name="new_password" placeholder="New Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <button type="submit">Reset Password</button>
                </form>
                
            <?php endif; ?>
            
            <a href="adminLogin.php" class="back-link">Back to Login</a>
        </div>
        
        <div class="alert-container">
            <?php
            if (isset($_SESSION['recovery_error'])) {
                echo "<p>" . $_SESSION['recovery_error'] . "</p>";
                unset($_SESSION['recovery_error']);
            }
            if (isset($_SESSION['recovery_message'])) {
                echo "<p class='success-message'>" . $_SESSION['recovery_message'] . "</p>";
                unset($_SESSION['recovery_message']);
            }
            ?>
        </div>
    </div>
</body>
</html>