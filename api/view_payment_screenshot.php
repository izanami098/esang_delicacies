<?php
require_once '../config/database.php';

// Security: Only allow order managers to view payment screenshots
// In a real application, you would implement proper authentication here
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

try {
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
    
    if (!$order_id) {
        throw new Exception('Order ID is required');
    }
    
    // Get payment screenshot path from database
    $stmt = $pdo->prepare("
        SELECT 
            payment_screenshot_path,
            payment_screenshot_original_name,
            payment_method
        FROM orders 
        WHERE order_id = ? AND payment_screenshot_path IS NOT NULL
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Payment screenshot not found for this order');
    }
    
    // Construct full file path
    $file_path = '../' . $order['payment_screenshot_path'];
    
    // Verify file exists
    if (!file_exists($file_path)) {
        throw new Exception('Payment screenshot file not found on server');
    }
    
    // Get file information
    $file_info = pathinfo($file_path);
    $mime_type = mime_content_type($file_path);
    
    // Validate mime type for security
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type');
    }
    
    // Set appropriate headers
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . basename($order['payment_screenshot_original_name']) . '"');
    header('Content-Length: ' . filesize($file_path));
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Cache-Control: private, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the file
    readfile($file_path);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error: ' . $e->getMessage();
    
} catch (Exception $e) {
    http_response_code(404);
    echo $e->getMessage();
}
?>