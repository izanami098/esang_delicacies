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
    $customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Base query for feedback with order and customer details
    $base_query = "
        SELECT 
            f.feedback_id,
            f.order_id,
            f.customer_id,
            f.delivery_rating,
            f.taste_rating,
            f.food_quality_rating,
            f.service_rating,
            f.overall_rating,
            f.comment,
            f.is_anonymous,
            f.status,
            f.created_at,
            f.updated_at,
            c.name as customer_name,
            c.email as customer_email,
            o.total_amount,
            o.delivery_address,
            o.order_date,
            r.name as rider_name
        FROM feedback f
        JOIN customer c ON f.customer_id = c.customerId
        JOIN orders o ON f.order_id = o.order_id
        LEFT JOIN rider r ON o.rider_id = r.empId
        WHERE f.status = 'active'
    ";
    
    $params = [];
    
    // Add filters based on parameters
    if ($order_id) {
        $base_query .= " AND f.order_id = ?";
        $params[] = $order_id;
    }
    
    if ($customer_id) {
        $base_query .= " AND f.customer_id = ?";
        $params[] = $customer_id;
    }
    
    // Get single feedback record if order_id is specified
    if ($order_id) {
        $stmt = $pdo->prepare($base_query);
        $stmt->execute($params);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$feedback) {
            echo json_encode([
                'success' => true,
                'data' => null,
                'message' => 'No feedback found for this order'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => $feedback
            ]);
        }
        
    } else {
        // Get multiple feedback records with pagination
        $count_query = "SELECT COUNT(*) as total FROM feedback f WHERE f.status = 'active'";
        if ($customer_id) {
            $count_query .= " AND f.customer_id = ?";
        }
        
        $count_stmt = $pdo->prepare($count_query);
        if ($customer_id) {
            $count_stmt->execute([$customer_id]);
        } else {
            $count_stmt->execute();
        }
        $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Add ordering and pagination
        $base_query .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($base_query);
        $stmt->execute($params);
        $feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate average ratings for summary
        $avg_query = "
            SELECT 
                AVG(delivery_rating) as avg_delivery,
                AVG(taste_rating) as avg_taste,
                AVG(food_quality_rating) as avg_food_quality,
                AVG(service_rating) as avg_service,
                AVG(overall_rating) as avg_overall,
                COUNT(*) as total_reviews
            FROM feedback 
            WHERE status = 'active'
        ";
        
        if ($customer_id) {
            $avg_query .= " AND customer_id = ?";
            $avg_stmt = $pdo->prepare($avg_query);
            $avg_stmt->execute([$customer_id]);
        } else {
            $avg_stmt = $pdo->prepare($avg_query);
            $avg_stmt->execute();
        }
        
        $averages = $avg_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $feedback_list,
            'pagination' => [
                'total' => intval($total_count),
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ],
            'summary' => [
                'average_delivery_rating' => round(floatval($averages['avg_delivery']), 1),
                'average_taste_rating' => round(floatval($averages['avg_taste']), 1),
                'average_food_quality_rating' => round(floatval($averages['avg_food_quality']), 1),
                'average_service_rating' => round(floatval($averages['avg_service']), 1),
                'average_overall_rating' => round(floatval($averages['avg_overall']), 1),
                'total_reviews' => intval($averages['total_reviews'])
            ]
        ]);
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