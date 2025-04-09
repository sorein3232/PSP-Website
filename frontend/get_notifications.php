<?php
include '../dbs_connection/database.php';

header('Content-Type: application/json');

// 1. FETCH RECENT NOTIFICATIONS
$sql = "
    SELECT * FROM (
        (SELECT 'Advertisement' AS type, title AS message, created_at, id FROM advertisements 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_active = 1)
        UNION ALL
        (SELECT 'Announcement' AS type, title AS message, created_at, id FROM announcements 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
        UNION ALL
        (SELECT 'Schedule' AS type, activity_description AS message, created_at, id FROM schedule 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR))
    ) AS recent_notifications
    ORDER BY created_at DESC 
    LIMIT 10";

$result = $conn->query($sql);

$notifications = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'type' => $row['type'],
            'message' => $row['message'],
            'time' => timeAgo($row['created_at'])
        ];
    }
}

// 2. OPTIONAL: CLEANUP OLD ADVERTISEMENTS
// This makes advertisements that are older than 24 hours inactive
// Note: Only need to do this for advertisements since they have an 'is_active' flag
$cleanup_sql = "
    UPDATE advertisements 
    SET is_active = 0 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_active = 1";

$conn->query($cleanup_sql);

echo json_encode($notifications);

// Function to calculate "time ago"
function timeAgo($timestamp)
{
    // Convert timestamp to Unix time
    $time_ago = strtotime($timestamp);

    // Check if timestamp is valid
    if (!$time_ago) {
        return "Invalid time";
    }

    $current_time = time();
    $time_difference = $current_time - $time_ago;

    // Prevent negative values (future timestamps)
    if ($time_difference < 1) {
        return "Just now";
    }

    $seconds = $time_difference;
    $minutes = floor($seconds / 60);
    $hours = floor($seconds / 3600);
    $days = floor($seconds / 86400);
    $weeks = floor($days / 7);
    $months = floor($days / 30.44); // More accurate month calculation
    $years = floor($days / 365.25); // Account for leap years

    if ($seconds < 60) {
        return "$seconds second" . ($seconds != 1 ? "s" : "") . " ago";
    } elseif ($minutes < 60) {
        return "$minutes minute" . ($minutes != 1 ? "s" : "") . " ago";
    } elseif ($hours < 24) {
        return "$hours hour" . ($hours != 1 ? "s" : "") . " ago";
    } elseif ($days < 7) {
        return "$days day" . ($days != 1 ? "s" : "") . " ago";
    } elseif ($weeks < 4) {
        return "$weeks week" . ($weeks != 1 ? "s" : "") . " ago";
    } elseif ($months < 12) {
        return "$months month" . ($months != 1 ? "s" : "") . " ago";
    } else {
        return "$years year" . ($years != 1 ? "s" : "") . " ago";
    }
}