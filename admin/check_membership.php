<?php
session_start();
require_once 'database.php';

header("Content-Type: application/json");

$currentDate = new DateTime();

// Find all frozen users who have been frozen for 2+ months
$stmt = $conn->prepare("SELECT id, frozen_at FROM users WHERE membership_status = 'frozen'");
$stmt->execute();
$result = $stmt->get_result();

$usersToUnfreeze = [];

while ($row = $result->fetch_assoc()) {
    $frozenAt = new DateTime($row['frozen_at']);
    $interval = $frozenAt->diff($currentDate);

    if ($interval->m >= 2) {
        $usersToUnfreeze[] = $row['id'];
    }
}

$stmt->close();

if (!empty($usersToUnfreeze)) {
    $placeholders = implode(',', array_fill(0, count($usersToUnfreeze), '?'));
    $query = "UPDATE users SET membership_status = 'active', frozen_at = NULL WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    $stmt->bind_param(str_repeat('i', count($usersToUnfreeze)), ...$usersToUnfreeze);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        "success" => true,
        "message" => count($usersToUnfreeze) . " users have been unfrozen."
    ]);
} else {
    echo json_encode(["success" => false]);
}

$conn->close();
