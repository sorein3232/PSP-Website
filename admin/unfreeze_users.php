<?php
session_start();
require_once 'database.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_ids'])) {
    $userIds = $_POST['user_ids'];

    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $query = "UPDATE users SET membership_status = 'active', frozen_at = NULL WHERE id IN ($placeholders)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["success" => true, "message" => "Selected users have been unfrozen."]);
    } else {
        echo json_encode(["success" => false, "error" => "No users selected."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid request."]);
}

$conn->close();
