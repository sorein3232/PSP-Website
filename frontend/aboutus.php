<?php
session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/aboutus.css">
    <link rel="stylesheet" href="../frontend/css/header.css">
    <link rel="stylesheet" href="../frontend/css/notification.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>
    <!-- Header Section -->
    <div class="header">
        <div class="header-left">
            <img src="../frontend/resources/psp-logo.jpg" alt="Logo" class="logo-image" />
            <a href="index.php" class="logo-text">Philippine Sports Performance</a>
        </div>
        <div class="header-right">
            <a href="index.php">Home</a>
            <a href="appointment.php">Appointment</a>
            <a class="active" href="aboutus.php">About Us</a>
            <a href="schedule.php">Schedule</a>
            <a href="profile.php">Profile</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Notification Button and Popup in About Us page -->
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
    </div>
    </div>

    <!-- Content Section -->
    <div class="content">
        <div class="background-overlay"></div>
        <div class="card">
            <img src="../frontend/resources/mission.png" alt="Mission Icon" class="icon">
            <h2 class="title">MISSION</h2>
            <p class="description">"To build a strong and resilient community of Filipino athletes who embody excellence, discipline, and integrity, aiming to bring honor to the Philippines on the global stage through outstanding sports performances."</p>
        </div>
        <div class="card">
            <img src="../frontend/resources/vision.png" alt="Vision Icon" class="icon">
            <h2 class="title">VISION</h2>
            <p class="description">"Our mission is to promote health, wellness, and athletic excellence by providing accessible opportunities for sports, fitness classes, and exercise programs to all Filipinos. We strive to foster inclusivity, discipline, and a culture of active living, inspiring national pride and unity."</p>
        </div>
    </div>

    <!-- Glimpse Section -->
    <div class="glimpse-section">
        <h1 class="glimpse-title">GLIMPSE OF PSP</h1>
        <div class="glimpse-container">
        <div class="glimpse-item">
                <img src="../frontend/resources/building-site.jpeg" alt="PSP's Building Site" class="glimpse-image">
                <p class="glimpse-caption">PSP'S BUILDING SITE</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/admindesk.jpg" alt="PSP's Building Site" class="glimpse-image">
                <p class="glimpse-caption">ADMIN DESK</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/fitness-area1.jpeg" alt="Fitness Area" class="glimpse-image">
                <p class="glimpse-caption">FITNESS AREA</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/activityarea.jpg" alt="PSP's Building Site" class="glimpse-image">
                <p class="glimpse-caption">ACTIVITY AREA</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/fitness-area.jpeg" alt="Fitness Area and Commercial Space" class="glimpse-image">
                <p class="glimpse-caption">AMENITIES</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/showerarea.jpg" alt="Amenities" class="glimpse-image">
                <p class="glimpse-caption">SHOWER ROOM</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/locker.jpg" alt="PSP's Building Site" class="glimpse-image">
                <p class="glimpse-caption">LOCKER ROOM</p>
            </div>
            <div class="glimpse-item">
                <img src="../frontend/resources/entrance.jpeg" alt="Entrance" class="glimpse-image">
                <p class="glimpse-caption">ENTRANCE</p>
            </div>
        </div>
    </div>

    <section class="personnel-section">
    <h2 class="personnel-title">Our Team</h2>
    <div class="personnel-grid">
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/club-general.png" alt="Joanna Marie Dela Cruz" class="personnel-photo">
            </div>
            <div class="personnel-name">Joanna Marie Dela Cruz</div>
            <div class="personnel-role">Club General Manager</div>
        </div>
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/club-admin.jpg" alt="Mary Joy Taguinod" class="personnel-photo">
            </div>
            <div class="personnel-name">Mary Joy Taguinod</div>
            <div class="personnel-role">Club Admin</div>
        </div>
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/membership-consultant.png" alt="Gino Paulo Andrade" class="personnel-photo">
            </div>
            <div class="personnel-name">Gino Paulo Andrade</div>
            <div class="personnel-role">Membership Consultant</div>
        </div>
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/personal-trainer.jpg" alt="Mark Anthony De Paz" class="personnel-photo">
            </div>
            <div class="personnel-name">Mark Anthony De Paz</div>
            <div class="personnel-role">Personal Trainer</div>
        </div>
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/personal-trainer1.jpg" alt="Nikko Garrido" class="personnel-photo">
            </div>
            <div class="personnel-name">Nikko Garrido</div>
            <div class="personnel-role">Personal Trainer</div>
        </div>
        <div class="personnel-card">
            <div class="personnel-photo-wrapper">
                <img src="../frontend/resources/personal-trainer2.jpg" alt="Jayson Rafol" class="personnel-photo">
            </div>
            <div class="personnel-name">Jayson Rafol</div>
            <div class="personnel-role">Personal Trainer</div>
        </div>
    </div>
</section>

    <!-- Footer Section -->
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