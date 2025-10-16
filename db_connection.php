<?php
$host = "localhost";             // Always localhost for Truehost
$user = "esangdel_app";          // As shown in your screenshot
$pass = "9H;.?zz7NeX(}qn6";      // Your database password
$db   = "esangdel_esang_db";     // As shown in your screenshot

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset for proper UTF-8 support
$conn->set_charset("utf8mb4");
?>
