<?php
/**
 * Rider Notifications API
 * Handles rider notifications, order assignments, and real-time updates
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/HashBasedAuth.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new HashBasedAuth($pdo);

// Verify rider authentication
if (!$auth->isRiderAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$rider = $auth->getAuthenticatedRider();
if (!$rider) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Rider not found']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $rider);
            break;
        case 'POST':
            handlePostRequest($action, $rider);
            break;
        case 'PUT':
            handlePutRequest($action, $rider);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Rider Notifications API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $rider) {
    switch ($action) {
        case 'all':
            getAllNotifications($rider);
            break;
        case 'unread':
            getUnreadNotifications($rider);
            break;
        case 'count':
            getNotificationCount($rider);
            break;
        case 'poll':
            pollForUpdates($rider);
            break;
        default:
            getAllNotifications($rider);
    }
}

function handlePostRequest($action, $rider) {
    switch ($action) {
        case 'mark_read':
            markNotificationAsRead($rider);
            break;
        case 'mark_all_read':
            markAllNotificationsAsRead($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $rider) {
    switch ($action) {
        case 'preferences':
            updateNotificationPreferences($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Get all notifications for the rider
 */
function getAllNotifications($rider) {
    global $pdo;
    
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    $type = $_GET['type'] ?? null;
    
    try {
        $sql = "
            SELECT notification_id, title, message, type, related_order_id, 
                   is_read, created_at, read_at
            FROM notifications 
            WHERE recipient_id = ? AND recipient_type = 'rider'
        ";
        $params = [$rider['rider_id']];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order details for order-related notifications
        foreach ($notifications as &$notification) {
            if ($notification['related_order_id']) {
                $orderStmt = $pdo->prepare("
                    SELECT order_id, customer_name, delivery_address, total_amount 
                    FROM orders WHERE order_id = ?
                ");
                $orderStmt->execute([$notification['related_order_id']]);
                $notification['order_details'] = $orderStmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
        
    } catch (Exception $e) {
        error_log("Get all notifications error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve notifications']);
    }
}

/**
 * Get unread notifications
 */
function getUnreadNotifications($rider) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT notification_id, title, message, type, related_order_id, created_at
            FROM notifications 
            WHERE recipient_id = ? AND recipient_type = 'rider' AND is_read = FALSE
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$rider['rider_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order details for order-related notifications
        foreach ($notifications as &$notification) {
            if ($notification['related_order_id']) {
                $orderStmt = $pdo->prepare("
                    SELECT order_id, customer_name, delivery_address, total_amount 
                    FROM orders WHERE order_id = ?
                ");
                $orderStmt->execute([$notification['related_order_id']]);
                $notification['order_details'] = $orderStmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
        
    } catch (Exception $e) {
        error_log("Get unread notifications error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve unread notifications']);
    }
}

/**
 * Get notification count (total and unread)
 */
function getNotificationCount($rider) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_count,
                COUNT(CASE WHEN is_read = FALSE THEN 1 END) as unread_count
            FROM notifications 
            WHERE recipient_id = ? AND recipient_type = 'rider'
        ");
        $stmt->execute([$rider['rider_id']]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'counts' => $counts
        ]);
        
    } catch (Exception $e) {
        error_log("Get notification count error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to get notification count']);
    }
}

/**
 * Poll for new notifications and order updates
 */
function pollForUpdates($rider) {
    global $pdo;
    
    $lastPoll = $_GET['last_poll'] ?? date('Y-m-d H:i:s', time() - 30); // Default to 30 seconds ago
    
    try {
        // Get new notifications since last poll
        $stmt = $pdo->prepare("
            SELECT notification_id, title, message, type, related_order_id, created_at
            FROM notifications 
            WHERE recipient_id = ? AND recipient_type = 'rider' 
            AND created_at > ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$rider['rider_id'], $lastPoll]);
        $newNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get order status updates for active orders
        $stmt = $pdo->prepare("
            SELECT order_id, order_status, customer_name, delivery_address, updated_at
            FROM orders 
            WHERE rider_id = ? AND order_status IN ('on_delivery', 'ready_for_delivery')
            AND updated_at > ?
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$rider['rider_id'], $lastPoll]);
        $orderUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get new available orders if rider is active
        $newOrders = [];
        if ($rider['status'] === 'active') {
            $stmt = $pdo->prepare("
                SELECT o.order_id, o.customer_name, o.delivery_address, o.total_amount, o.delivery_fee, o.created_at
                FROM orders o
                WHERE o.order_status = 'ready_for_delivery' 
                AND o.rider_id IS NULL
                AND o.created_at > ?
                ORDER BY o.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$lastPoll]);
            $newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'poll_time' => date('Y-m-d H:i:s'),
            'new_notifications' => $newNotifications,
            'order_updates' => $orderUpdates,
            'new_orders' => $newOrders,
            'counts' => [
                'new_notifications' => count($newNotifications),
                'order_updates' => count($orderUpdates),
                'new_orders' => count($newOrders)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Poll for updates error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to poll for updates']);
    }
}

/**
 * Mark a specific notification as read
 */
function markNotificationAsRead($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = $input['notification_id'] ?? null;
    
    if (!$notificationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        return;
    }
    
    try {
        // Verify notification belongs to this rider
        $stmt = $pdo->prepare("
            SELECT notification_id FROM notifications 
            WHERE notification_id = ? AND recipient_id = ? AND recipient_type = 'rider'
        ");
        $stmt->execute([$notificationId, $rider['rider_id']]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
            return;
        }
        
        // Mark as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE, read_at = NOW() 
            WHERE notification_id = ?
        ");
        $stmt->execute([$notificationId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
        
    } catch (Exception $e) {
        error_log("Mark notification as read error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($rider) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE, read_at = NOW() 
            WHERE recipient_id = ? AND recipient_type = 'rider' AND is_read = FALSE
        ");
        $stmt->execute([$rider['rider_id']]);
        
        $affectedRows = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'marked_count' => $affectedRows
        ]);
        
    } catch (Exception $e) {
        error_log("Mark all notifications as read error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
    }
}

/**
 * Update notification preferences
 */
function updateNotificationPreferences($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    try {
        // Check if preferences exist
        $stmt = $pdo->prepare("SELECT rider_id FROM notification_preferences WHERE rider_id = ?");
        $stmt->execute([$rider['rider_id']]);
        $exists = $stmt->fetch();
        
        $preferences = [
            'order_assignments' => $input['order_assignments'] ?? true,
            'status_updates' => $input['status_updates'] ?? true,
            'earnings_reports' => $input['earnings_reports'] ?? true,
            'system_notifications' => $input['system_notifications'] ?? true,
            'email_notifications' => $input['email_notifications'] ?? false,
            'push_notifications' => $input['push_notifications'] ?? true
        ];
        
        if ($exists) {
            // Update existing preferences
            $stmt = $pdo->prepare("
                UPDATE notification_preferences 
                SET preferences = ?, updated_at = NOW()
                WHERE rider_id = ?
            ");
            $stmt->execute([json_encode($preferences), $rider['rider_id']]);
        } else {
            // Insert new preferences
            $stmt = $pdo->prepare("
                INSERT INTO notification_preferences (rider_id, preferences, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
            ");
            $stmt->execute([$rider['rider_id'], json_encode($preferences)]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'preferences' => $preferences
        ]);
        
    } catch (Exception $e) {
        error_log("Update notification preferences error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update notification preferences']);
    }
}

/**
 * Create a new notification for the rider
 * This function is used internally by other parts of the system
 */
function createNotification($riderId, $title, $message, $type = 'system', $relatedOrderId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_id, recipient_type, title, message, type, related_order_id, created_at)
            VALUES (?, 'rider', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$riderId, $title, $message, $type, $relatedOrderId]);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order assignment notification
 */
function notifyOrderAssignment($riderId, $orderId, $customerName, $deliveryAddress) {
    return createNotification(
        $riderId,
        'New Order Assignment',
        "You have been assigned order #{$orderId} for {$customerName} at {$deliveryAddress}",
        'assignment',
        $orderId
    );
}

/**
 * Send earnings notification
 */
function notifyEarnings($riderId, $amount, $orderId) {
    return createNotification(
        $riderId,
        'Delivery Completed',
        "You earned ₱{$amount} from order #{$orderId}. Keep up the great work!",
        'delivery',
        $orderId
    );
}
?>