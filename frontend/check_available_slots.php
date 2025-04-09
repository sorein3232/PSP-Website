<?php
session_start();
include("../dbs_connection/database.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['date'])) {
    $date = $_POST['date'];
    
    // Query to get all booked time slots for the selected date
    // Modified to include all appointments that are not cancelled (Pending, On-going, Done)
    $stmt = $conn->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'Cancelled'");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }
    
    echo json_encode(['booked_slots' => $booked_slots]);
    exit();
}

echo json_encode(['error' => 'Invalid request']);
?>