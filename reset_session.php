<?php
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Reset</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { color: blue; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Session Reset</h1>";

echo "<div class='info'><h2>Current Session Data:</h2>";
if (empty($_SESSION)) {
    echo "<p>No session data found.</p>";
} else {
    echo "<pre>";
    foreach ($_SESSION as $key => $value) {
        echo "$key: " . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "\n";
    }
    echo "</pre>";
}
echo "</div>";

// Clear session
session_unset();
session_destroy();

echo "<div class='success'><p>Session cleared successfully!</p>";
echo "<p><a href='app/views/auth/LogIn.php'>Go to Login Page</a></p>";
echo "<p><a href='test_rider_auth.php'>Go to Auth Test Page</a></p>";
echo "</div>";

echo "</body></html>";
?>