<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit();
}

// Database connection
include("../dbs_connection/database.php");

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = "frozen"; // Ensure it's lowercase to match profile.php

    $sql = "UPDATE users SET membership_status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Membership has been frozen successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error freezing membership."]);
    }

    $stmt->close();
    $conn->close();
}
?>
