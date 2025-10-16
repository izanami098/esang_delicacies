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
    
    // Validate required fields
    $required_fields = ['order_id', 'customer_id', 'delivery_rating', 'taste_rating', 'food_quality_rating', 'service_rating'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $order_id = intval($input['order_id']);
    $customer_id = intval($input['customer_id']);
    $delivery_rating = intval($input['delivery_rating']);
    $taste_rating = intval($input['taste_rating']);
    $food_quality_rating = intval($input['food_quality_rating']);
    $service_rating = intval($input['service_rating']);
    $comment = isset($input['comment']) ? trim($input['comment']) : null;
    $is_anonymous = isset($input['is_anonymous']) ? intval($input['is_anonymous']) : 0;
    
    // Validate rating values (1-5)
    $ratings = [$delivery_rating, $taste_rating, $food_quality_rating, $service_rating];
    foreach ($ratings as $rating) {
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating values must be between 1 and 5");
        }
    }
    
    // Calculate overall rating (average of all ratings)
    $overall_rating = ($delivery_rating + $taste_rating + $food_quality_rating + $service_rating) / 4;
    $overall_rating = round($overall_rating, 1);
    
    // Verify the order exists and belongs to the customer
    $verify_stmt = $pdo->prepare("
        SELECT o.order_id, o.status 
        FROM orders o 
        WHERE o.order_id = ? AND o.customer_id = ? AND o.status = 'delivered'
    ");
    $verify_stmt->execute([$order_id, $customer_id]);
    $order = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found, doesn't belong to customer, or is not delivered yet");
    }
    
    // Check if feedback already exists for this order
    $check_stmt = $pdo->prepare("SELECT feedback_id FROM feedback WHERE order_id = ?");
    $check_stmt->execute([$order_id]);
    $existing_feedback = $check_stmt->fetch();
    
    if ($existing_feedback) {
        // Update existing feedback
        $update_stmt = $pdo->prepare("
            UPDATE feedback SET 
                delivery_rating = ?,
                taste_rating = ?,
                food_quality_rating = ?,
                service_rating = ?,
                overall_rating = ?,
                comment = ?,
                is_anonymous = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE order_id = ?
        ");
        
        $update_stmt->execute([
            $delivery_rating,
            $taste_rating,
            $food_quality_rating,
            $service_rating,
            $overall_rating,
            $comment,
            $is_anonymous,
            $order_id
        ]);
        
        $feedback_id = $existing_feedback['feedback_id'];
        $action = 'updated';
        
    } else {
        // Insert new feedback
        $insert_stmt = $pdo->prepare("
            INSERT INTO feedback (
                order_id, 
                customer_id, 
                delivery_rating, 
                taste_rating, 
                food_quality_rating, 
                service_rating, 
                overall_rating,
                comment,
                is_anonymous
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insert_stmt->execute([
            $order_id,
            $customer_id,
            $delivery_rating,
            $taste_rating,
            $food_quality_rating,
            $service_rating,
            $overall_rating,
            $comment,
            $is_anonymous
        ]);
        
        $feedback_id = $pdo->lastInsertId();
        $action = 'created';
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Feedback {$action} successfully",
        'data' => [
            'feedback_id' => $feedback_id,
            'order_id' => $order_id,
            'overall_rating' => $overall_rating,
            'action' => $action
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