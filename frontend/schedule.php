<?php
session_start();
include("../dbs_connection/database.php");

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit();
}

// Fetch schedule data from database with custom day ordering
$sql = "SELECT * FROM schedule 
        ORDER BY 
            CASE day 
                WHEN 'Monday' THEN 1 
                WHEN 'Tuesday' THEN 2 
                WHEN 'Wednesday' THEN 3 
                WHEN 'Thursday' THEN 4 
                WHEN 'Friday' THEN 5 
                WHEN 'Saturday' THEN 6 
                ELSE 7 
            END ASC, 
            start_time ASC";
$result = $conn->query($sql);

// Store all schedule items in an array
$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

// Try to find the Sunday schedule (ID 7)
$sundaySchedule = null;
foreach ($schedules as $key => $schedule) {
    if ($schedule['id'] == 7) {
        $sundaySchedule = $schedule;
        // Remove it from the regular schedule array
        unset($schedules[$key]);
        break;
    }
}
// Reset array keys after removing Sunday
$schedules = array_values($schedules);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - PSP</title>
    <link rel="stylesheet" href="css/schedule.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/header.css">
    <link rel="stylesheet" href="../frontend/css/notification.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    
        /* 3x3 Grid Layout */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto auto auto;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .class-card {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .class-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .class-card-content {
            padding: 15px;
            margin: 0;
            text-align: center;
        }
        
        /* Style for the row-by-row text display */
        .schedule-info-line {
            display: block;
            margin-bottom: 5px;
        }
        
        .schedule-info-day {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }
        
        /* Style for the centered card in third row */
        .center-third-row {
            grid-column: 2; /* Center column */
            grid-row: 3; /* Third row */
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="header">
        <!-- Left section: Logo and Website Name -->
        <div class="header-left">
            <img src="../frontend/resources/psp-logo.jpg" alt="Logo" class="logo-image" />
            <a href="index.php" class="logo-text">Philippine Sports Performance</a>
        </div>

        <!-- Right section: Navigation Links -->
        <div class="header-right">
            <a href="index.php">Home</a>
            <a href="appointment.php">Appointment</a>
            <a href="aboutus.php">About Us</a>
            <a class="active" href="schedule.php">Schedule</a>
            <a href="profile.php">Profile</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="notification-container">
                    <button class="notification-btn" onclick="toggleNotificationPopup()">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="notification-popup" id="notificationPopup">
                        <p>Loading notifications...</p>
                    </div>
                </div>

                <!-- Logout Button with Icon -->
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> <!-- Font Awesome logout icon -->
                </button>
            <?php else: ?>
                <!-- Login and Signup Buttons for non-logged-in users -->
                <button class="login-btn" onclick="window.location.href='login.php'">Login</button>
                <button class="signup-btn" onclick="window.location.href='register.php'">Sign Up</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main">
        <div class="schedule-banner">
            <h1>Schedules</h1>
        </div>

        <div class="group-class-schedule">
            <h2>Group Class Schedule</h2>
            <div class="schedule-grid">
                <?php
                $count = 0;
                
                // Create the first 6 class cards (positions 0-5)
                for ($i = 0; $i < 6; $i++) {
                    if ($count < count($schedules)) {
                        $schedule = $schedules[$count];
                        
                        // Format time display based on available fields
                        $timeDisplay = "Time not specified";
                        if (isset($schedule['start_time']) && isset($schedule['end_time'])) {
                            $timeDisplay = date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                          date('h:i A', strtotime($schedule['end_time']));
                        } else if (isset($schedule['time'])) {
                            $timeDisplay = date('h:i A', strtotime($schedule['time']));
                        }
                        
                        echo '<div class="class-card">
                                <img src="../admin/uploads/' . $schedule['schedule_picture'] . '" alt="' . $schedule['activity_description'] . '">
                                <div class="class-card-content">
                                    <span class="schedule-info-line schedule-info-day">' . $schedule['day'] . '</span>
                                    <span class="schedule-info-line">' . $schedule['activity_description'] . '</span>
                                    <span class="schedule-info-line">' . $schedule['personnel_name'] . '</span>
                                    <span class="schedule-info-line">' . $timeDisplay . '</span>
                                </div>
                            </div>';
                        $count++;
                    } else {
                        echo '<div class="class-card">
                                <img src="../admin/uploads/placeholder.jpg" alt="No Schedule">
                                <div class="class-card-content">
                                    <span class="schedule-info-line">No Class Scheduled</span>
                                </div>
                            </div>';
                    }
                }
                
                // Add the Sunday card (ID 7) in the center of the third row with the same design as other cards
                if ($sundaySchedule) {
                    // Format time display based on available fields
                    $timeDisplay = "Time not specified";
                    if (isset($sundaySchedule['start_time']) && isset($sundaySchedule['end_time'])) {
                        $timeDisplay = date('h:i A', strtotime($sundaySchedule['start_time'])) . ' - ' . 
                                      date('h:i A', strtotime($sundaySchedule['end_time']));
                    } else if (isset($sundaySchedule['time'])) {
                        $timeDisplay = date('h:i A', strtotime($sundaySchedule['time']));
                    }
                    
                    echo '<div class="class-card center-third-row">
                            <img src="../admin/uploads/' . $sundaySchedule['schedule_picture'] . '" alt="' . $sundaySchedule['activity_description'] . '">
                            <div class="class-card-content">
                                <span class="schedule-info-line schedule-info-day">' . $sundaySchedule['day'] . '</span>
                                <span class="schedule-info-line">' . $sundaySchedule['activity_description'] . '</span>
                                <span class="schedule-info-line">' . $sundaySchedule['personnel_name'] . '</span>
                                <span class="schedule-info-line">' . $timeDisplay . '</span>
                            </div>
                        </div>';
                } else {
                    // Fallback if ID 7 is not found
                    echo '<div class="class-card center-third-row">
                            <img src="../admin/uploads/closed_sunday.jpg" alt="Closed on Sunday">
                            <div class="class-card-content">
                                <span class="schedule-info-line schedule-info-day">Sunday</span>
                                <span class="schedule-info-line">CLOSED</span>
                                <span class="schedule-info-line">Open Monday-Saturday</span>
                                <span class="schedule-info-line">6:00 AM - 5:00 PM</span>
                            </div>
                        </div>';
                }
                ?>
            </div>
        </div>
        
        
    </div>

    <!-- Footer Section -->
    <div class="footer">
        <!-- Left Footer Section -->
        <div class="leftfooter">
            <h1>About Us</h1>
            <p>851 A.H Lacson cor España Blvd. Sampaloc, Manila, Philippines</p>
            <h1>Contact Us</h1>
            <p>📞 09602862411</p>
            <p>📧 pspubelt@gmail.com</p>
        </div>

        <!-- Right Footer Section -->
        <div class="rightfooter">
            <h1>Socials</h1>
            <p>📍 851 A.H Lacson cor España Blvd. Sampaloc, Manila, Philippines</p>
            <p>📱 @pspubeltmanila</p>
            <p>📱 @pspubeltmanilapro</p>
        </div>
    </div>

    <script>
        // Close the notification when clicking outside of it
        window.addEventListener('click', function(event) {
            var popup = document.getElementById("notificationPopup");
            var notificationBtn = document.querySelector('.notification-btn');

            // Close the notification if clicked outside of the popup or the notification button
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

                    // notificationPopup.innerHTML += '<button class="see-notifications-btn">See previous notifications</button>';
                })
                .catch(error => console.error("Error fetching notifications:", error));
        }

        function toggleNotificationPopup() {
            const popup = document.getElementById("notificationPopup");
            popup.classList.toggle("show");
        }
    </script>
</body>

</html>

<?php $conn->close(); ?>