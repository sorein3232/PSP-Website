<?php
date_default_timezone_set('Asia/Manila'); 

session_start();
require '../dbs_connection/database.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format!");
    }

    
    $stmt = $conn->prepare("SELECT id FROM users WHERE emailAddress = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(50));
        $expires = date("Y-m-d H:i:s", strtotime("+3 hours"));


        
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE emailAddress = ?");
        $stmt->bind_param("sss", $token, $expires, $email);
        $stmt->execute();

        
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
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "Click the link below to reset your password:<br><br>
                <a href='http://localhost/PSP-Website/frontend/reset_password.php?token=" . urlencode($token) . "'>Reset Password</a>";

            $mail->send();
            echo '<script>alert("Password reset link has been sent!")</script>';
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo '<script>alert("No account found with this email.")</script>';
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


    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form action="forgot_password.php" method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <p style="margin-top: 5px;">
            <a href="login.php" style="color: #ff8800; text-decoration: none; font-size: 0.9rem">Return to Login</a>
        </p>
    </div>
</body>
</html>

