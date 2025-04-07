<?php
require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['userId'])) {
    $userId = intval($_POST['userId']);
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validate inputs
    if (empty($fullName) || empty($email) || empty($phone)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit();
    }

    $sql = "UPDATE users SET fullName = ?, emailAddress = ?, phoneNumber = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $fullName, $email, $phone,  $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update user."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
