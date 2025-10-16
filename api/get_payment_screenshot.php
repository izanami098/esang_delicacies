<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
    
    if (!$order_id) {
        throw new Exception('Order ID is required');
    }
    
    // Get order details with payment screenshot information
    $query = "
        SELECT 
            o.order_id,
            o.customer_id,
            o.payment_method,
            o.payment_screenshot_path,
            o.payment_screenshot_original_name,
            o.payment_screenshot_uploaded_at,
            o.payment_verified,
            o.total_amount,
            o.order_date,
            c.name as customer_name,
            c.email as customer_email,
            uf.file_size,
            uf.mime_type
        FROM orders o
        JOIN customer c ON o.customer_id = c.customerId
        LEFT JOIN uploaded_files uf ON o.order_id = uf.order_id AND uf.file_type = 'payment_screenshot' AND uf.is_active = 1
        WHERE o.order_id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'order_id' => $order['order_id'],
            'customer_id' => $order['customer_id'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'payment_method' => $order['payment_method'],
            'total_amount' => $order['total_amount'],
            'order_date' => $order['order_date'],
            'payment_verified' => (bool) $order['payment_verified'],
            'has_screenshot' => !empty($order['payment_screenshot_path'])
        ]
    ];
    
    // Add screenshot information if available
    if (!empty($order['payment_screenshot_path'])) {
        $response['data']['screenshot'] = [
            'path' => $order['payment_screenshot_path'],
            'original_name' => $order['payment_screenshot_original_name'],
            'uploaded_at' => $order['payment_screenshot_uploaded_at'],
            'file_size' => $order['file_size'],
            'mime_type' => $order['mime_type'],
            // Generate secure download URL
            'download_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/esang_delicacies/api/view_payment_screenshot.php?order_id=' . $order_id
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>