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
            time ASC";
$result = $conn->query($sql);
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
        /* Additional styles for the opening hours section */
        .hours-of-operation {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .hours-of-operation h2 {
            color: #333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .operation-hours p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .operation-hours strong {
            color: #0056b3;
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
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="class-card">
                        <img src="../admin/uploads/<?= $row['schedule_picture'] ?>" alt="<?= $row['activity_description'] ?>">
                        <p><?= $row['day'] ?>: <?= $row['activity_description'] ?><br> <?= $row['personnel_name'] ?>, <?= date('h:i A', strtotime($row['time'])) ?></p>
                    </div>
                <?php endwhile; ?>
                
                <!-- Sunday Closed Class Card -->
                <div class="class-card sunday-closed">
                    <img src="../admin/uploads/closed_sunday.jpg" alt="Closed on Sunday">
                    <p><strong>Sunday:</strong> CLOSED<br>Open Monday-Saturday: 6:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
        
        <!-- Hours of Operation Information - Centered with light background below schedule -->
        <div class="hours-of-operation">
            <h2>Opening Hours</h2>
            <div class="operation-hours">
                <p><strong>Monday - Saturday:</strong> Open from 6:00 AM to 5:00 PM</p>
                <p><strong>Sunday:</strong> CLOSED</p>
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