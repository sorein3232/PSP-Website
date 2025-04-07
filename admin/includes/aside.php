<style>
    .active-link {
        background-color: rgb(150, 100, 8) !important;
    }

    /* Make nav links white */
    .nav-link {
        color: white !important;
    }

    /* Hover effect */
    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.3) !important;
    }

    /* Sidebar text color */
    .main-sidebar {
        color: white !important;
    }
</style>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="main-sidebar sidebar-dark-primary text-white elevation-4" style="background-color: #ffcd00;">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
        <img src="../frontend/resources/psp-logo.jpg" alt="PSP LOGO" class="brand-image img-circle elevation-3"
            style="opacity: .8">
        <span class="brand-text font-weight-light" style="font-size: 12px;">PHILIPPINE SPORTS PERFORMANCE</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item py-1">
                    <a href="gymMembers.php" class="nav-link <?= ($current_page == 'gymMembers.php') ? 'active-link' : '' ?>">
                        <span class="emoji">👤</span>
                        <p>Gym Members</p>
                    </a>
                </li>

                <!-- Appointments -->
                <li class="nav-item py-1">
                    <a href="appointments.php" class="nav-link <?= ($current_page == 'appointments.php') ? 'active-link' : '' ?>">
                        <span class="emoji">📅</span>
                        <p>Appointments</p>
                    </a>
                </li>

                <!-- Advertisements -->
                <li class="nav-item py-1">
                    <a href="advertisement.php" class="nav-link <?= ($current_page == 'advertisement.php') ? 'active-link' : '' ?>">
                        <span class="emoji">📊</span>
                        <p>Advertisements</p>
                    </a>
                </li>

                <!-- Schedule -->
                <li class="nav-item py-1">
                    <a href="scheduleAdmin.php" class="nav-link <?= ($current_page == 'scheduleAdmin.php') ? 'active-link' : '' ?>">
                        <span class="emoji">📆</span>
                        <p>Schedule</p>
                    </a>
                </li>

                <!-- Announcement -->
                <li class="nav-item py-1">
                    <a href="announcement.php" class="nav-link <?= ($current_page == 'announcement.php') ? 'active-link' : '' ?>">
                        <span class="emoji">📣</span>
                        <p>Announcement</p>
                    </a>
                </li>

                <!-- Payments -->
                <li class="nav-item py-1">
                    <a href="payment.php" class="nav-link <?= ($current_page == 'payment.php') ? 'active-link' : '' ?>">
                        <span class="emoji">💵</span>
                        <p>Payments</p>
                    </a>
                </li>

                <li class="nav-item py-1">
                    <a href="?logout=true" class="nav-link btn btn-danger text-white">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>