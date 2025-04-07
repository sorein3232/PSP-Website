<?php
session_start();
include("../dbs_connection/database.php");

header("Content-Type: application/json"); // Ensure JSON response

$user_id = $_SESSION['user_id'];
$field = $_POST['field'] ?? '';

// Password validation function
function isValidPassword($password) {
    // Check length between 8-16 characters
    if (strlen($password) < 8 || strlen($password) > 16) {
        return ["valid" => false, "error" => "Password must be between 8-16 characters"];
    }

    // Check for at least one uppercase letter
    if (!preg_match("/[A-Z]/", $password)) {
        return ["valid" => false, "error" => "Password must contain at least one uppercase letter"];
    }

    // Check for at least one special character
    if (!preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)) {
        return ["valid" => false, "error" => "Password must contain at least one special character"];
    }

    return ["valid" => true];
}

if ($field === "profile_picture" && isset($_FILES['profile_picture'])) {
    $uploadDir = "../frontend/uploads/";
    $fileName = "user_" . $user_id . "_" . time() . "." . pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $uploadPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
        $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $fileName, $user_id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "profile_picture" => $fileName]);
        } else {
            echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "Failed to upload image"]);
    }
    $conn->close();
    exit();
}

// If updating the password
if ($field === "password") {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch the user's current password from the database
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_password);
    $stmt->fetch();
    $stmt->close();

    // Verify the current password
    if (!password_verify($current_password, $db_password)) {
        echo json_encode(["success" => false, "error" => "Current password is incorrect"]);
        exit();
    }

    // Check if the new passwords match
    if ($new_password !== $confirm_password) {
        echo json_encode(["success" => false, "error" => "New password and confirm password do not match"]);
        exit();
    }

    // Validate new password
    $passwordValidation = isValidPassword($new_password);
    if (!$passwordValidation['valid']) {
        echo json_encode(["success" => false, "error" => $passwordValidation['error']]);
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Password updated successfully"]);
    } else {
        echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// If updating other profile fields
$value = trim($_POST['value'] ?? '');
$allowed_fields = [
    "fullName" => "fullName",
    "username" => "username",
    "phoneNumber" => "phoneNumber",
    "emailAddress" => "emailAddress",
    "birthday" => "birthday"
];

if (!array_key_exists($field, $allowed_fields)) {
    echo json_encode(["success" => false, "error" => "Invalid field"]);
    exit();
}

if ($value === "") {
    echo json_encode(["success" => false, "error" => "Field cannot be empty"]);
    exit();
}

// Update the database
$sql = "UPDATE users SET {$allowed_fields[$field]} = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("si", $value, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();