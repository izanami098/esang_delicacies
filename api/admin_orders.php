<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Role-based authentication - Order Manager role only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'order_manager') {
    sendResponse(false, 'Access denied. Order Manager role required.');
}

require_once '../app/config/database.php';

try {
    $db = Database::getConnection();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_orders':
            handleGetOrders($db);
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Admin Orders API Error: " . $e->getMessage());
    sendResponse(false, 'Internal server error: ' . $e->getMessage());
}

function handleGetOrders($db) {
    $status = $_GET['status'] ?? '';
    
    if (empty($status)) {
        sendResponse(false, 'Status parameter required');
        return;
    }
    
    // Handle multiple statuses
    $statusArray = explode(',', $status);
    $placeholders = str_repeat('?,', count($statusArray) - 1) . '?';
    
    // Base query to get orders with customer information
    $query = "
        SELECT 
            o.order_id,
            o.customer_id,
            o.rider_id,
            o.total_amount,
            o.status,
            o.payment_method,
            o.payment_verified,
            o.delivery_address,
            o.customer_phone,
            o.order_date,
            o.special_instructions,
            o.created_at,
            o.updated_at,
            c.name as customer_name,
            c.email as customer_email,
            ps.file_path as payment_screenshot,
            ps.file_path as proof_screenshot,
            rr.reason as return_reason,
            rr.status as return_status
        FROM orders o
        LEFT JOIN customer c ON o.customer_id = c.customerId  
        LEFT JOIN payment_screenshots ps ON o.order_id = ps.order_id
        LEFT JOIN return_requests rr ON o.order_id = rr.order_id
        WHERE o.status IN ($placeholders)
        ORDER BY o.order_date DESC, o.order_id DESC
    ";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($statusArray);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process the orders to include additional information
        $processedOrders = [];
        foreach ($orders as $order) {
            // Convert null values to empty strings for frontend
            $order['customer_name'] = $order['customer_name'] ?: 'Unknown Customer';
            $order['customer_phone'] = $order['customer_phone'] ?: 'N/A';
            $order['delivery_address'] = $order['delivery_address'] ?: 'N/A';
            $order['payment_method'] = $order['payment_method'] ?: 'N/A';
            $order['special_instructions'] = $order['special_instructions'] ?: null;
            $order['return_reason'] = $order['return_reason'] ?: null;
            
            // Format dates
            if ($order['order_date']) {
                $order['formatted_date'] = date('M j, Y g:i A', strtotime($order['order_date']));
            } else {
                $order['formatted_date'] = 'N/A';
            }
            
            // Check if payment screenshot exists
            $order['has_screenshot'] = !empty($order['payment_screenshot']) || !empty($order['proof_screenshot']);
            
            $processedOrders[] = $order;
        }
        
        sendResponse(true, 'Orders retrieved successfully', [
            'orders' => $processedOrders,
            'count' => count($processedOrders)
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in get_orders: " . $e->getMessage());
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}
?>