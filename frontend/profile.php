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

// Updated SQL query to fetch subscription and payment details
$sql = "SELECT 
            u.fullName, 
            u.username, 
            u.emailAddress, 
            u.phoneNumber, 
            u.birthday, 
            u.profile_picture,
            u.membership_status,
            u.date_started,
            u.next_payment,
            u.account_balance,
            MIN(p.payment_date) AS first_subscription_date,
            MAX(p.payment_due) AS next_payment_date
        FROM 
            users u
        LEFT JOIN 
            payments p ON u.id = p.user_id
        WHERE 
            u.id = ?
        GROUP BY 
            u.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result(
    $user_name,
    $user_username,
    $user_email,
    $user_contact,
    $user_birthday,
    $profile_picture,
    $db_membership_status,
    $db_date_started,
    $db_next_payment,
    $account_balance,
    $subscription_date,
    $next_payment
);
$stmt->fetch();
$stmt->close();

// Provide default values if any are NULL
$user_name = $user_name ?? 'N/A';
$user_username = $user_username ?? 'N/A';
$user_email = $user_email ?? 'N/A';
$user_contact = $user_contact ?? 'N/A';
$user_birthday = $user_birthday ?? 'N/A';
$account_balance = $account_balance ?? 0.00;

// Determine date of subscription and next payment
$formatted_subscription_date = $subscription_date 
    ? date('F j, Y', strtotime($subscription_date)) 
    : ($db_date_started && $db_date_started !== '0000-00-00' 
        ? date('F j, Y', strtotime($db_date_started)) 
        : 'No Subscription');

$formatted_next_payment = $next_payment 
    ? date('F j, Y', strtotime($next_payment)) 
    : ($db_next_payment && $db_next_payment !== '0000-00-00' 
        ? date('F j, Y', strtotime($db_next_payment)) 
        : 'No Upcoming Payment');

// Use database membership status, with fallback
$membership_status = $db_membership_status ?? 'Inactive';

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
    <title>Profile - PSP</title>
    <link rel="stylesheet" href="css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/notification.css">
    <link rel="stylesheet" href="css/header.css">
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
                        <p>Loading notifications...</p>
                    </div>
                </div>

                <!-- Logout Button -->
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            <?php else: ?>
                <button class="login-btn" onclick="window.location.href='login.php'">Login</button>
                <button class="signup-btn" onclick="window.location.href='register.php'">Sign Up</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Wrap the main content for flex layout -->
    <div class="profile-container">
        <div class="profile-left">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($profile_picturePath);  ?>" alt="Profile Picture">
                <h2><?php echo htmlspecialchars($user_name); ?></h2>
                <p>@<?php echo htmlspecialchars($user_username); ?></p>
                <p><strong>Email Address:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user_contact); ?></p>
                <p><strong>Birthday:</strong> <?php echo htmlspecialchars($user_birthday); ?></p>
                <button class="edit-profile-btn" onclick="window.location.href='editprofile.php'">
                    <span class="icon">✎</span> EDIT PROFILE
                </button>
            </div>
        </div>

        <!-- Right Side: Billing Section -->
        <div class="billings-container">
            <h3 id="billing-header">Billings <i class="fas fa-chevron-up"></i></h3>
            <div class="billing-box" id="billing-content">
                <h4>Membership Details</h4>
                <div class="billing-info">
                    <div class="billing-left">
                        <p><strong>Date of Subscription:</strong> <?php echo htmlspecialchars($formatted_subscription_date); ?></p>
                        <p><strong>Next Payment:</strong> <?php echo htmlspecialchars($formatted_next_payment); ?></p>
                        <p><strong>Account Balance:</strong> ₱ <?php echo number_format($account_balance, 2); ?></p>
                        <p><strong>Membership Status:</strong> <?php echo htmlspecialchars($membership_status); ?></p>
                    </div>
                </div>
            </div>
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
                })
                .catch(error => console.error("Error fetching notifications:", error));
        }

        function toggleNotificationPopup() {
            const popup = document.getElementById("notificationPopup");
            popup.classList.toggle("show");
        }

        document.getElementById("billing-header").addEventListener("click", function() {
            var content = document.getElementById("billing-content");
            var icon = this.querySelector("i");

            if (content.style.display === "none") {
                content.style.display = "block";
                icon.classList.remove("fa-chevron-up");
                icon.classList.add("fa-chevron-down");
            } else {
                content.style.display = "none";
                icon.classList.remove("fa-chevron-down");
                icon.classList.add("fa-chevron-up");
            }
        });

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
    </script>

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

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title">Edit</h2>
            <input type="text" id="editInput">
            <button onclick="saveChanges()">Save</button>
        </div>
    </div>

</body>

</html>