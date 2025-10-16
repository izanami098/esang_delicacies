<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['order_id'])) {
        throw new Exception('Order ID is required');
    }
    
    $order_id = intval($input['order_id']);
    
    // Verify the order exists and has a payment screenshot
    $verify_stmt = $pdo->prepare("
        SELECT 
            order_id, 
            payment_method, 
            payment_screenshot_path, 
            payment_verified 
        FROM orders 
        WHERE order_id = ?
    ");
    $verify_stmt->execute([$order_id]);
    $order = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    if ($order['payment_verified']) {
        throw new Exception('Payment is already verified');
    }
    
    if (!in_array($order['payment_method'], ['GCash', 'Bank Transfer'])) {
        throw new Exception('Payment verification is only required for GCash and Bank Transfer payments');
    }
    
    if (empty($order['payment_screenshot_path'])) {
        throw new Exception('No payment screenshot found for this order');
    }
    
    // Update payment verification status
    $update_stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_verified = 1 
        WHERE order_id = ?
    ");
    
    if (!$update_stmt->execute([$order_id])) {
        throw new Exception('Failed to update payment verification status');
    }
    
    // Log the verification (optional - for audit trail)
    // You can add a verification log table here if needed
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'data' => [
            'order_id' => $order_id,
            'verified_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
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