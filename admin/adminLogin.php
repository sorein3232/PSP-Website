<?php
session_start();
require_once "database.php";  // Include your database connection

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: appointmentAdmin.php");
    exit();
}

// Process the form when it's submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email and password are correct
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        // Verify the password (Assuming you store passwords hashed)
        if (password_verify($password, $admin['password'])) {
            // Store the admin's ID in the session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];

            // Redirect to the dashboard or a different page
            header("Location: appointmentAdmin.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password!";
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/adminlogin.css">
    <title>Admin Login</title>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Welcome, Admin!</h1>
            <?php
            if (isset($_SESSION['login_error'])) {
                echo "<p style='color:red;'>" . $_SESSION['login_error'] . "</p>";
                unset($_SESSION['login_error']);
            }
            ?>
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="login" type="submit">Login</button>
            </form>
        </div>
    </div>
</body>

</html>