<?php
// Add output buffering at the very top of the file
ob_start();

session_start();
require_once "database.php"; 
// Remove any output or whitespace before including header
ob_clean();
include('includes/header.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Modify logout handling with output buffering
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    // Clear any output before redirecting
    ob_end_clean();
    session_destroy();
    header("Location: adminLogin.php");
    exit();
}

// Modify session check with output buffering
if (!isset($_SESSION['admin'])) {
    ob_end_clean();
    header("Location: adminLogin.php");
    exit();
}

// Handle appointment deletion (AJAX request)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_appointment_id'])) {
    // Ensure no output before sending JSON
    ob_clean();
    header('Content-Type: application/json');

    $appointment_id = intval($_POST['delete_appointment_id']);

    $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to delete"]);
    }

    $stmt->close();
    ob_end_flush();
    exit();
}

// Handle appointment status change (AJAX request)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_status_appointment_id']) && isset($_POST['new_status'])) {
    // Ensure no output before sending JSON
    ob_clean();
    header('Content-Type: application/json');

    $appointment_id = intval($_POST['change_status_appointment_id']);
    $new_status = $_POST['new_status'];

    // Validate status
    $valid_statuses = ['Pending', 'Done', 'Cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(["success" => false, "error" => "Invalid status"]);
        ob_end_flush();
        exit();
    }

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmt->bind_param("si", $new_status, $appointment_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "new_status" => $new_status]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update status"]);
    }

    $stmt->close();
    ob_end_flush();
    exit();
}

// Fetch appointments from the database
$sql = "SELECT u.id, u.fullName, a.*
        FROM appointments a JOIN users u ON u.id = a.user_id ORDER BY a.appointment_date DESC";
$result = $conn->query($sql);
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Appointments</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Appointments</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="bg-black">
                            <tr>
                                <th>APPOINTMENT DATE</th>
                                <th>APPOINTMENT TIME</th>
                                <th>APPOINTMENT ID</th>
                                <th>USER</th>
                                <th>DESCRIPTION</th>
                                <th>TRAINER</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
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
                                    <td class='appointment-status'>" . htmlspecialchars($row['status']) . "</td>
                                    <td>
                                        <div class='btn-group'>
                                            <button class='btn btn-danger btn-delete' data-id='" . $row['appointment_id'] . "'>Delete</button>
                                            <div class='btn-group'>
                                                <button type='button' class='btn btn-secondary dropdown-toggle' data-toggle='dropdown'>
                                                    Change Status
                                                </button>
                                                <div class='dropdown-menu'>
                                                    <a class='dropdown-item status-change' href='#' data-id='" . $row['appointment_id'] . "' data-status='Pending'>Pending</a>
                                                    <a class='dropdown-item status-change' href='#' data-id='" . $row['appointment_id'] . "' data-status='Done'>Done</a>
                                                    <a class='dropdown-item status-change' href='#' data-id='" . $row['appointment_id'] . "' data-status='Cancelled'>Cancelled</a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                  </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8'>No appointments found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
// Flush output buffer
ob_end_flush();
include('includes/footer.php'); 
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Add event listener to all delete buttons
        document.querySelectorAll(".btn-delete").forEach(function (button) {
            button.addEventListener("click", function () {
                const appointmentId = this.getAttribute("data-id");
                const row = this.closest(".appointment-row");

                if (confirm("Are you sure you want to delete this appointment?")) {
                    // Send AJAX request to delete the appointment
                    fetch("appointments.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({ delete_appointment_id: appointmentId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            row.remove();
                            alert("Appointment successfully deleted.");
                        } else {
                            alert("Failed to delete appointment. Please try again.");
                        }
                    })
                }
            });
        });

        // Add event listener to status change links
        document.querySelectorAll(".status-change").forEach(function (link) {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                const appointmentId = this.getAttribute("data-id");
                const newStatus = this.getAttribute("data-status");
                const row = this.closest(".appointment-row");
                const statusCell = row.querySelector(".appointment-status");

                fetch("appointments.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({ 
                        change_status_appointment_id: appointmentId, 
                        new_status: newStatus 
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the status cell
                        statusCell.textContent = data.new_status;
                        alert("Appointment status successfully updated.");
                    } else {
                        alert("Failed to update appointment status. Please try again.");
                    }
                })
            });
        });
    });
</script>