<?php
include 'database.php'; // Ensure your database connection is included

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"])) {
    $user_id = $_POST["user_id"];

    // Ensure correct table name
    $sql = "DELETE FROM gym_members WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Failed to remove member.";
        }
        $stmt->close();
    } else {
        echo "SQL Error: " . $conn->error;
    }

    $conn->close();
} else {
    echo "Invalid request.";
}
?>
