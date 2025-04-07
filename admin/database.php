<?php

$host = "localhost"; 
$user = "root";  
$pass = "";     
$dbname = "psp_ubelt";

// Create a MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check if connection failed
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
?>
