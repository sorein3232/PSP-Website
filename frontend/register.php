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

    // Age validation - check if user is at least 16 years old
    $birthDate = new DateTime("$year-$month-$day");
    $currentDate = new DateTime();
    $age = $currentDate->diff($birthDate)->y;
    
    if ($age < 16) {
        echo json_encode(["status" => "error", "message" => "❌ You must be at least 16 years old to register."]);
        exit();
    }

    // Detailed password validation
    $password_errors = [];
    if (strlen($password) < 8 || strlen($password) > 16) {
        $password_errors[] = "Password must be 8-16 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $password_errors[] = "Include at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $password_errors[] = "Include at least one lowercase letter";
    }
    if (!preg_match('/\d/', $password)) {
        $password_errors[] = "Include at least one number";
    }
    if (!preg_match('/[\W_]/', $password)) {
        $password_errors[] = "Include at least one special character";
    }
    
    if (!empty($password_errors)) {
        echo json_encode([
            "status" => "error", 
            "message" => "❌ Password requirements: " . implode(", ", $password_errors)
        ]);
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
            $mail->Body = "Click <a href='http://localhost/PSP-Website/frontend/verify.php?token=$verificationToken'>here</a> to verify your account.";

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

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }

        /* Password strength indicator styles */
        .password-container {
            position: relative;
            width: 100%;
            margin-bottom: 15px;
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            width: 100%;
            background: #ddd;
            border-radius: 3px;
        }

        .password-strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s, background-color 0.3s;
        }

        .password-feedback {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }

        .very-weak { background-color: #ff4d4d; width: 20%; }
        .weak { background-color: #ffa64d; width: 40%; }
        .medium { background-color: #ffff4d; width: 60%; }
        .strong { background-color: #4dff4d; width: 80%; }
        .very-strong { background-color: #26b226; width: 100%; }

        /* Password requirements popup */
        .password-requirements {
            display: none;
            position: absolute;
            width: 250px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            top: 70px;
            left: 0;
            transition: opacity 0.3s ease;
            opacity: 0;
        }

        .password-requirements.show {
            display: block;
            opacity: 1;
        }

        .password-requirements h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #333;
        }

        .requirements-list {
            padding-left: 15px;
            margin: 0;
        }

        .requirements-list li {
            margin-bottom: 5px;
            font-size: 12px;
            list-style-type: none;
            position: relative;
            padding-left: 18px;
        }

        .requirements-list li:before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ff4d4d;
        }

        .requirement-met:before {
            background-color: #26b226 !important;
        }

        /* Arrow pointing to input */
        .password-requirements:before {
            content: "";
            position: absolute;
            top: -10px;
            left: 20px;
            border-width: 0 10px 10px 10px;
            border-style: solid;
            border-color: transparent transparent #ddd transparent;
        }

        .password-requirements:after {
            content: "";
            position: absolute;
            top: -9px;
            left: 20px;
            border-width: 0 10px 10px 10px;
            border-style: solid;
            border-color: transparent transparent white transparent;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Form submission handler
            document.getElementById("registerForm").addEventListener("submit", function(event) {
                event.preventDefault();

                let formData = new FormData(this);
                let agreeChecked = document.getElementById("agree").checked;

                if (!agreeChecked) {
                    alert("❌ You must agree to the terms and conditions before proceeding.");
                    return;
                }

                // Age validation - client side
                const day = parseInt(document.getElementById("day").value);
                const month = parseInt(document.getElementById("month").value) - 1; // JavaScript months are 0-indexed
                const year = parseInt(document.getElementById("year").value);
                
                const birthDate = new Date(year, month, day);
                const today = new Date();
                const ageDiff = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                let age = ageDiff;
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                if (age < 16) {
                    alert("❌ You must be at least 16 years old to register.");
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

            // Terms and conditions modal handlers
            document.getElementById("termsLink").addEventListener("click", function(event) {
                event.preventDefault();
                document.getElementById("termsModal").style.display = "block";
            });

            document.getElementById("closeModal").addEventListener("click", function() {
                document.getElementById("termsModal").style.display = "none";
            });

            // Close modals when clicking outside
            window.addEventListener("click", function(event) {
                const termsModal = document.getElementById("termsModal");
                
                if (event.target === termsModal) {
                    termsModal.style.display = "none";
                }
            });

            // Password requirements popup handlers
            const passwordInput = document.getElementById("password");
            const requirementsPopup = document.getElementById("passwordRequirements");
            
            passwordInput.addEventListener("focus", function() {
                requirementsPopup.classList.add("show");
            });
            
            passwordInput.addEventListener("blur", function(e) {
                // Small delay to check if focus moved to an element inside the popup
                setTimeout(() => {
                    if (!requirementsPopup.contains(document.activeElement)) {
                        requirementsPopup.classList.remove("show");
                    }
                }, 100);
            });

            // Password strength checker
            const strengthBar = document.getElementById("strength-bar");
            const strengthText = document.getElementById("strength-text");
            
            // Password requirement checkers
            const lengthReq = document.getElementById("length-req");
            const uppercaseReq = document.getElementById("uppercase-req");
            const lowercaseReq = document.getElementById("lowercase-req");
            const numberReq = document.getElementById("number-req");
            const specialReq = document.getElementById("special-req");

            passwordInput.addEventListener("input", function() {
                const password = passwordInput.value;
                let strength = 0;
                let feedback = "Very Weak";
                
                // Check requirements
                const hasLength = password.length >= 8 && password.length <= 16;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[\W_]/.test(password);
                
                // Update requirement indicators
                updateRequirement(lengthReq, hasLength);
                updateRequirement(uppercaseReq, hasUppercase);
                updateRequirement(lowercaseReq, hasLowercase);
                updateRequirement(numberReq, hasNumber);
                updateRequirement(specialReq, hasSpecial);
                
                // Calculate strength
                if (hasLength) strength++;
                if (hasUppercase) strength++;
                if (hasLowercase) strength++;
                if (hasNumber) strength++;
                if (hasSpecial) strength++;
                
                // Update visual indicators
                strengthBar.className = "password-strength-bar";
                
                switch (strength) {
                    case 1:
                        strengthBar.classList.add("very-weak");
                        feedback = "Very Weak";
                        break;
                    case 2:
                        strengthBar.classList.add("weak");
                        feedback = "Weak";
                        break;
                    case 3:
                        strengthBar.classList.add("medium");
                        feedback = "Medium";
                        break;
                    case 4:
                        strengthBar.classList.add("strong");
                        feedback = "Strong";
                        break;
                    case 5:
                        strengthBar.classList.add("very-strong");
                        feedback = "Very Strong";
                        break;
                    default:
                        strengthBar.classList.add("very-weak");
                        feedback = "Very Weak";
                }
                
                strengthText.textContent = feedback;
            });
            
            function updateRequirement(element, isMet) {
                if (isMet) {
                    element.classList.add("requirement-met");
                } else {
                    element.classList.remove("requirement-met");
                }
            }
        });
    </script>
</head>

<body>
    <div class="signup-container">
        <h1>Sign Up</h1>
        <form id="registerForm">
            <input type="text" name="fullName" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="phoneNumber" placeholder="Phone Number (e.g., 9171234567)" required>

            <h4 class="birthdayLabel">Birthday (Must be at least 16 years old)</h4>
            <div style="display: flex;">
                <input type="number" id="day" name="day" placeholder="Day" min="1" max="31" required>
                <input type="number" id="month" name="month" placeholder="Month" min="1" max="12" required>
                <input type="number" id="year" name="year" placeholder="Year" min="1900" max="2025" required>
            </div>

            <input type="email" name="emailAddress" placeholder="Enter your email" required>
            
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Create Password" required>
                <div class="password-strength">
                    <div id="strength-bar" class="password-strength-bar"></div>
                </div>
                <div class="password-feedback">
                    <span>Password Strength: </span>
                    <span id="strength-text">Very Weak</span>
                </div>
                
                <!-- Password Requirements Popup -->
                <div id="passwordRequirements" class="password-requirements">
                    <h4>Password Requirements</h4>
                    <ul class="requirements-list">
                        <li id="length-req">8-16 characters</li>
                        <li id="uppercase-req">One uppercase letter</li>
                        <li id="lowercase-req">One lowercase letter</li>
                        <li id="number-req">One number</li>
                        <li id="special-req">One special character</li>
                    </ul>
                </div>
            </div>
            
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

    <!-- Terms and Conditions Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2>Terms and Conditions</h2>
            <p>Welcome to PSP-Ubelt! By creating an account on our website, you agree to comply with the following Terms and Conditions. Please read them carefully.</p>

            <ul>
                <li><strong>1. Eligibility:</strong> You must be at least 16 years old to create an account. By registering, you confirm that the information provided is accurate and complete.</li>

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