<?php
date_default_timezone_set('Asia/Manila'); 

require '../dbs_connection/database.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

function validatePassword($password) {
    // Check password length between 8 and 16 characters
    if (strlen($password) < 8 || strlen($password) > 16) {
        return false;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for at least one number
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    
    // Check for at least one special character
    if (!preg_match('/[\W_]/', $password)) {
        return false;
    }
    
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['token']) || empty($_POST['token'])) {
        die("Error: No token provided!");
    }

    $token = trim($_POST['token']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        echo '<script>
            alert("Passwords do not match!");
            window.history.back();
        </script>';
        exit();
    }

    // Detailed password validation
    $password_errors = [];
    if (strlen($new_password) < 8 || strlen($new_password) > 16) {
        $password_errors[] = "Password must be 8-16 characters long";
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        $password_errors[] = "Include at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $new_password)) {
        $password_errors[] = "Include at least one lowercase letter";
    }
    if (!preg_match('/\d/', $new_password)) {
        $password_errors[] = "Include at least one number";
    }
    if (!preg_match('/[\W_]/', $new_password)) {
        $password_errors[] = "Include at least one special character";
    }
    
    if (!empty($password_errors)) {
        echo '<script>
            alert("Password requirements: ' . implode(", ", $password_errors) . '");
            window.history.back();
        </script>';
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $userId);

        if ($stmt->execute()) {
            echo '<script>
                alert("Password updated successfully!");
                window.location.href = "login.php";
            </script>';
        } else {
            echo "Error updating password.";
        }
    } else {
        echo '<script>
            alert("Invalid or expired token!");
            window.location.href = "login.php";
        </script>';
    }

    $stmt->close();
    $conn->close();
} elseif (isset($_GET['token'])) { 
    $token = htmlspecialchars($_GET['token']);
} else {
    die("Invalid request.");
}
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
            width: 300px;
        }

        h2 {
            margin-bottom: 15px;
            color: #ff8800;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #ff8800;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #ff8800;
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
            // Password strength checker
            const passwordInput = document.getElementById("password");
            const strengthBar = document.getElementById("strength-bar");
            const strengthText = document.getElementById("strength-text");
            const requirementsPopup = document.getElementById("passwordRequirements");
            
            // Password requirement checkers
            const lengthReq = document.getElementById("length-req");
            const uppercaseReq = document.getElementById("uppercase-req");
            const lowercaseReq = document.getElementById("lowercase-req");
            const numberReq = document.getElementById("number-req");
            const specialReq = document.getElementById("special-req");

            // Form submission handler
            document.getElementById("resetForm").addEventListener("submit", function(event) {
                const password = passwordInput.value;
                const confirmPassword = document.getElementById("confirm_password").value;
                
                // Check if passwords match
                if (password !== confirmPassword) {
                    alert("Passwords do not match!");
                    event.preventDefault();
                    return;
                }
                
                // Check all password requirements
                const hasLength = password.length >= 8 && password.length <= 16;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasSpecial = /[\W_]/.test(password);
                
                if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
                    let errorMessages = [];
                    
                    if (!hasLength) errorMessages.push("Password must be 8-16 characters long");
                    if (!hasUppercase) errorMessages.push("Include at least one uppercase letter");
                    if (!hasLowercase) errorMessages.push("Include at least one lowercase letter");
                    if (!hasNumber) errorMessages.push("Include at least one number");
                    if (!hasSpecial) errorMessages.push("Include at least one special character");
                    
                    alert("Password requirements: " + errorMessages.join(", "));
                    event.preventDefault();
                }
            });

            // Password requirements popup handlers
            passwordInput.addEventListener("focus", function() {
                requirementsPopup.classList.add("show");
            });
            
            passwordInput.addEventListener("blur", function() {
                // Small delay to check if focus moved to an element inside the popup
                setTimeout(() => {
                    if (!requirementsPopup.contains(document.activeElement)) {
                        requirementsPopup.classList.remove("show");
                    }
                }, 100);
            });

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
    <div class="container">
        <h2>Reset Your Password</h2>
        <form id="resetForm" action="reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo isset($token) ? $token : ''; ?>">
            
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Enter new password" required>
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
            
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>