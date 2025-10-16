<?php
session_start();

// Include necessary files for database access
require_once 'app/views/_bootstrap.php';

// Try to use the new ProfileHashManager system for logout logging
try {
    require_once 'app/classes/ProfileHashManager.php';
    
    // Log logout attempt if profile hash session exists
    if (isset($_SESSION['profile_hash']) && isset($_SESSION['session_id'])) {
        $profileHashManager = ProfileHashManager::getInstance();
        
        // Validate session and log logout
        $sessionValid = $profileHashManager->validateSession($_SESSION['session_id'], $_SESSION['profile_hash']);
        if ($sessionValid) {
            // Log the logout using the new ProfileHashManager
            $profileHashManager->logProfileAccess(
                $_SESSION['profile_hash'], 
                $_SESSION['customerId'] ?? 0,
                'LOGOUT',
                [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
            
            // Logout the user session using ProfileHashManager
            $profileHashManager->logoutUserSession($_SESSION['session_id']);
        }
    }
} catch (Exception $e) {
    // If ProfileHashManager fails, just continue with basic logout
    error_log("ProfileHashManager logout error: " . $e->getMessage());
}

// Clear all session data
$_SESSION = array();

// Regenerate session ID for security
session_regenerate_id(true);

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

$_SESSION['flash'] = [
    'type' => 'success',
    'msg'  => 'You have been successfully logged out.'
  ];
// Redirect to login page with logout confirmation
header("Location: app/views/auth/LogIn.php");
exit();
?>