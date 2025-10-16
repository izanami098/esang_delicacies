<?php
/**
 * Customer Notifications API
 * Secure endpoint for fetching customer notifications using profile hash authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
        // Get query parameters
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $unreadOnly = isset($_GET['unread_only']) ? filter_var($_GET['unread_only'], FILTER_VALIDATE_BOOLEAN) : false;
        $count = isset($_GET['count']) ? filter_var($_GET['count'], FILTER_VALIDATE_BOOLEAN) : false;

        // Validate parameters
        $limit = max(1, min(100, $limit)); // Between 1 and 100
        $offset = max(0, $offset);

        if ($count) {
            // Return unread count only
            $unreadCount = $notificationService->getUnreadNotificationCount($profileHash);
            $response['success'] = true;
            $response['data'] = ['unread_count' => $unreadCount];
        } else {
            // Get notifications
            $notifications = $notificationService->getCustomerNotifications($profileHash, $limit, $offset, $unreadOnly);
            $unreadCount = $notificationService->getUnreadNotificationCount($profileHash);
            
            $response['success'] = true;
            $response['data'] = [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'limit' => $limit,
                'offset' => $offset,
                'unread_only' => $unreadOnly
            ];
        }

    } elseif ($method === 'POST') {
        // Handle POST requests for marking notifications as read
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['action'])) {
            $response['message'] = 'Missing action parameter';
            http_response_code(400);
            echo json_encode($response);
            exit();
        }

        switch ($input['action']) {
            case 'poll':
                // Handle polling requests for new notifications
                $lastNotificationId = isset($input['last_notification_id']) ? intval($input['last_notification_id']) : 0;
                $userId = isset($input['user_id']) ? intval($input['user_id']) : null;
                
                // Get new notifications since last poll
                $notifications = $notificationService->getNewNotifications($profileHash, $lastNotificationId);
                
                $response['success'] = true;
                $response['notifications'] = $notifications;
                $response['data'] = [
                    'last_notification_id' => $lastNotificationId,
                    'new_notifications_count' => count($notifications)
                ];
                break;
                
            case 'mark_read':
                if (!isset($input['notification_id'])) {
                    $response['message'] = 'Missing notification_id parameter';
                    http_response_code(400);
                } else {
                    $result = $notificationService->markNotificationAsRead(intval($input['notification_id']), $profileHash);
                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Notification marked as read';
                        $response['data'] = ['notification_id' => intval($input['notification_id'])];
                    } else {
                        $response['message'] = 'Notification not found or already read';
                        http_response_code(404);
                    }
                }
                break;

            case 'mark_all_read':
                $count = $notificationService->markAllNotificationsAsRead($profileHash);
                $response['success'] = true;
                $response['message'] = "Marked {$count} notifications as read";
                $response['data'] = ['marked_count' => $count];
                break;

            default:
                $response['message'] = 'Invalid action';
                http_response_code(400);
        }

    } else {
        $response['message'] = 'Method not allowed';
        http_response_code(405);
    }

} catch (Exception $e) {
    error_log("Notifications API error: " . $e->getMessage());
    $response['message'] = 'Internal server error';
    http_response_code(500);
}

echo json_encode($response);
?>