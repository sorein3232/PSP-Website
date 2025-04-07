<?php
include("../dbs_connection/database.php");

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update_stmt->bind_param("s", $token);
        $verification_status = $update_stmt->execute() ? "success" : "error";
    } else {
        $verification_status = "invalid";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        body {
            background-image: url('../frontend/resources/gym-background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
        }

        .verification-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 350px;
            padding: 30px;
            text-align: center;
        }

        .verification-title {
            color: #ff8800;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .verification-message {
            margin-bottom: 20px;
            color: #333;
        }

        .verification-link {
            display: inline-block;
            background-color: #ff8800;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }

        .verification-link:hover {
            background-color: #ff9a2a;
        }

        .success-message {
            color: green;
        }

        .error-message {
            color: red;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <h2 class="verification-title">Email Verification</h2>
        <div class="verification-message">
            <?php if (isset($verification_status)): ?>
                <?php if ($verification_status === "success"): ?>
                    <p class="success-message">✅ Email verified successfully!</p>
                    <a href="login.php" class="verification-link">Go to Login</a>
                <?php elseif ($verification_status === "error"): ?>
                    <p class="error-message">❌ Verification failed. Please try again.</p>
                    <a href="login.php" class="verification-link">Return to Login</a>
                <?php else: ?>
                    <p class="error-message">❌ Invalid or expired token.</p>
                    <a href="login.php" class="verification-link">Return to Login</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>