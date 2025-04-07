<style>
    .sidebar ul li.active {
        background-color: #c05600;
        font-weight: bold;
    }
</style>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <ul>
        <li class="<?= ($current_page == 'gymMembers.php') ? 'active' : '' ?>">
            <span class="emoji">👤</span><a href="gymMembers.php">Gym Members</a>
        </li>
        <li class="<?= ($current_page == 'appointments.php') ? 'active' : '' ?>">
            <span class="emoji">📅</span><a href="appointments.php">Appointments</a>
        </li>
        <li class="<?= ($current_page == 'advertisement.php') ? 'active' : '' ?>">
            <span class="emoji">📊</span><a href="advertisement.php">Advertisements</a>
        </li>
        <li class="<?= ($current_page == 'scheduleAdmin.php') ? 'active' : '' ?>">
            <span class="emoji">📆</span><a href="scheduleAdmin.php">Schedule</a>
        </li>
        <li class="<?= ($current_page == 'announcement.php') ? 'active' : '' ?>">
            <span class="emoji">📣</span><a href="announcement.php">Announcement</a>
        </li>
        <li class="<?= ($current_page == 'payment.php') ? 'active' : '' ?>">
            <span class="emoji">💵</span><a href="payment.php">Payments</a>
        </li>
    </ul>
</div>