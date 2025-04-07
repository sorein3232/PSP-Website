<?php
include 'database.php'; // Ensure this includes the correct database connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"])) {
    $appointmentId = $_POST["appointment_id"];

    $sql = "DELETE FROM appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $appointmentId);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
    
    $stmt->close();
    $conn->close();
}
?>
