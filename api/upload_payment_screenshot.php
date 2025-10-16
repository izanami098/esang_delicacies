<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';

// Security configuration
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];
const UPLOAD_DIR = '../uploads/payment_screenshots/';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Validate required data
    if (!isset($_POST['order_id']) || !isset($_POST['customer_id'])) {
        throw new Exception('Missing required order ID or customer ID');
    }
    
    $order_id = intval($_POST['order_id']);
    $customer_id = intval($_POST['customer_id']);
    $payment_account_name = isset($_POST['payment_account_name']) ? trim($_POST['payment_account_name']) : null;
    $payment_reference_number = isset($_POST['payment_reference_number']) ? trim($_POST['payment_reference_number']) : null;
    
    // Validate file upload
    if (!isset($_FILES['payment_screenshot']) || $_FILES['payment_screenshot']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'File upload failed';
        if (isset($_FILES['payment_screenshot']['error'])) {
            switch ($_FILES['payment_screenshot']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'File is too large. Maximum size is 5MB.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'File upload was interrupted. Please try again.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No file was uploaded.';
                    break;
                default:
                    $error_message = 'Unknown file upload error.';
            }
        }
        throw new Exception($error_message);
    }
    
    $file = $_FILES['payment_screenshot'];
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, and PNG files are allowed.');
    }
    
    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('Invalid file extension. Only JPG, JPEG, and PNG files are allowed.');
    }
    
    // Verify the order exists and belongs to the customer
    $verify_stmt = $pdo->prepare("
        SELECT o.order_id, o.payment_method, o.customer_id 
        FROM orders o 
        WHERE o.order_id = ? AND o.customer_id = ?
    ");
    $verify_stmt->execute([$order_id, $customer_id]);
    $order = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or does not belong to the customer');
    }
    
    // Check if payment method requires screenshot
    if (!in_array($order['payment_method'], ['GCash', 'Bank Transfer'])) {
        throw new Exception('Payment screenshots are only required for GCash and Bank Transfer payments');
    }
    
    // Generate secure filename
    $timestamp = time();
    $random_string = bin2hex(random_bytes(8));
    $stored_filename = $timestamp . '_' . $random_string . '.' . $file_extension;
    $file_path = UPLOAD_DIR . $stored_filename;
    
    // Create upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Move uploaded file to secure location
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Start database transaction
    $pdo->beginTransaction();
    
    try {
        // Update orders table with screenshot information and payment details
        $update_order_stmt = $pdo->prepare("
            UPDATE orders SET 
                payment_screenshot_path = ?,
                payment_screenshot_original_name = ?,
                payment_screenshot_uploaded_at = CURRENT_TIMESTAMP,
                payment_verified = 0,
                payment_account_name = ?,
                payment_reference_number = ?
            WHERE order_id = ?
        ");
        
        $update_order_stmt->execute([
            'uploads/payment_screenshots/' . $stored_filename,
            $file['name'],
            $payment_account_name,
            $payment_reference_number,
            $order_id
        ]);
        
        // Insert into uploaded_files table for tracking
        $insert_file_stmt = $pdo->prepare("
            INSERT INTO uploaded_files (
                order_id, 
                file_type, 
                original_filename, 
                stored_filename, 
                file_path, 
                file_size, 
                mime_type, 
                uploaded_by,
                payment_account_name,
                payment_reference_number
            ) VALUES (?, 'payment_screenshot', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_file_stmt->execute([
            $order_id,
            $file['name'],
            $stored_filename,
            'uploads/payment_screenshots/' . $stored_filename,
            $file['size'],
            $mime_type,
            $customer_id,
            $payment_account_name,
            $payment_reference_number
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment screenshot uploaded successfully',
            'data' => [
                'order_id' => $order_id,
                'filename' => $stored_filename,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'upload_time' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        // Delete uploaded file if database operation failed
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        throw $e;
    }
    
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