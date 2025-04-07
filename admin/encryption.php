<?php
$admin_email = "admin1@example.com"; // Set your admin email
$admin_password = "admin234"; // Set your admin password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT); // Encrypt password

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "psp_ubelt";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert admin user (without role)
$sql = "INSERT INTO admin (email, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admin_email, $hashed_password);

if ($stmt->execute()) {
    echo "Admin user created successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
