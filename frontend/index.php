<?php
session_start();
include '../dbs_connection/database.php'; // Ensure database connection
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSP Ubelt</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/notification.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        footer {
            clear: both;
            position: relative;
            height: 230px;
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
            <a class="active" href="index.php">Home</a>
            <a href="appointment.php">Appointment</a>
            <a href="aboutus.php">About Us</a>
            <a href="schedule.php">Schedule</a>
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
    </div>
    </div>

    <div class="body text-center">
        <h2>WELCOME TO PSP</h2>
        <h1>PHILIPPINE SPORTS PERFORMANCE</h1>
        <h1>UBELT MANILA</h1>
        <h2>WHERE YOUR FITNESS GOALS COME ALIVE! JOIN US AND LET US ACCOMPANY YOU TO DEVELOP A BRIGHTER FUTURE AHEAD.</h2>
    </div>

    <div class="advertisements">
        <?php
        $query = "SELECT title, image, description FROM advertisements ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='advertisement-item'>";
                echo "<img src='../admin/" . htmlspecialchars($row['image']) . "' alt='Advertisement Image' class='ad-image'>";
                echo "<div class='ad-content'>";
                echo "<h3 class='ad-title'>" . htmlspecialchars($row['title']) . "</h3>";
                echo "<p class='ad-description'>" . htmlspecialchars($row['description']) . "</p>";                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p class='no-ads'>No advertisements available.</p>";
        }
        ?>
    </div>


    <!-- Bootstrap Carousel for Announcements -->
    <div id="announcementCarousel" class="carousel slide mb-0" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            $announcements = mysqli_query($conn, "SELECT image_path FROM announcements ORDER BY created_at DESC");
            $active = true;
            while ($announcement = mysqli_fetch_assoc($announcements)) {
                $imagePath = "../admin/" . htmlspecialchars($announcement['image_path']);
                echo "<div class='carousel-item" . ($active ? " active" : "") . "'>";
                echo "<img src='$imagePath' class='d-block' alt='Announcement Image'>";
                echo "</div>";
                $active = false;
            }

            ?>

        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <footer class="footer">
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
    </footer>

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