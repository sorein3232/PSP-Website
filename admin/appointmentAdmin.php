<?php
session_start();
require_once "database.php";

// Redirect to login if not an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: adminLogin.php");
    exit();
}

// Logout handling
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Handle appointment deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_appointment_id'])) {
    header('Content-Type: application/json');

    $appointment_id = intval($_POST['delete_appointment_id']);

    // Prepare and execute delete statement with comprehensive error handling
    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);

    try {
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    "success" => true, 
                    "message" => "Appointment successfully deleted"
                ]);
            } else {
                echo json_encode([
                    "success" => false, 
                    "message" => "No appointment found with the given ID",
                    "error_code" => "NO_APPOINTMENT"
                ]);
            }
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        error_log("Appointment Deletion Error: " . $e->getMessage());
        echo json_encode([
            "success" => false, 
            "message" => "Failed to delete appointment",
            "error" => $e->getMessage(),
            "error_code" => "DB_ERROR"
        ]);
    } finally {
        $stmt->close();
    }
    exit();
}

// Fetch appointments from the database
$sql = "SELECT u.id, u.fullName, a.*
        FROM appointments a JOIN users u ON u.id = a.user_id ORDER BY a.appointment_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Admin - Appointments</title>
    <link rel="stylesheet" href="css/appointmentAdmin.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo">
                <img src="../frontend/resources/psp-logo.jpg" alt="PSP Logo">
                <h1>PHILIPPINE SPORTS PERFORMANCE</h1>
            </div>
            <button class="logout" onclick="window.location.href='?logout=true'">Logout</button>
        </header>

        <div class="sidebar">
            <ul>
                <li>👤 <a href="gymMembers.php">Gym Members</a></li>
                <li>📅 <a href="appointmentAdmin.php"> Appointments</a></li>
                <li>📊 <a href="advertisement.php"> Advertisements</a></li>
                <li>📆 <a href="scheduleAdmin.php"> Schedule</a></li>
                <li>📣 <a href="announcement.php"> Announcements</a></li>
                <li>💵 <a href="payment.php"> Payments</a></li>
            </ul>
        </div>

        <main class="main-content">
            <div class="search-bar">
                <label for="search-appointment">SEARCH APPOINTMENT:</label>
                <input type="text" id="search-appointment" placeholder="Enter Appointment ID">
            </div>

            <table class="appointment-table">
                <thead>
                    <tr>
                        <th>APPOINTMENT DATE</th>
                        <th>APPOINTMENT TIME</th>
                        <th>APPOINTMENT ID</th>
                        <th>USER</th>
                        <th>DESCRIPTION</th>
                        <th>TRAINER</th>
                        <th>CANCEL</th>
                    </tr>
                </thead>
                <tbody id="appointment-list">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr class='appointment-row'>
                                    <td>" . htmlspecialchars($row['appointment_date']) . "</td>
                                    <td>" . htmlspecialchars($row['appointment_time']) . "</td>
                                    <td class='appointment-id'>" . htmlspecialchars($row['appointment_id']) . "</td>
                                    <td>" . htmlspecialchars($row['fullName']) . "</td>
                                    <td>" . htmlspecialchars($row['description']) . "</td>
                                    <td>" . ($row['trainer'] ? htmlspecialchars($row['trainer']) : 'None') . "</td>
                                    <td><button class='cancel' data-id='" . $row['appointment_id'] . "'>Cancel</button></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No appointments found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Delete appointment function
        function deleteAppointment(appointmentId, button) {
            if (!confirm("Are you sure you want to cancel this appointment?")) return;

            fetch("appointmentAdmin.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "delete_appointment_id=" + appointmentId
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message || "Appointment canceled successfully.");
                    button.closest("tr").remove();
                } else {
                    // More detailed error message
                    alert(data.message || "Failed to cancel appointment. " + 
                          (data.error ? "Error: " + data.error : ""));
                    console.error("Deletion Error:", data);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An unexpected error occurred. Please try again.");
            });
        }

        // Attach event listeners to cancel buttons
        document.querySelectorAll(".cancel").forEach(button => {
            button.addEventListener("click", function() {
                let appointmentId = this.getAttribute("data-id");
                deleteAppointment(appointmentId, this);
            });
        });

        // Search functionality
        document.getElementById("search-appointment").addEventListener("input", function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll(".appointment-row").forEach(row => {
                let appointmentId = row.querySelector(".appointment-id").textContent.toLowerCase();
                row.style.display = appointmentId.includes(filter) ? "" : "none";
            });
        });
    });
    </script>
</body>
</html>