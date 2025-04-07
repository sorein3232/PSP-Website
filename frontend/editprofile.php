<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to access this page.'); window.location.href='login.php';</script>";
    exit();
}

// Database connection
include("../dbs_connection/database.php");

// Retrieve user details from session
$user_id = $_SESSION['user_id'];

$sql = "SELECT fullName, username, emailAddress, phoneNumber, birthday, 
               date_started, next_payment, membership_status, profile_picture 
        FROM users 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result(
    $username,
    $user_username,
    $user_email,
    $user_contact,
    $user_birthday,
    $subscription_date,
    $next_payment,
    $membership_status,
    $profile_picture
);
$stmt->fetch();
$stmt->close();

// Provide default values if any are NULL
$username = $username ?? 'N/A';
$user_username = $user_username ?? 'N/A';
$user_email = $user_email ?? 'N/A';
$user_contact = $user_contact ?? 'N/A';
$user_birthday = $user_birthday ?? 'N/A';
$subscription_date = $subscription_date ?? 'N/A';
$next_payment = $next_payment ?? 'N/A';
$membership_status = $membership_status ?? 'N/A';

$uploadsDir = "uploads/";
$profile_picturePath = $uploadsDir . $profile_picture;
if (!file_exists($profile_picturePath) || empty($profile_picture)) {
    $profile_picturePath = $uploadsDir . "default.svg";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/editprofile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            <a href="aboutus.php">About Us</a>
            <a href="schedule.php">Schedule</a>
            <a class="active" href="profile.php">Profile</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="notification-container">
                    <button class="notification-btn" onclick="toggleNotificationPopup()">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="notification-popup" id="notificationPopup">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                        </div>
                        <div class="notification-message">
                            <p><strong>Successful payment</strong><br>Dear user, you have successfully paid your membership fee.</p>
                            <small>5 hours ago</small>
                        </div>
                        <button class="see-notifications-btn">See previous notifications</button>
                    </div>
                </div>
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            <?php else: ?>
                <button class="login-btn" onclick="window.location.href='login.php'">Login</button>
                <button class="signup-btn" onclick="window.location.href='register.php'">Sign Up</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Profile Section -->
    <div class="edit-profile-container">
        <div class="edit-profile-left">
            <a href="profile.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <h2>Edit Profile</h2>
            <div class="profile-picture">
                <img src="<?php echo htmlspecialchars($profile_picturePath); ?>" alt="Profile Picture">
            </div>
            <a href="#" class="edit-image" onclick="openModal('profile_picture'); return false;">Edit</a>
        </div>

        <div class="edit-profile-right">
            <h2>Edit Personal Details:</h2>
            <div class="profile-detail">
                <span>Name:</span> <span id="fullName"><?php echo htmlspecialchars($username); ?></span>
                <a href="#" onclick="openModal('fullName'); return false;">Edit</a>
            </div>
            <div class="profile-detail">
                <span>Username:</span> <span id="username">@<?php echo htmlspecialchars($user_username); ?></span>
                <a href="#" onclick="openModal('username'); return false;">Edit</a>
            </div>
            <div class="profile-detail">
                <span>Contact Number:</span> <span id="phoneNumber"><?php echo htmlspecialchars($user_contact); ?></span>
                <a href="#" onclick="openModal('phoneNumber'); return false;">Edit</a>
            </div>
            <div class="profile-detail">
                <span>Email Address:</span> 
                <span id="emailAddress"><?php echo htmlspecialchars($user_email); ?></span>
                <a href="#" onclick="showEmailAlert(); return false;">Edit</a>
            </div>
            <div class="profile-detail">
                <span>Birthday:</span> <span id="birthday"><?php echo htmlspecialchars($user_birthday); ?></span>
                <a href="#" onclick="openModal('birthday'); return false;">Edit</a>
            </div>
            <div class="profile-detail">
                <span>Password:</span> <span id="password">************</span>
                <a href="#" onclick="openModal('password'); return false;">Edit</a>
            </div>
        </div>
    </div>

    <!-- Modal for Edit Profile -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 id="modal-title">Edit Profile</h3>
            <div id="modal-body"></div>
        </div>
    </div>

    <script defer src="editprofile.js"></script>
    <script>

        function showEmailAlert() {
            alert("Your email address cannot be changed or edited.");
        }

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
                    console.log(data);

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

        function openModal(field) {
            var modal = document.getElementById("editModal");
            var inputField = document.getElementById("editInput");
            var title = document.getElementById("modal-title");

            inputField.value = document.getElementById(field).innerText;
            title.innerText = `Edit ${field.charAt(0).toUpperCase() + field.slice(1)}`;
            modal.style.display = "block";
            inputField.setAttribute('data-field', field);
        }

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
        }

        function saveEdit() {
            var inputValue = document.getElementById("editInput").value;
            var field = document.getElementById("editInput").getAttribute('data-field');
            document.getElementById(field).innerText = inputValue;
            closeModal();
        }
    </script>
</body>

</html>