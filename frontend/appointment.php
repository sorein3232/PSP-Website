<?php
session_start();
include("../dbs_connection/database.php");
include '../session_handler/session_timeout.php';

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
        
        
        /* Styles for trainer selection and availability */
        .trainer-selection-container {
            margin: 15px 0;
        }
        
        .trainer-checkbox-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .trainer-checkbox-wrapper input[type="checkbox"] {
            margin-right: 10px;
        }
        
        #trainer-options {
            margin-top: 10px;
        }
        
        /* Trainer schedule styling */
.trainer-schedule-container {
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin-top: 15px;
    background-color: rgba(255, 255, 255, 0.8);
    display: none; /* Initially hidden */
}

.trainer-schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    margin-bottom: 20px;
}

.trainer-schedule-table th,
.trainer-schedule-table td,
.coach-schedule-table th,
.coach-schedule-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    color: #333; /* Ensuring text is dark and readable */
}

.trainer-schedule-table th,
.coach-schedule-table th {
    background-color: #f8f9fa;
    color: #333;
}

.availability-available {
    color: #28a745;
    font-weight: bold;
}

.availability-busy {
    color: #dc3545;
}

.trainer-info {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.trainer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
}

.trainer-name {
    color: #333; /* Making coach names dark and readable */
    font-size: 16px;
}

.coach-avatar-large {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 15px auto;
    font-weight: bold;
    font-size: 24px;
}

.coach-specific-schedule {
    display: none;
    text-align: center;
}

.coach-schedule-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    margin-bottom: 20px;
}

/* Add a back button for individual coach schedules */
.back-to-all {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 15px;
}

.back-to-all:hover {
    background-color: #5a6268;
}

/* Making sure all text in the trainer schedule is readable */
#trainer-schedule h4 {
    color: #333;
    margin-bottom: 15px;
    text-align: center;
}

.coach-specific-schedule h4 {
    color: #333;
    margin-bottom: 15px;
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
<input type="time" id="time-input" name="time" min="06:00" max="00:00" step="900" required>
<small class="form-text text-muted">Select a time between 6:00 AM and 12:00 AM (midnight). Time slots are available in 15-minute intervals. If you wish to avail a personal trainer, please select an appointment time based on your chosen trainer's schedule shown below.</small>
                    
                    
                </select>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required></textarea>

                <!-- Modified trainer selection section -->
                <div class="trainer-selection-container">
                    <div class="trainer-checkbox-wrapper">
                        <input type="checkbox" id="trainer-checkbox">
                        <label for="trainer-checkbox">Would you like to get a personal trainer? (Service charges may vary.)</label>
                    </div>

                    <div id="trainer-options" style="display: none;">
                        <label for="trainer-select">Choose your trainer</label>
                        <select id="trainer-select" name="trainer" disabled>
                            <option value="" disabled selected>Select a Trainer</option>
                            <option value="Coach Nikko">Coach Nikko</option>
                            <option value="Coach Mark">Coach Mark</option>
                            <option value="Coach Jayson">Coach Jayson</option>
                        </select>
                    </div>

                    <div id="trainer-schedule" class="trainer-schedule-container">
    <h4>Trainers' Availability Schedule</h4>
    
    <div class="trainer-info">
        <div class="trainer-avatar">N</div>
        <strong class="trainer-name">Coach Nikko</strong>
    </div>
    <div class="trainer-info">
        <div class="trainer-avatar">M</div>
        <strong class="trainer-name">Coach Mark</strong>
    </div>
    <div class="trainer-info">
        <div class="trainer-avatar">J</div>
        <strong class="trainer-name">Coach Jayson</strong>
    </div>
    
    <!-- Main comparison table shown initially -->
    <table class="trainer-schedule-table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Coach Nikko</th>
                <th>Coach Mark</th>
                <th>Coach Jayson</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Monday</td>
                <td class="availability-available">6AM - 12PM</td>
                <td class="availability-available">1PM - 5PM</td>
                <td class="availability-available">9AM - 3PM</td>
            </tr>
            <tr>
                <td>Tuesday</td>
                <td class="availability-available">9AM - 3PM</td>
                <td class="availability-available">6AM - 12PM</td>
                <td class="availability-busy">Not Available</td>
            </tr>
            <tr>
                <td>Wednesday</td>
                <td class="availability-available">1PM - 5PM</td>
                <td class="availability-available">9AM - 3PM</td>
                <td class="availability-available">6AM - 12PM</td>
            </tr>
            <tr>
                <td>Thursday</td>
                <td class="availability-busy">Not Available</td>
                <td class="availability-available">1PM - 5PM</td>
                <td class="availability-available">9AM - 3PM</td>
            </tr>
            <tr>
                <td>Friday</td>
                <td class="availability-available">6AM - 12PM</td>
                <td class="availability-busy">Not Available</td>
                <td class="availability-available">1PM - 5PM</td>
            </tr>
            <tr>
                <td>Saturday</td>
                <td class="availability-available">9AM - 3PM</td>
                <td class="availability-available">6AM - 12PM</td>
                <td class="availability-available">1PM - 5PM</td>
            </tr>
            <tr>
                <td>Sunday</td>
                <td class="availability-busy">Closed</td>
                <td class="availability-busy">Closed</td>
                <td class="availability-busy">Closed</td>
            </tr>
        </tbody>
    </table>
                        <table class="trainer-schedule-table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Coach Nikko</th>
                                    <th>Coach Mark</th>
                                    <th>Coach Jayson</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Monday</td>
                                    <td class="availability-available">6AM - 12PM</td>
                                    <td class="availability-available">1PM - 5PM</td>
                                    <td class="availability-available">9AM - 3PM</td>
                                </tr>
                                <tr>
                                    <td>Tuesday</td>
                                    <td class="availability-available">9AM - 3PM</td>
                                    <td class="availability-available">6AM - 12PM</td>
                                    <td class="availability-busy">Not Available</td>
                                </tr>
                                <tr>
                                    <td>Wednesday</td>
                                    <td class="availability-available">1PM - 5PM</td>
                                    <td class="availability-available">9AM - 3PM</td>
                                    <td class="availability-available">6AM - 12PM</td>
                                </tr>
                                <tr>
                                    <td>Thursday</td>
                                    <td class="availability-busy">Not Available</td>
                                    <td class="availability-available">1PM - 5PM</td>
                                    <td class="availability-available">9AM - 3PM</td>
                                </tr>
                                <tr>
                                    <td>Friday</td>
                                    <td class="availability-available">6AM - 12PM</td>
                                    <td class="availability-busy">Not Available</td>
                                    <td class="availability-available">1PM - 5PM</td>
                                </tr>
                                <tr>
                                    <td>Saturday</td>
                                    <td class="availability-available">9AM - 3PM</td>
                                    <td class="availability-available">6AM - 12PM</td>
                                    <td class="availability-available">1PM - 5PM</td>
                                </tr>
                                <tr>
                                    <td>Sunday</td>
                                    <td class="availability-busy">Closed</td>
                                    <td class="availability-busy">Closed</td>
                                    <td class="availability-busy">Closed</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
<!-- Coach Nikko's Schedule -->
<div id="Coach-Nikko-schedule" class="coach-specific-schedule">
        <h4>Coach Nikko's Schedule</h4>
        <div class="coach-avatar-large">N</div>
        <table class="coach-schedule-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monday</td>
                    <td class="availability-available">6AM - 12PM</td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td class="availability-available">9AM - 3PM</td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td class="availability-available">1PM - 5PM</td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td class="availability-busy">Not Available</td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td class="availability-available">6AM - 12PM</td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td class="availability-available">9AM - 3PM</td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td class="availability-busy">Closed</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="Coach-Mark-schedule" class="coach-specific-schedule">
        <h4>Coach Mark's Schedule</h4>
        <div class="coach-avatar-large">M</div>
        <table class="coach-schedule-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monday</td>
                    <td class="availability-available">1PM - 5PM</td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td class="availability-available">6AM - 12PM</td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td class="availability-available">9AM - 3PM</td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td class="availability-available">1PM - 5PM</td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td class="availability-busy">Not Available</td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td class="availability-available">6AM - 12PM</td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td class="availability-busy">Closed</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="Coach-Jayson-schedule" class="coach-specific-schedule">
        <h4>Coach Jayson's Schedule</h4>
        <div class="coach-avatar-large">J</div>
        <table class="coach-schedule-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monday</td>
                    <td class="availability-available">9AM - 3PM</td>
                </tr>
                <tr>
                    <td>Tuesday</td>
                    <td class="availability-busy">Not Available</td>
                </tr>
                <tr>
                    <td>Wednesday</td>
                    <td class="availability-available">6AM - 12PM</td>
                </tr>
                <tr>
                    <td>Thursday</td>
                    <td class="availability-available">9AM - 3PM</td>
                </tr>
                <tr>
                    <td>Friday</td>
                    <td class="availability-available">1PM - 5PM</td>
                </tr>
                <tr>
                    <td>Saturday</td>
                    <td class="availability-available">1PM - 5PM</td>
                </tr>
                <tr>
                    <td>Sunday</td>
                    <td class="availability-busy">Closed</td>
                </tr>
            </tbody>
        </table>
    </div>
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
            if ($("#time-input").val()) {
                checkDateTimeLeeway();
            }
            
            // Check for available time slots
            checkAvailableTimeSlots(dateStr);
        }
    });
    
    // Time input validation
    $("#time-input").on("change", function() {
        const timeValue = $(this).val();
        if (timeValue) {
            const hour = parseInt(timeValue.split(':')[0]);
            const minute = parseInt(timeValue.split(':')[1]);
            
            // Validate time is within operating hours (6 AM to 12 AM)
            if ((hour < 6) || (hour === 0 && minute > 0)) {
                alert("Please select a time between 6:00 AM and 12:00 AM (midnight).");
                $(this).val("");
                return;
            }
            
            // Validate that time is in 15-minute intervals
            if (minute % 15 !== 0) {
                const roundedMinute = Math.round(minute / 15) * 15;
                const adjustedHour = hour + (roundedMinute === 60 ? 1 : 0);
                const adjustedMinute = roundedMinute === 60 ? '00' : (roundedMinute < 10 ? '0' + roundedMinute : roundedMinute);
                const adjustedTime = (adjustedHour < 10 ? '0' + adjustedHour : adjustedHour) + ':' + adjustedMinute;
                
                alert("Times must be in 15-minute intervals. Your time has been adjusted to " + formatTimeForDisplay(adjustedTime));
                $(this).val(adjustedTime);
            }
            
            checkDateTimeLeeway();
            
            // Check availability if date is selected
            if ($("#date").val()) {
                checkAvailableTimeSlots($("#date").val());
            }
        }
    });
    
    // Format time for display (24h to 12h format)
    function formatTimeForDisplay(time24h) {
        const [hour, minute] = time24h.split(':');
        let hour12 = parseInt(hour) % 12;
        if (hour12 === 0) hour12 = 12;
        return hour12 + ':' + minute + (parseInt(hour) >= 12 ? ' PM' : ' AM');
    }
    
    // Function to check available time slots for selected date
    function checkAvailableTimeSlots(selectedDate) {
        if (selectedDate && $("#time-input").val()) {
            $.ajax({
                url: 'check_available_slots.php',
                method: 'POST',
                data: { 
                    date: selectedDate, 
                    time: $("#time-input").val() 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.is_booked) {
                        alert("This time slot is already booked. Please select another time.");
                        $("#time-input").val("");
                    }
                }
            });
        }
    }
    
    // Function to check if selected datetime is at least 24 hours in the future
    function checkDateTimeLeeway() {
        if ($("#date").val() && $("#time-input").val()) {
            let selectedDate = $("#date").val();
            let selectedTime = $("#time-input").val();
            let selectedDateTime = new Date(selectedDate + 'T' + selectedTime);
            let now = new Date();
            
            // Calculate time difference in hours
            let timeDiff = (selectedDateTime - now) / (1000 * 60 * 60);
            
            if (timeDiff < 24) {
                alert("Appointments must be made at least 24 hours in advance.");
                $("#time-input").val("");
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
        $("#trainer-schedule").toggle(this.checked);
        
        if (!this.checked) {
            $("#trainer-select").val(""); // Clear selection
            $("#trainer-select").prop('disabled', true); // Disable dropdown
            
            // Hide all coach specific schedules
            $(".coach-specific-schedule").hide();
            
        } else {
            $("#trainer-select").prop('disabled', false); // Enable dropdown
            
            // Show the main comparison table initially
            $(".trainer-schedule-table").show();
            
            // Show alert to select a trainer
            alert("Please select a trainer from the dropdown.");
        }
    });
    
    // Coach selection changes
    $("#trainer-select").on("change", function() {
        const selectedCoach = $(this).val();
        
        if (!selectedCoach) {
            // If no coach is selected, hide all individual schedules
            $(".coach-specific-schedule").hide();
            // Show the full comparison table
            $(".trainer-schedule-table").show();
            return;
        }
        
        // Hide all coach schedules first
        $(".coach-specific-schedule").hide();
        // Hide the full comparison table
        $(".trainer-schedule-table").hide();
        
        // Show only the selected coach's schedule - use the exact ID format
        $(`#${selectedCoach.replace(/\s+/g, '-')}-schedule`).show();
    });
    
    // Add back buttons to individual coach schedules
    $(".coach-specific-schedule").each(function() {
        const backBtn = $('<button class="back-to-all">Back to All Schedules</button>');
        $(this).prepend(backBtn);
        
        backBtn.on('click', function() {
            $(".coach-specific-schedule").hide();
            $(".trainer-schedule-table").show();
            $("#trainer-select").val(""); // Reset selection
        });
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
                        $("#trainer-schedule").hide();
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
                        $("#trainer-schedule").hide();
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