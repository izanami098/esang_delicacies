<?php
/**
 * Customer Notification Preferences API
 * Secure endpoint for managing customer notification preferences using profile hash authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';
require_once '../classes/ProfileHashManager.php';
require_once '../src/notifications/NotificationService.php';

use EsangDelicacies\Notifications\NotificationService;

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Start session for hash-based authentication
    session_start();

    // Check if customer is authenticated via profile hash
    $profileHashManager = new ProfileHashManager();
    $sessionValid = $profileHashManager->validateCurrentSession();

    if (!$sessionValid) {
        $response['message'] = 'Authentication required';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }

    // Get authenticated customer's profile hash
    $profileHash = $_SESSION['customer_profile_hash'] ?? null;
    if (!$profileHash) {
        $response['message'] = 'Invalid session - profile hash missing';
        http_response_code(401);
        echo json_encode($response);
        exit();
    }

    // Initialize notification service
    $notificationService = new NotificationService();

    // Handle different request methods
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Get current notification preferences
        $preferences = $notificationService->getNotificationPreferences($profileHash);
        
        $response['success'] = true;
        $response['data'] = $preferences;
        $response['message'] = 'Notification preferences retrieved successfully';

    } elseif ($method === 'POST' || $method === 'PUT') {
        // Update notification preferences
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $response['message'] = 'Invalid JSON data';
            http_response_code(400);
            echo json_encode($response);
            exit();
        }

        // Define expected preference fields with default values
        $expectedPreferences = [
            'order_status_notifications' => true,
            'payment_notifications' => true,
            'promotional_notifications' => false,
            'email_notifications' => true,
            'push_notifications' => true,
            'sound_enabled' => true
        ];

        // Validate and sanitize input
        $preferences = [];
        foreach ($expectedPreferences as $field => $default) {
            if (isset($input[$field])) {
                $preferences[$field] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($preferences[$field] === null) {
                    $response['message'] = "Invalid value for {$field}. Expected boolean.";
                    http_response_code(400);
                    echo json_encode($response);
                    exit();
                }
            } else {
                $preferences[$field] = $default;
            }
        }

        // Update preferences
        $result = $notificationService->updateNotificationPreferences($profileHash, $preferences);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Notification preferences updated successfully';
            $response['data'] = $preferences;
        } else {
            $response['message'] = 'Failed to update notification preferences';
            http_response_code(500);
        }

    } else {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
    }

} catch (Exception $e) {
    error_log("Notification preferences API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>