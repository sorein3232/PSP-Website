<?php
date_default_timezone_set('Asia/Manila'); 

session_start();
require_once "database.php";  // Include your database connection
require '../vendor/autoload.php';  // Using the same path as your working file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: appointmentAdmin.php");
    exit();
}

// Process the form when it's submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_error'] = "Invalid email format!";
    } else {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT id, email FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($adminId, $adminEmail);
            $stmt->fetch();
            
            // Generate a unique token
            $token = bin2hex(random_bytes(50));
            $expires = date("Y-m-d H:i:s", strtotime("+3 hours")); // Token expires in 3 hours
            
            // Store the token in the database
            $updateStmt = $conn->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $token, $expires, $adminId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Create reset link
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/resetPassword.php?token=" . urlencode($token);
            
            // Send email with PHPMailer - using the same configuration as your working file
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'subtest164@gmail.com';
                $mail->Password = 'jcui ljvb tjul mfey';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('subtest164@gmail.com', 'PSP-Ubelt');
                $mail->addAddress('smpurbano17@gmail.com'); // Safety email for recovery
                $mail->isHTML(true);
                $mail->Subject = "Admin Password Reset Request";
                $mail->Body = "An admin account password reset was requested for email: $adminEmail.<br><br>
                    <a href='$resetLink'>Reset Password</a><br><br>
                    This link will expire in 3 hours.<br><br>
                    If you did not request a password reset, please ignore this email.";

                $mail->send();
                $_SESSION['reset_success'] = "Password reset instructions have been sent to the recovery email. Please check for the reset link.";
            } catch (Exception $e) {
                $_SESSION['reset_error'] = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            // To prevent email enumeration, show the same message even if email doesn't exist
            $_SESSION['reset_success'] = "If your email is in our system, password reset instructions have been sent to the recovery email.";
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
    <title>Forgot Password</title>
    <style>
        body {
            background-image: url('../frontend/resources/gym-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 20px 30px 20px 20px; 
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 350px;
        }

        h2 {
            margin-bottom: 15px;
            color: #ff8800;
        }

        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box; 
        }

        button {
            background: #ff8800;
            color: white;
            cursor: pointer;
            border: 1px solid #ff8800; 
        }

        button:hover {
            background: #e67700; 
        }

        .error-message {
            color: red;
            margin-top: 10px;
        }

        .success-message {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form action="forgotPassword.php" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <p style="margin-top: 5px;">
            <a href="adminLogin.php" style="color: #ff8800; text-decoration: none; font-size: 0.9rem">Return to Login</a>
        </p>
        
        <?php if (isset($_SESSION['reset_error'])): ?>
            <p class="error-message">
                <?php 
                echo $_SESSION['reset_error']; 
                unset($_SESSION['reset_error']);
                ?>
            </p>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['reset_success'])): ?>
            <p class="success-message">
                <?php 
                echo $_SESSION['reset_success']; 
                unset($_SESSION['reset_success']);
                ?>
            </p>
        <?php endif; ?>
    </div>
</body>

</html>