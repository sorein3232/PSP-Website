<?php
session_start();
include("../dbs_connection/database.php");

header("Content-Type: application/json");

$user_id = $_SESSION['user_id'];
$appointment_id = $_POST['appointment_id'] ?? '';

if (!$appointment_id) {
    echo json_encode(["success" => false, "error" => "Invalid appointment ID"]);
    exit();
}

// Check if the appointment exists and belongs to the user
$stmt = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

if ($status !== 'Pending') {
    echo json_encode(["success" => false, "error" => "Appointment cannot be canceled"]);
    exit();
}

// Delete the appointment from the database
$stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $appointment_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Appointment canceled successfully"]);
} else {
    echo json_encode(["success" => false, "error" => "Database error"]);
}

$stmt->close();
$conn->close();
