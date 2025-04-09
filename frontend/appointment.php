<?php
session_start();
include("../dbs_connection/database.php");

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === "book_appointment") {
        // Check for existing pending appointments
        $stmt = $conn->prepare("SELECT COUNT(*) as existPending FROM appointments WHERE user_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['existPending'] > 0) {
            // Changed to include an alert in the response
            echo json_encode(['success' => false, 'message' => 'You already have a pending appointment.', 'alert' => true]);
            exit();
        }

        $appointment_date = $_POST['date'];
        $appointment_time = $_POST['time'];
        $description = $_POST['description'];
        
        // Modify trainer handling to allow null/empty value
        $trainer = isset($_POST['trainer']) && $_POST['trainer'] !== '' ? $_POST['trainer'] : null;

        // Get current date and time
        $current_datetime = new DateTime('now');
        $appointment_datetime = new DateTime($appointment_date . ' ' . $appointment_time);
        
        // Calculate time difference in hours
        $time_diff = $appointment_datetime->getTimestamp() - $current_datetime->getTimestamp();
        $hours_diff = $time_diff / 3600; // Convert seconds to hours
        
        $day_of_week = date("w", strtotime($appointment_date));

        // Check if appointment is at least 24 hours in the future
        if ($hours_diff < 24) {
            echo json_encode(['success' => false, 'message' => 'Appointments must be made at least 24 hours in advance.']);
            exit();
        }

        // Check for Sunday
        if ($day_of_week == 0) {
            echo json_encode(['success' => false, 'message' => 'Appointments are not available on Sundays.']);
            exit();
        }

        $hour = (int)date("H", strtotime($appointment_time));
        if ($hour < 6 || $hour > 17) {
            echo json_encode(['success' => false, 'message' => 'Invalid time! Allowed from 6 AM to 5 PM.']);
            exit();
        }

        // MODIFIED: Check if the time slot is already booked (regardless of status except 'Cancelled')
        $stmt = $conn->prepare("SELECT COUNT(*) as slot_exists FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'Cancelled'");
        $stmt->bind_param("ss", $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['slot_exists'] > 0) {
            echo json_encode(['success' => false, 'message' => 'This time slot is already booked. Please select another time.']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, appointment_time, description, trainer) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $appointment_date, $appointment_time, $description, $trainer);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment successfully booked!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error booking appointment. Try again.']);
        }
        $stmt->close();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment</title>
    <link rel="stylesheet" href="css/appointment.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Flatpickr CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Add styling for disabled dates and other custom styles -->
    <style>
        /* Style for the flatpickr calendar */
        .flatpickr-day.disabled, 
        .flatpickr-day.disabled:hover {
            color: #ccc !important;
            background-color: #f5f5f5 !important;
            text-decoration: line-through;
            cursor: not-allowed !important;
        }
        
        /* Custom time input styling */
        #time-select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        /* Additional styles for the date input to match other form elements */
        #date {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <img src="../frontend/resources/psp-logo.jpg" alt="Logo" class="logo-image" />
            <a href="index.php" class="logo-text">Philippine Sports Performance</a>
        </div>
        <div class="header-right">
            <a href="index.php">Home</a>
            <a class="active" href="appointment.php">Appointment</a>
            <a href="aboutus.php">About Us</a>
            <a href="schedule.php">Schedule</a>
            <a href="profile.php">Profile</a>

            <div class="notification-container">
                <button class="notification-btn" onclick="toggleNotificationPopup()">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="notification-popup" id="notificationPopup">
                    <p>Loading notifications...</p>
                </div>
            </div>

            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        </div>
    </div>

    <div class="appointment-section">
        <h1>SET UP AN APPOINTMENT</h1>
        <div class="appointment-form">
            <form id="appointmentForm">
                <label for="date">Appointment Date</label>
                <input type="text" id="date" name="date" placeholder="Select Date" required>
                
                <label for="time">Appointment Time</label>
                <select id="time-select" name="time" required>
                    <option value="" disabled selected>Select a time</option>
                    <option value="06:00">6:00 AM</option>
                    <option value="07:00">7:00 AM</option>
                    <option value="08:00">8:00 AM</option>
                    <option value="09:00">9:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="12:00">12:00 PM</option>
                    <option value="13:00">1:00 PM</option>
                    <option value="14:00">2:00 PM</option>
                    <option value="15:00">3:00 PM</option>
                    <option value="16:00">4:00 PM</option>
                    <option value="17:00">5:00 PM</option>
                </select>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>

                <label>
                    <input type="checkbox" id="trainer-checkbox">
                    Would you like to get a personal trainer? (Service charges may vary.)
                </label>

                <div id="trainer-options" style="display: none;">
                    <label for="trainer-select">Choose your trainer</label>
                    <select id="trainer-select" name="trainer" disabled>
                        <option value="" disabled selected>Select a Trainer</option>
                        <option value="Coach Nikko">Coach Nikko</option>
                        <option value="Coach Mark">Coach Mark</option>
                        <option value="Coach Jayson">Coach Jayson</option>
                    </select>
                </div>

                <button type="submit">Set Appointment</button>
            </form>
            <p id="status-message"></p>
        </div>

        <div class="appointment-list">
            <h2>Your Appointments</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Description</th>
                            <th>Trainer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $appointments = $result->fetch_all(MYSQLI_ASSOC);

                        foreach ($appointments as $row) {
                            $appointment_id = $row['appointment_id'];
                            $status = $row['status'];
                        ?>
                            <tr id="appointment-row-<?= $appointment_id ?>">
                                <td><?= date('F d, Y', strtotime($row['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($row['appointment_time'])) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= htmlspecialchars($row['trainer'] ?? 'No Trainer') ?></td>
                                <td id="status-<?= $appointment_id ?>"><?= htmlspecialchars($status) ?></td>
                                <td>
                                    <?php if ($status === 'Pending') { ?>
                                        <button onclick="cancelAppointment(<?= $appointment_id ?>)">Cancel</button>
                                    <?php } elseif ($status === 'Approved') { ?>
                                        <span>Approved</span>
                                    <?php } else  { ?>
                                        <span>Cancelled</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="leftfooter">
            <h1>About Us</h1>
            <p>851 A.H Lacson cor España Blvd. Sampaloc, Manila, Philippines</p>
            <h1>Contact Us</h1>
            <p>📞 09602862411</p>
            <p>📧 pspubelt@gmail.com</p>
        </div>
        <div class="rightfooter">
            <h1>Socials</h1>
            <p>📍 851 A.H Lacson cor España Blvd. Sampaloc, Manila, Philippines</p>
            <p>📱 @pspubeltmanila</p>
            <p>📱 @pspubeltmanilapro</p>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Calculate tomorrow's date for minimum date selection
        let tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        // Initialize Flatpickr
        const datePicker = flatpickr("#date", {
            minDate: tomorrow,
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    // Disable Sundays (0 is Sunday)
                    return date.getDay() === 0;
                }
            ],
            // Optional: Add this to show the month and enable navigation between months
            monthSelectorType: "static",
            // Prevent selection of disabled dates
            onChange: function(selectedDates, dateStr, instance) {
                const selected = selectedDates[0];
                if (selected && selected.getDay() === 0) {
                    alert("Appointments are not available on Sundays.");
                    instance.clear();
                    return false;
                }
                
                // Check date and time leeway if time is already selected
                if ($("#time-select").val()) {
                    checkDateTimeLeeway();
                }
                
                // Check for available time slots
                checkAvailableTimeSlots(dateStr);
            }
        });
        
        // Function to check available time slots for selected date
        function checkAvailableTimeSlots(selectedDate) {
            if (selectedDate) {
                $.ajax({
                    url: 'check_available_slots.php',
                    method: 'POST',
                    data: { date: selectedDate },
                    dataType: 'json',
                    success: function(response) {
                        // Reset all options to enabled
                        $("#time-select option").prop('disabled', false);
                        
                        // Disable times that are already booked
                        if (response.booked_slots && response.booked_slots.length > 0) {
                            response.booked_slots.forEach(function(time) {
                                $("#time-select option[value='" + time + "']").prop('disabled', true);
                            });
                        }
                        
                        // If currently selected time is now disabled, reset selection
                        if ($("#time-select").val() && $("#time-select option:selected").prop('disabled')) {
                            $("#time-select").val("");
                            alert("Your previously selected time is no longer available. Please select another time.");
                        }
                    }
                });
            }
        }
        
        // Function to check if selected datetime is at least 24 hours in the future
        function checkDateTimeLeeway() {
            if ($("#date").val() && $("#time-select").val()) {
                let selectedDate = $("#date").val();
                let selectedTime = $("#time-select").val();
                let selectedDateTime = new Date(selectedDate + 'T' + selectedTime);
                let now = new Date();
                
                // Calculate time difference in hours
                let timeDiff = (selectedDateTime - now) / (1000 * 60 * 60);
                
                if (timeDiff < 24) {
                    alert("Appointments must be made at least 24 hours in advance.");
                    return false;
                }
            }
            return true;
        }

        // Validate time when it changes
        $("#time-select").on("change", function() {
            checkDateTimeLeeway();
            
            // Check availability when time changes too
            if ($("#date").val()) {
                checkAvailableTimeSlots($("#date").val());
            }
        });

        // Trainer checkbox toggle
        $("#trainer-checkbox").on("change", function() {
            $("#trainer-options").toggle(this.checked);
            
            if (!this.checked) {
                $("#trainer-select").val(""); // Clear selection
                $("#trainer-select").prop('disabled', true); // Disable dropdown
            } else {
                $("#trainer-select").prop('disabled', false); // Enable dropdown
                // Show alert to select a trainer
                alert("Please select a trainer from the dropdown.");
            }
        });

        // Form submission
        $("#appointmentForm").on("submit", function(e) {
            e.preventDefault();
            
            // Check appointment time leeway
            if (!checkDateTimeLeeway()) {
                return;
            }
            
            // Check if trainer checkbox is checked
            if ($("#trainer-checkbox").is(":checked")) {
                // If no trainer is selected
                if ($("#trainer-select").val() === null || $("#trainer-select").val() === "") {
                    alert("Please select a trainer before submitting the form.");
                    return;
                }
            }
            
            // If trainer checkbox is not checked, remove trainer from form data
            if (!$("#trainer-checkbox").is(":checked")) {
                let formData = $(this).serializeArray();
                formData = formData.filter(function(item) {
                    return item.name !== 'trainer';
                });
                
                let serializedData = $.param(formData) + '&action=book_appointment';
                
                $.ajax({
                    url: 'appointment.php',
                    method: 'POST',
                    data: serializedData,
                    dataType: 'json',
                    success: function(response) {
                        $("#status-message").text(response.message).css("color", response.success ? "green" : "red");
                        
                        // Check if there's an alert to show
                        if (response.alert) {
                            alert("You already have a pending appointment!");
                            return;
                        }
                        
                        if (response.success) {
                            // Add alert prompt for successful appointment
                            alert("Appointment added successfully!");
                            
                            $("#appointmentForm")[0].reset();
                            $("#trainer-options").hide();
                            $("#trainer-select").prop('disabled', true);
                            $("#trainer-select").val("");
                            location.reload(); // Reload to update appointments list
                        }
                    }
                });
            } else {
                // If checkbox is checked, submit normally
                $.ajax({
                    url: 'appointment.php',
                    method: 'POST',
                    data: $(this).serialize() + '&action=book_appointment',
                    dataType: 'json',
                    success: function(response) {
                        $("#status-message").text(response.message).css("color", response.success ? "green" : "red");
                        
                        // Check if there's an alert to show
                        if (response.alert) {
                            alert("You already have a pending appointment!");
                            return;
                        }
                        
                        if (response.success) {
                            // Add alert prompt for successful appointment
                            alert("Appointment added successfully!");
                            
                            $("#appointmentForm")[0].reset();
                            $("#trainer-options").hide();
                            $("#trainer-select").prop('disabled', true);
                            $("#trainer-select").val("");
                            location.reload(); // Reload to update appointments list
                        }
                    }
                });
            }
        });
    });

    // Notification popup functionality
    window.addEventListener('click', function(event) {
        var popup = document.getElementById("notificationPopup");
        var notificationBtn = document.querySelector('.notification-btn');

        if (!popup.contains(event.target) && !notificationBtn.contains(event.target)) {
            popup.classList.remove('active');
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        fetchNotifications();
    });

    function fetchNotifications() {
        fetch("get_notifications.php")
            .then(response => response.json())
            .then(data => {
                const notificationPopup = document.getElementById("notificationPopup");
                notificationPopup.innerHTML = '<div class="notification-header"><h4>Notifications</h4></div>';

                if (data.length > 0) {
                    data.forEach(notification => {
                        notificationPopup.innerHTML += `
                    <div class="notification-message">
                        <p><strong>${notification.type}</strong><br>${notification.message}</p>
                        <small>${notification.time}</small>
                    </div>`;
                    });
                } else {
                    notificationPopup.innerHTML += '<p>No new notifications</p>';
                }
            })
            .catch(error => console.error("Error fetching notifications:", error));
    }

    function toggleNotificationPopup() {
        const popup = document.getElementById("notificationPopup");
        popup.classList.toggle("show");
    }

    function cancelAppointment(appointmentId) {
        if (!confirm("Are you sure you want to cancel this appointment?")) {
            return;
        }

        fetch('cancel_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'appointment_id=' + appointmentId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to update appointments list
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again.");
            });
    }
    </script>
</body>
</html>