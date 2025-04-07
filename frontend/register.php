<?php
session_start();
include("../dbs_connection/database.php");

header("Content-Type: application/json");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Load PHPMailer

function validatePhilippinePhoneNumber($phoneNumber) {
    // Remove all non-digit characters
    $cleanedNumber = preg_replace('/\D/', '', $phoneNumber);
    
    // Check total length after removing non-digit characters
    $length = strlen($cleanedNumber);
    
    // Valid Philippine mobile number scenarios
    if ($length == 10) {
        // Standard 10-digit mobile number starting with 9
        return substr($cleanedNumber, 0, 1) === '9';
    } elseif ($length == 11) {
        // Number with leading 0
        return substr($cleanedNumber, 0, 2) === '09';
    } elseif ($length == 12) {
        // Number with country code
        return substr($cleanedNumber, 0, 3) === '639';
    }
    
    return false;
}

function normalizePhoneNumber($phoneNumber) {
    // Remove all non-digit characters
    $cleanedNumber = preg_replace('/\D/', '', $phoneNumber);
    
    // Normalize to international format
    $length = strlen($cleanedNumber);
    
    if ($length == 10) {
        // 9xxxxxxxxx -> 639xxxxxxxxx
        return '63' . $cleanedNumber;
    } elseif ($length == 11 && $cleanedNumber[0] === '0') {
        // 09xxxxxxxxx -> 639xxxxxxxxx
        return '63' . substr($cleanedNumber, 1);
    } elseif ($length == 12 && substr($cleanedNumber, 0, 3) === '639') {
        // Already in correct format
        return $cleanedNumber;
    }
    
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = [];

    // Collect and trim form data
    $fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';
    $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
    $day = isset($_POST['day']) ? $_POST['day'] : '';
    $month = isset($_POST['month']) ? $_POST['month'] : '';
    $year = isset($_POST['year']) ? $_POST['year'] : '';
    $agree = isset($_POST['agree']) ? 1 : 0;
    $membership_status = "Inactive";

    // Validate required fields
    if (empty($fullName) || empty($username) || empty($phoneNumber) || empty($emailAddress) || empty($password) || empty($confirmPassword) || empty($day) || empty($month) || empty($year)) {
        echo json_encode(["status" => "error", "message" => "❌ All fields are required."]);
        exit();
    }

    if (!$agree) {
        echo json_encode(["status" => "error", "message" => "❌ You must agree to the terms and conditions."]);
        exit();
    }

    if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "❌ Invalid email format."]);
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo json_encode(["status" => "error", "message" => "❌ Username can only contain letters, numbers, and underscores."]);
        exit();
    }

    // New phone number validation
    if (!validatePhilippinePhoneNumber($phoneNumber)) {
        echo json_encode(["status" => "error", "message" => "❌ Invalid Philippine phone number format. Use 9xxxxxxxxx, 09xxxxxxxxx, or +639xxxxxxxxx"]);
        exit();
    }

    // Normalize the phone number
    $phoneNumber = normalizePhoneNumber($phoneNumber);
    if ($phoneNumber === false) {
        echo json_encode(["status" => "error", "message" => "❌ Could not normalize phone number."]);
        exit();
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,16}$/', $password)) {
        echo json_encode(["status" => "error", "message" => "❌ Password must be 8-16 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character."]);
        exit();
    }

    if ($password !== $confirmPassword) {
        echo json_encode(["status" => "error", "message" => "❌ Passwords do not match."]);
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    if (!checkdate($month, $day, $year)) {
        echo json_encode(["status" => "error", "message" => "❌ Invalid birthday."]);
        exit();
    }
    $birthday = "$year-$month-$day";

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR emailAddress = ?");
    $check_stmt->bind_param("ss", $username, $emailAddress);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "❌ Username or Email is already taken."]);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Generate a unique verification token
    $verificationToken = bin2hex(random_bytes(32));

    $stmt = $conn->prepare("INSERT INTO users (fullName, username, phoneNumber, emailAddress, password, birthday, membership_status, email_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
    $stmt->bind_param("ssssssss", $fullName, $username, $phoneNumber, $emailAddress, $hashedPassword, $birthday, $membership_status, $verificationToken);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'subtest164@gmail.com';
            $mail->Password = 'jcui ljvb tjul mfey';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('subtest164@gmail.com', 'PSP-Ubelt');
            $mail->addAddress($emailAddress);
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email';
            $mail->Body = "Click <a href='http://localhost/psp/frontend/verify.php?token=$verificationToken'>here</a> to verify your account.";

            $mail->send();
            echo json_encode(["status" => "success", "message" => "✅ Registration successful! Check your email for verification."]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "❌ Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
header("Content-Type: text/html");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/register.css">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("registerForm").addEventListener("submit", function(event) {
                event.preventDefault();

                let formData = new FormData(this);
                let agreeChecked = document.getElementById("agree").checked;

                if (!agreeChecked) {
                    alert("❌ You must agree to the terms and conditions before proceeding.");
                    return;
                }

                fetch("register.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === "success") {
                            window.location.href = "login.php";
                        }
                    })
                    .catch(error => console.error("Error:", error));
            });

            document.getElementById("termsLink").addEventListener("click", function(event) {
                event.preventDefault();
                document.getElementById("termsModal").style.display = "block";
            });

            document.getElementById("closeModal").addEventListener("click", function() {
                document.getElementById("termsModal").style.display = "none";
            });
        });
    </script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="signup-container">
        <h1>Sign Up</h1>
        <form id="registerForm">
            <input type="text" name="fullName" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="phoneNumber" placeholder="Phone Number (e.g., 9171234567)" required>

            <h4 class="birthdayLabel">Birthday</h4>
            <div style="display: flex;">
                <input type="number" name="day" placeholder="Day" min="1" max="31" required>
                <input type="number" name="month" placeholder="Month" min="1" max="12" required>
                <input type="number" name="year" placeholder="Year" min="1900" max="2025" required>
            </div>

            <input type="email" name="emailAddress" placeholder="Enter your email" required>
            <input type="password" name="password" placeholder="Create Password" required>
            <input type="password" name="confirmPassword" placeholder="Confirm Password" required>

            <div class="terms">
                <input type="checkbox" id="agree" name="agree">
                <label for="agree">I agree to the <a href="#" id="termsLink">terms and conditions</a></label>
            </div>

            <button type="submit">Create Account</button>
        </form>
        <p class="login-link">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>

    <div id="termsModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2>Terms and Conditions</h2>
            <p>Welcome to PSP-Ubelt! By creating an account on our website, you agree to comply with the following Terms and Conditions. Please read them carefully.</p>

            <ul>
                <li><strong>1. Eligibility:</strong> You must be at least 18/16 years old to create an account. By registering, you confirm that the information provided is accurate and complete.</li>

                <li><strong>2. Account Security:</strong> You are responsible for maintaining the confidentiality of your account credentials. Notify us immediately of any unauthorized access or suspicious activity.</li>

                <li><strong>3. Use of Services:</strong> Your account grants access to gym-related services, including but not limited to booking classes, purchasing memberships, and accessing fitness resources. You agree to use the services for personal, non-commercial purposes only.</li>

                <li><strong>4. Membership:</strong> Account creation is free, but some services require paid memberships or fees.</li>

                <li><strong>5. Code of Conduct:</strong> You agree not to:
                    <ul>
                        <li>Engage in unlawful activities through the website.</li>
                        <li>Misuse or attempt to hack the platform.</li>
                        <li>Share your account with others.</li>
                    </ul>
                </li>

                <li><strong>6. Freeze and Termination:</strong> You may freeze your account anytime through your profile settings. We reserve the right to suspend or terminate accounts for violations of these terms.</li>

                <li><strong>7. Privacy Policy:</strong> Your personal data will be handled according to our Privacy Policy. By creating an account, you consent to the collection and use of your information as described.</li>

                <li><strong>8. Liability Disclaimer:</strong> PSP-UBelt is not liable for any injuries or damages incurred during the use of our facilities or services. Use at your own risk.</li>

                <li><strong>9. Changes to Terms:</strong> We reserve the right to modify these terms. Updates will be posted on the website, and your continued use constitutes acceptance of the new terms.</li>
            </ul>
        </div>
    </div>
</body>

</html>