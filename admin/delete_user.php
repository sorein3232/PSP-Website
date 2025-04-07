<?php
session_start();
require_once 'database.php';

header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit();
}

$user_id = $_POST['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User ID missing"]);
    exit();
}

// Optional: Prevent user from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(["success" => false, "error" => "You cannot delete your own account"]);
    exit();
}

// Delete user from database
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database error"]);
}

$stmt->close();
$conn->close();
