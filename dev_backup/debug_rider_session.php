<?php
/**
 * Debug script to check session variables for rider authentication
 */

session_start();
require_once 'includes/db.php';

echo "=== Rider Session Debug ===\n";
echo "PHP Session ID: " . session_id() . "\n\n";

echo "=== Session Variables ===\n";
foreach ($_SESSION as $key => $value) {
    echo "$key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}

echo "\n=== Key Authentication Variables ===\n";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "riderId: " . ($_SESSION['riderId'] ?? 'NOT SET') . "\n";
echo "rider_id: " . ($_SESSION['rider_id'] ?? 'NOT SET') . "\n";  
echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "user_type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";

// Check if we can connect to database
echo "\n=== Database Check ===\n";
try {
    require_once 'app/config/database.php';
    $mysqli = Database::getConnection();
    echo "Database connection: SUCCESS\n";
    
    // Check if both tables exist
    $riderTableCheck = $mysqli->query("SHOW TABLES LIKE 'RIDER'");
    $ridersTableCheck = $mysqli->query("SHOW TABLES LIKE 'riders'");
    
    echo "RIDER table exists: " . ($riderTableCheck && $riderTableCheck->num_rows > 0 ? "YES" : "NO") . "\n";
    echo "riders table exists: " . ($ridersTableCheck && $ridersTableCheck->num_rows > 0 ? "YES" : "NO") . "\n";
    
    // If we have a rider ID, check what data exists
    if (isset($_SESSION['riderId'])) {
        $riderId = $_SESSION['riderId'];
        echo "\n=== Rider Data Check for ID: $riderId ===\n";
        
        // Check old RIDER table
        if ($riderTableCheck && $riderTableCheck->num_rows > 0) {
            $stmt = $mysqli->prepare("SELECT empId, name, email, status FROM RIDER WHERE empId = ?");
            if ($stmt) {
                $stmt->bind_param('i', $riderId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    echo "RIDER table data: " . json_encode($row) . "\n";
                } else {
                    echo "RIDER table: No record found for ID $riderId\n";
                }
                $stmt->close();
            }
        }
        
        // Check new riders table
        if ($ridersTableCheck && $ridersTableCheck->num_rows > 0) {
            $stmt = $mysqli->prepare("SELECT rider_id, name, email, status FROM riders WHERE rider_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $riderId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    echo "riders table data: " . json_encode($row) . "\n";
                } else {
                    echo "riders table: No record found for ID $riderId\n";
                }
                $stmt->close();
            }
        }
    }
    
} catch (Exception $e) {
    echo "Database connection: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== Authentication Status Check ===\n";
if (isset($_SESSION['role']) && $_SESSION['role'] === 'RIDER' && isset($_SESSION['riderId'])) {
    echo "Session-based authentication: VALID\n";
} else {
    echo "Session-based authentication: INVALID\n";
    echo "Missing: ";
    if (!isset($_SESSION['role'])) echo "role ";
    if ($_SESSION['role'] ?? '' !== 'RIDER') echo "role_value ";
    if (!isset($_SESSION['riderId'])) echo "riderId ";
    echo "\n";
}

// Check HashBasedAuth if available
echo "\n=== Hash-Based Auth Check ===\n";
try {
    require_once 'includes/HashBasedAuth.php';
    $auth = new HashBasedAuth($pdo ?? null);
    
    if ($auth->isRiderAuthenticated()) {
        echo "Hash-based authentication: VALID\n";
        $rider = $auth->getAuthenticatedRider();
        echo "Hash auth rider data: " . json_encode($rider) . "\n";
    } else {
        echo "Hash-based authentication: INVALID\n";
    }
} catch (Exception $e) {
    echo "Hash-based authentication: ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>