<?php
session_start();
require_once 'app/config/database.php';
require_once 'app/auth/HashBasedAuth.php';
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Rider Auth Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
        .error { color: red; }
        .success { color: green; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Rider Authentication Test</h1>";

echo "<div class='section'>
        <h2>Current Session Status</h2>";

if (empty($_SESSION)) {
    echo "<p class='error'>No active session found. Please log in first.</p>";
    echo "<p><a href='app/views/auth/LogIn.php'>Go to Login Page</a></p>";
} else {
    echo "<p class='info'>Active session found!</p>";
    echo "<pre>";
    foreach ($_SESSION as $key => $value) {
        echo "$key: " . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "\n";
    }
    echo "</pre>";
}

echo "</div>";

// Test the authentication logic from our rider pages
echo "<div class='section'>
        <h2>Testing Authentication Logic</h2>";

try {
    $auth = new HashBasedAuth($pdo);
    $mysqli = Database::getConnection();
    
    $riderName = 'Default Rider';
    $riderId = null;
    $rider = null;
    
    // Check if rider is authenticated using hash-based auth
    if (!$auth->isRiderAuthenticated()) {
        echo "<p class='info'>Hash-based auth: NOT authenticated</p>";
        
        // Fallback to session-based auth
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'RIDER' || !isset($_SESSION['riderId'])) {
            echo "<p class='error'>Session-based auth: NOT authenticated</p>";
            echo "<p>Missing: ";
            if (!isset($_SESSION['role'])) echo "role ";
            if (($_SESSION['role'] ?? '') !== 'RIDER') echo "role_value ";  
            if (!isset($_SESSION['riderId'])) echo "riderId ";
            echo "</p>";
        } else {
            echo "<p class='success'>Session-based auth: AUTHENTICATED</p>";
            
            $riderId = $_SESSION['riderId'];
            $riderName = $_SESSION['user_name'] ?? 'Rider';
            $rider = null;
            
            echo "<p>Initial rider name from session: <strong>" . htmlspecialchars($riderName) . "</strong></p>";
            
            // Try new riders table first, then fallback to old RIDER table
            $stmt = $mysqli->prepare("SELECT name, email, phone, status, rating, total_deliveries FROM riders WHERE rider_id = ?");
            $stmt->bind_param('i', $riderId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $rider = $row;
                $riderName = $row['name'] ?? $riderName;
                echo "<p class='success'>Found in 'riders' table: <strong>" . htmlspecialchars($riderName) . "</strong></p>";
                echo "<p>Rider data: " . htmlspecialchars(json_encode($row)) . "</p>";
            } else {
                echo "<p class='info'>Not found in 'riders' table, trying 'RIDER' table...</p>";
                
                // Fallback to old RIDER table if new riders table doesn't have the record
                $stmt2 = $mysqli->prepare("SELECT name, email, phone, status FROM RIDER WHERE empId = ?");
                if ($stmt2) {
                    $stmt2->bind_param('i', $riderId);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row2 = $result2->fetch_assoc()) {
                        $rider = $row2;
                        $riderName = $row2['name'] ?? $riderName;
                        echo "<p class='success'>Found in 'RIDER' table: <strong>" . htmlspecialchars($riderName) . "</strong></p>";
                        echo "<p>Rider data: " . htmlspecialchars(json_encode($row2)) . "</p>";
                        // Set defaults for new fields that don't exist in old table
                        $rider['rating'] = 5.0;
                        $rider['total_deliveries'] = 0;
                    } else {
                        echo "<p class='error'>Rider ID $riderId not found in either table!</p>";
                    }
                    $stmt2->close();
                } else {
                    echo "<p class='error'>Could not prepare query for 'RIDER' table</p>";
                }
            }
            $stmt->close();
            
            echo "<p><strong>Final rider name: " . htmlspecialchars($riderName) . "</strong></p>";
        }
    } else {
        echo "<p class='success'>Hash-based auth: AUTHENTICATED</p>";
        $rider = $auth->getAuthenticatedRider();
        $riderId = $rider['rider_id'];
        $riderName = $rider['name'];
        echo "<p><strong>Rider name from hash auth: " . htmlspecialchars($riderName) . "</strong></p>";
        echo "<p>Rider data: " . htmlspecialchars(json_encode($rider)) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $riderName = 'Error';
}

echo "</div>";

echo "<div class='section'>
        <h2>Test Results</h2>
        <p><strong>Rider Name for Display: " . htmlspecialchars($riderName) . "</strong></p>
        <p>Rider ID: " . ($riderId ?? 'Not set') . "</p>
        <p>Has rider data: " . ($rider ? 'Yes' : 'No') . "</p>";

if ($_SESSION) {
    echo "<p><a href='app/views/rider/order_assignments.php'>Go to Order Assignments</a></p>";
    echo "<p><a href='app/views/rider/order_status.php'>Go to Order Status</a></p>";
    echo "<p><a href='app/views/rider/rider_profile.php'>Go to Rider Profile</a></p>";
}

echo "</div>";

echo "</body></html>";
?>