<?php
session_start();
require_once 'includes/db.php';

// Update rider status to offline if rider is logging out
if ((isset($_SESSION['role']) && $_SESSION['role'] === 'RIDER' && isset($_SESSION['riderId'])) || 
    (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rider' && isset($_SESSION['rider_id']))) {
    try {
        $riderId = $_SESSION['riderId'] ?? $_SESSION['rider_id'];
        $stmt = $pdo->prepare("UPDATE riders SET status = 'offline' WHERE rider_id = ?");
        $stmt->execute([$riderId]);
    } catch (Exception $e) {
        // Status column might not exist - that's okay
        error_log('Failed to update rider status on logout: ' . $e->getMessage());
    }
}

// Clear session
session_unset();
session_destroy();

// Redirect to login page
header("Location: app/views/auth/LogIn.php?logged_out=1");
exit();
?>
