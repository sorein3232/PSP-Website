<?php
session_start();
require_once "database.php";  // Include your database connection

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: appointmentAdmin.php");
    exit();
}

// Check if token is provided in the URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['login_error'] = "Invalid or missing reset token. Please try the reset process again.";
    header("Location: adminLogin.php");
    exit();
}

$token = $_GET['token'];
$currentTime = date("Y-m-d H:i:s");

// Verify token exists and is not expired
$stmt = $conn->prepare("SELECT id FROM admin WHERE reset_token = ? AND reset_expires > ?");
$stmt->bind_param("ss", $token, $currentTime);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['login_error'] = "Invalid or expired reset token. Please request a new password reset.";
    header("Location: adminLogin.php");
    exit();
}

$stmt->bind_result($adminId);
$stmt->fetch();
$stmt->close();

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate password
    if (empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the password and clear reset token
        $updateStmt = $conn->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_expires = NULL, login_attempts = 0, lock_until = NULL WHERE id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $adminId);
        
        if ($updateStmt->execute()) {
            $_SESSION['login_success'] = "Password has been reset successfully. You can now login with your new password.";
            header("Location: adminLogin.php");
            exit();
        } else {
            $error = "Failed to reset password. Please try again.";
        }
        
        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminlogin.css">
    <title>Reset Password</title>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Reset Password</h1>
            <p>Enter your new password below.</p>
            
            <form action="resetPassword.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <input type="password" name="password" placeholder="New Password" required minlength="8">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
                <button class="login" type="submit">Reset Password</button>
                
                <?php if (isset($error)): ?>
                    <p style="color:red; margin-top: 15px;">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>

</html>