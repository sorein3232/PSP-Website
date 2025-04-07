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
    
    // Check for at least one special character
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
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

    // Validate password
    if (!validatePassword($new_password)) {
        echo '<script>
            alert("Password must be 8-16 characters long and contain at least one uppercase letter and one special character!");
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo isset($token) ? $token : ''; ?>">
            <input type="password" name="password" placeholder="Enter new password" required>
            <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>