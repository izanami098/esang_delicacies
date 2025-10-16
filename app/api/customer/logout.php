<?php
/**
 * Customer Logout API - Profile Hash Authenticated
 */

require_once 'BaseAPI.php';

class LogoutAPI extends BaseAPI {
    
    public function handleRequest() {
        try {
            // Logout the user session using ProfileHashManager
            $logoutSuccess = $this->profileHashManager->logoutUserSession($this->sessionId);
            
            if ($logoutSuccess) {
                // Clear PHP session variables
                session_start();
                session_unset();
                session_destroy();
                
                // Log the logout activity
                $this->profileHashManager->logProfileAccess(
                    $this->profileHash, 
                    $this->customer['customerId'], 
                    'API_LOGOUT',
                    ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null]
                );
                
                $this->sendSuccess([], 'Logged out successfully');
            } else {
                $this->sendError('Logout failed - session may have already expired');
            }
            
        } catch (Exception $e) {
            error_log("Logout API Error: " . $e->getMessage());
            $this->sendError('Failed to logout');
        }
    }
}

// Initialize and handle request
$api = new LogoutAPI();
$api->handleRequest();
?>