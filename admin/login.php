<?php
session_start();
include("database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($hashedPassword);
    $stmt->fetch();
    $stmt->close();

    // Verify password
    if ($hashedPassword && password_verify($password, $hashedPassword)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $email;
        header("Location: gymMembers.php");
        exit();
    } else {
        // Invalid credentials
        $_SESSION['login_error'] = "Invalid email or password!";
        header("Location: adminLogin.php");
        exit();
    }
}
