<?php
/**
 * Rider Orders API
 * Handles order assignment, acceptance, and status updates
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
    error_log("Rider API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $rider) {
    global $pdo;
    
    switch ($action) {
        case 'available_orders':
            getAvailableOrders($rider);
            break;
        case 'my_orders':
            getMyOrders($rider);
            break;
        case 'order_details':
            getOrderDetails($rider);
            break;
        case 'stats':
            getRiderStats($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePostRequest($action, $rider) {
    global $pdo;
    
    switch ($action) {
        case 'accept_order':
            acceptOrder($rider);
            break;
        case 'update_location':
            updateRiderLocation($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $rider) {
    global $pdo;
    
    switch ($action) {
        case 'update_status':
            updateOrderStatus($rider);
            break;
        case 'update_rider_status':
            updateRiderStatus($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Get available orders for delivery
 */
function getAvailableOrders($rider) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.order_date, o.pickup_address, o.delivery_address,
               o.customer_name, o.customer_phone, o.total_amount, o.delivery_fee,
               o.special_instructions, o.estimated_delivery_time,
               c.name as customer_name_full, c.phone as customer_phone_full
        FROM orders o
        JOIN customers c ON o.customerId = c.customer_id
        WHERE o.order_status = 'ready_for_delivery' 
        AND o.rider_id IS NULL
        ORDER BY o.order_date ASC
        LIMIT 20
    ");
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add order items for each order
    foreach ($orders as &$order) {
        $itemsStmt = $pdo->prepare("
            SELECT item_name, quantity, unit_price, subtotal
            FROM order_items
            WHERE order_id = ?
        ");
        $itemsStmt->execute([$order['order_id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'count' => count($orders)
    ]);
}

/**
 * Get rider's assigned orders
 */
function getMyOrders($rider) {
    global $pdo;
    
    $status = $_GET['status'] ?? null;
    
    $sql = "
        SELECT o.order_id, o.order_date, o.pickup_address, o.delivery_address,
               o.customer_name, o.customer_phone, o.total_amount, o.delivery_fee,
               o.order_status, o.payment_status, o.special_instructions,
               o.estimated_delivery_time, o.actual_delivery_time,
               c.name as customer_name_full, c.phone as customer_phone_full
        FROM orders o
        JOIN customers c ON o.customerId = c.customer_id
        WHERE o.rider_id = ?
    ";
    
    $params = [$rider['rider_id']];
    
    if ($status) {
        $sql .= " AND o.order_status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY o.order_date DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add order items for each order
    foreach ($orders as &$order) {
        $itemsStmt = $pdo->prepare("
            SELECT item_name, quantity, unit_price, subtotal
            FROM order_items
            WHERE order_id = ?
        ");
        $itemsStmt->execute([$order['order_id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'count' => count($orders)
    ]);
}

/**
 * Get specific order details
 */
function getOrderDetails($rider) {
    global $pdo;
    
    $orderId = $_GET['order_id'] ?? null;
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.*, c.name as customer_name_full, c.phone as customer_phone_full,
               c.email as customer_email, c.address as customer_address
        FROM orders o
        JOIN customers c ON o.customerId = c.customer_id
        WHERE o.order_id = ? AND (o.rider_id = ? OR o.rider_id IS NULL)
    ");
    
    $stmt->execute([$orderId, $rider['rider_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT item_name, quantity, unit_price, subtotal
        FROM order_items
        WHERE order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
}

/**
 * Get rider statistics
 */
function getRiderStats($rider) {
    global $pdo;
    
    $stats = [];
    
    // Total deliveries
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_deliveries,
               COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as completed_deliveries,
               COUNT(CASE WHEN order_status = 'on_delivery' THEN 1 END) as active_deliveries,
               COALESCE(SUM(delivery_fee), 0) as total_earnings
        FROM orders 
        WHERE rider_id = ?
    ");
    $stmt->execute([$rider['rider_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Today's stats
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as today_deliveries,
               COALESCE(SUM(delivery_fee), 0) as today_earnings
        FROM orders 
        WHERE rider_id = ? AND DATE(order_date) = CURDATE()
    ");
    $stmt->execute([$rider['rider_id']]);
    $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats = array_merge($stats, $todayStats);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

/**
 * Accept an order for delivery
 */
function acceptOrder($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Check if order is still available
        $stmt = $pdo->prepare("
            SELECT order_id, order_status 
            FROM orders 
            WHERE order_id = ? AND order_status = 'ready_for_delivery' AND rider_id IS NULL
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception('Order not available or already assigned');
        }
        
        // Assign order to rider
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET rider_id = ?, order_status = 'on_delivery' 
            WHERE order_id = ?
        ");
        $stmt->execute([$rider['rider_id'], $orderId]);
        
        // Update rider status to busy
        $stmt = $pdo->prepare("UPDATE riders SET status = 'busy' WHERE rider_id = ?");
        $stmt->execute([$rider['rider_id']]);
        
        // Log assignment
        $stmt = $pdo->prepare("
            INSERT INTO order_assignments (order_id, rider_id, status, accepted_at)
            VALUES (?, ?, 'accepted', NOW())
        ");
        $stmt->execute([$orderId, $rider['rider_id']]);
        
        // Create notification for customer
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_id, recipient_type, title, message, type, related_order_id)
            SELECT customerId, 'customer', 'Order On The Way', 
                   CONCAT('Your order #', ?, ' is now on the way! Rider: ', ?),
                   'delivery', ?
            FROM orders WHERE order_id = ?
        ");
        $stmt->execute([$orderId, $rider['name'], $orderId, $orderId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order accepted successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Accept order error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Update order status
 */
function updateOrderStatus($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$orderId || !$status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID and status required']);
        return;
    }
    
    $allowedStatuses = ['on_delivery', 'delivered'];
    if (!in_array($status, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    try {
        // Verify rider owns this order
        $stmt = $pdo->prepare("
            SELECT order_id FROM orders 
            WHERE order_id = ? AND rider_id = ?
        ");
        $stmt->execute([$orderId, $rider['rider_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Update order status
        if ($status === 'delivered') {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = 'delivered', actual_delivery_time = NOW()
                WHERE order_id = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET order_status = ?
                WHERE order_id = ?
            ");
        }
        
        if ($status === 'delivered') {
            $stmt->execute([$orderId]);
            
            // Update rider status back to active and increment delivery count
            $stmt = $pdo->prepare("
                UPDATE riders 
                SET status = 'active', total_deliveries = total_deliveries + 1 
                WHERE rider_id = ?
            ");
            $stmt->execute([$rider['rider_id']]);
            
        } else {
            $stmt->execute([$status, $orderId]);
        }
        
        // Create notification for customer
        $title = $status === 'delivered' ? 'Order Delivered' : 'Order Status Update';
        $message = $status === 'delivered' 
            ? "Your order #$orderId has been delivered successfully!"
            : "Your order #$orderId status has been updated to: " . ucfirst(str_replace('_', ' ', $status));
            
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_id, recipient_type, title, message, type, related_order_id)
            SELECT customerId, 'customer', ?, ?, 'order_update', ?
            FROM orders WHERE order_id = ?
        ");
        $stmt->execute([$title, $message, $orderId, $orderId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update order status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
}

/**
 * Update rider location
 */
function updateRiderLocation($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $lat = $input['lat'] ?? null;
    $lng = $input['lng'] ?? null;
    
    if (!$lat || !$lng) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Latitude and longitude required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE riders 
            SET current_location_lat = ?, current_location_lng = ?
            WHERE rider_id = ?
        ");
        $stmt->execute([$lat, $lng, $rider['rider_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Location updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update location error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update location']);
    }
}

/**
 * Update rider status
 */
function updateRiderStatus($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $status = $input['status'] ?? null;
    
    $allowedStatuses = ['active', 'inactive', 'offline'];
    if (!$status || !in_array($status, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid status required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE riders SET status = ? WHERE rider_id = ?");
        $stmt->execute([$status, $rider['rider_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update rider status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}
?>