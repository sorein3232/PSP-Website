<?php
session_start();
include("../dbs_connection/database.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

// Max attempts and lockout duration (in seconds)
$maxAttempts = 5;
$initialLockoutSeconds = 60; // 1 minute lockout initially

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emailAddress = trim($_POST['emailAddress']);
    $password = trim($_POST['password']);

    if (empty($emailAddress) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "❌ Please fill in all fields."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, fullName, emailAddress, phoneNumber, birthday, password, membership_status, email_verified, login_attempts, lock_until FROM users WHERE emailAddress = ?");
    $stmt->bind_param("s", $emailAddress);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userId, $fullName, $email, $phoneNumber, $birthday, $hashedPassword, $membershipStatus, $emailVerified, $loginAttempts, $lockUntil);
        $stmt->fetch();

        $currentTime = time();

        // Check if the account is locked
        if ($lockUntil && $currentTime < strtotime($lockUntil)) {
            $remainingLockout = strtotime($lockUntil) - $currentTime;
            echo json_encode(["status" => "error", "message" => "⏳ Account is locked. Try again later.", "lockout_seconds" => $remainingLockout]);
            exit();
        }

        // Reset attempts if lockout time has passed
        if ($lockUntil && $currentTime >= strtotime($lockUntil)) {
            $loginAttempts = 0;
            $lockUntil = null;
            $conn->query("UPDATE users SET login_attempts = 0, lock_until = NULL WHERE emailAddress = '$emailAddress'");
        }

        if (!$emailVerified) {
            echo json_encode(["status" => "error", "message" => "❌ Please verify your email before logging in."]);
            exit();
        }

        if (!password_get_info($hashedPassword)['algo']) {
            echo json_encode(["status" => "error", "message" => "⚠️ Error: Password in database is NOT hashed! Update your database."]);
            exit();
        }

        if (password_verify($password, $hashedPassword)) {
            // Reset attempts on successful login
            $conn->query("UPDATE users SET login_attempts = 0, lock_until = NULL WHERE emailAddress = '$emailAddress'");

            $_SESSION['user_id'] = $userId;
            $_SESSION['fullName'] = $fullName;
            $_SESSION['emailAddress'] = $email;
            $_SESSION['phoneNumber'] = $phoneNumber;
            $_SESSION['birthday'] = $birthday;
            $_SESSION['membership_status'] = $membershipStatus;

            echo json_encode(["status" => "success", "message" => "✅ Login successful. Redirecting..."]);
        } else {
            $loginAttempts++;

            if ($loginAttempts >= $maxAttempts) {
                $lockDuration = $initialLockoutSeconds * pow(2, floor($loginAttempts / $maxAttempts));
                $lockUntil = date("Y-m-d H:i:s", $currentTime + $lockDuration);
                $conn->query("UPDATE users SET lock_until = '$lockUntil' WHERE emailAddress = '$emailAddress'");
                echo json_encode(["status" => "error", "message" => "⏳ Too many failed attempts. Account locked.", "lockout_seconds" => $lockDuration]);
            } else {
                $conn->query("UPDATE users SET login_attempts = $loginAttempts WHERE emailAddress = '$emailAddress'");
                $remainingAttempts = $maxAttempts - $loginAttempts;
                echo json_encode(["status" => "error", "message" => "❌ Invalid email or password. Attempts left: $remainingAttempts", "lockout_seconds" => 0]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Invalid email or password.", "lockout_seconds" => 0]);
    }

    $stmt->close();
    $conn->close();
}
?>
