<?php
/**
 * Rider Profile Management API
 * Handles rider profile operations - view, update, password change, etc.
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/HashBasedAuth.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new HashBasedAuth($pdo);

// Enhanced authentication - support both hash-based and session-based
$rider = null;
$riderId = null;

// Try hash-based authentication first
if ($auth->isRiderAuthenticated()) {
    $rider = $auth->getAuthenticatedRider();
    $riderId = $rider['rider_id'];
} else {
    // Fallback to session-based authentication
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'RIDER' && isset($_SESSION['riderId'])) {
        $riderId = $_SESSION['riderId'];
        // Get rider data from database with profile_hash if available
        try {
            $stmt = $pdo->prepare("SELECT r.rider_id, r.name, r.email, r.phone, r.license_number, r.vehicle_type, r.vehicle_plate, r.status, r.rating, r.total_deliveries, ph.profile_hash FROM riders r LEFT JOIN profile_hashes ph ON r.rider_id = ph.user_id AND ph.user_type = 'rider' WHERE r.rider_id = ?");
            $stmt->execute([$riderId]);
            $rider = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Fallback rider auth error: " . $e->getMessage());
        }
    } elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rider' && isset($_SESSION['rider_id'])) {
        $riderId = $_SESSION['rider_id'];
        // Get rider data from database with profile_hash if available
        try {
            $stmt = $pdo->prepare("SELECT r.rider_id, r.name, r.email, r.phone, r.license_number, r.vehicle_type, r.vehicle_plate, r.status, r.rating, r.total_deliveries, ph.profile_hash FROM riders r LEFT JOIN profile_hashes ph ON r.rider_id = ph.user_id AND ph.user_type = 'rider' WHERE r.rider_id = ?");
            $stmt->execute([$riderId]);
            $rider = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Fallback rider auth error: " . $e->getMessage());
        }
    }
}

// Check if we have authenticated rider
if (!$rider || !$riderId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
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
        case 'DELETE':
            handleDeleteRequest($action, $rider);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Rider Profile API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($action, $rider) {
    switch ($action) {
        case 'profile':
            getRiderProfile($rider);
            break;
        case 'activity_logs':
            getActivityLogs($rider);
            break;
        case 'earnings':
            getEarnings($rider);
            break;
        case 'performance':
            getPerformanceMetrics($rider);
            break;
        default:
            getRiderProfile($rider); // Default to profile
    }
}

function handlePostRequest($action, $rider) {
    switch ($action) {
        case 'upload_avatar':
            uploadAvatar($rider);
            break;
        case 'update_availability':
            updateAvailability($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $rider) {
    switch ($action) {
        case 'profile':
            updateRiderProfile($rider);
            break;
        case 'password':
            changePassword($rider);
            break;
        case 'vehicle_info':
            updateVehicleInfo($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $rider) {
    switch ($action) {
        case 'avatar':
            deleteAvatar($rider);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Get complete rider profile
 */
function getRiderProfile($rider) {
    global $pdo;
    
    try {
        // Get detailed rider information
        $stmt = $pdo->prepare("
            SELECT rider_id, name, email, phone, license_number, vehicle_type, 
                   vehicle_plate, status, current_location_lat, current_location_lng,
                   rating, total_deliveries, created_at, updated_at, is_approved,
                   profile_hash
            FROM riders 
            WHERE rider_id = ?
        ");
        $stmt->execute([$rider['rider_id']]);
        $riderData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$riderData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Rider profile not found']);
            return;
        }
        
        // Get recent activity count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as recent_orders
            FROM orders 
            WHERE rider_id = ? AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$rider['rider_id']]);
        $activityData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $riderData['recent_orders_count'] = $activityData['recent_orders'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'profile' => $riderData
        ]);
        
    } catch (Exception $e) {
        error_log("Get rider profile error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve profile']);
    }
}

/**
 * Update rider profile
 */
function updateRiderProfile($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    // Allowed fields for update
    $allowedFields = ['name', 'phone'];
    $updateFields = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field]) && !empty(trim($input[$field]))) {
            $updateFields[] = "$field = ?";
            $params[] = trim($input[$field]);
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }
    
    // Phone validation if provided
    if (isset($input['phone'])) {
        if (!preg_match('/^(09|\+639)\d{9}$/', $input['phone'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
            return;
        }
    }
    
    try {
        $updateFields[] = "updated_at = NOW()";
        $params[] = $rider['rider_id'];
        
        $sql = "UPDATE riders SET " . implode(', ', $updateFields) . " WHERE rider_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log the activity
        logRiderActivity($rider, 'profile_update', 'Profile information updated', $input);
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update rider profile error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
}

/**
 * Change rider password
 */
function changePassword($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['current_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Current password and new password required']);
        return;
    }
    
    if (strlen($input['new_password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
        return;
    }
    
    try {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM riders WHERE rider_id = ?");
        $stmt->execute([$rider['rider_id']]);
        $passwordData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$passwordData || !password_verify($input['current_password'], $passwordData['password_hash'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }
        
        // Update password
        $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE riders SET password_hash = ?, updated_at = NOW() WHERE rider_id = ?");
        $stmt->execute([$hashedPassword, $rider['rider_id']]);
        
        // Log the activity
        logRiderActivity($rider, 'password_change', 'Password changed successfully');
        
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }
}

/**
 * Update vehicle information
 */
function updateVehicleInfo($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        return;
    }
    
    $allowedVehicleTypes = ['motorcycle', 'bicycle', 'car', 'scooter', 'tricycle'];
    $updateFields = [];
    $params = [];
    
    if (isset($input['vehicle_type']) && in_array($input['vehicle_type'], $allowedVehicleTypes)) {
        $updateFields[] = "vehicle_type = ?";
        $params[] = $input['vehicle_type'];
    }
    
    if (isset($input['vehicle_plate']) && !empty(trim($input['vehicle_plate']))) {
        $updateFields[] = "vehicle_plate = ?";
        $params[] = trim($input['vehicle_plate']);
    }
    
    if (isset($input['license_number']) && !empty(trim($input['license_number']))) {
        $updateFields[] = "license_number = ?";
        $params[] = trim($input['license_number']);
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid vehicle information to update']);
        return;
    }
    
    try {
        $updateFields[] = "updated_at = NOW()";
        $params[] = $rider['rider_id'];
        
        $sql = "UPDATE riders SET " . implode(', ', $updateFields) . " WHERE rider_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log the activity
        logRiderActivity($rider, 'vehicle_update', 'Vehicle information updated', $input);
        
        echo json_encode([
            'success' => true,
            'message' => 'Vehicle information updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update vehicle info error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update vehicle information']);
    }
}

/**
 * Get rider activity logs
 */
function getActivityLogs($rider) {
    global $pdo;
    
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    // If no profile_hash available (session-based auth), return empty logs
    if (!isset($rider['profile_hash']) || empty($rider['profile_hash'])) {
        echo json_encode([
            'success' => true,
            'logs' => [],
            'count' => 0,
            'message' => 'Activity logging not available for session-based authentication'
        ]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT action, details, ip_address, created_at
            FROM profile_hash_logs 
            WHERE profile_hash = ? AND user_type = 'rider'
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$rider['profile_hash'], $limit, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process logs for better readability
        foreach ($logs as &$log) {
            $log['details'] = json_decode($log['details'], true);
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
        
    } catch (Exception $e) {
        error_log("Get activity logs error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve activity logs']);
    }
}

/**
 * Get earnings information
 */
function getEarnings($rider) {
    global $pdo;
    
    try {
        // Total earnings
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(delivery_fee), 0) as total_earnings,
                COUNT(*) as completed_deliveries
            FROM orders 
            WHERE rider_id = ? AND order_status = 'delivered'
        ");
        $stmt->execute([$rider['rider_id']]);
        $totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // This month's earnings
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(delivery_fee), 0) as month_earnings,
                COUNT(*) as month_deliveries
            FROM orders 
            WHERE rider_id = ? AND order_status = 'delivered' 
            AND MONTH(order_date) = MONTH(NOW()) AND YEAR(order_date) = YEAR(NOW())
        ");
        $stmt->execute([$rider['rider_id']]);
        $monthEarnings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // This week's earnings
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(delivery_fee), 0) as week_earnings,
                COUNT(*) as week_deliveries
            FROM orders 
            WHERE rider_id = ? AND order_status = 'delivered' 
            AND WEEK(order_date) = WEEK(NOW()) AND YEAR(order_date) = YEAR(NOW())
        ");
        $stmt->execute([$rider['rider_id']]);
        $weekEarnings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'earnings' => [
                'total' => $totalEarnings,
                'month' => $monthEarnings,
                'week' => $weekEarnings
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get earnings error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve earnings']);
    }
}

/**
 * Get performance metrics
 */
function getPerformanceMetrics($rider) {
    global $pdo;
    
    try {
        // Average rating and delivery metrics
        $stmt = $pdo->prepare("
            SELECT 
                rating,
                total_deliveries,
                status,
                COALESCE(
                    (SELECT AVG(TIMESTAMPDIFF(MINUTE, order_date, actual_delivery_time))
                     FROM orders 
                     WHERE rider_id = ? AND actual_delivery_time IS NOT NULL), 0
                ) as avg_delivery_time_minutes
            FROM riders 
            WHERE rider_id = ?
        ");
        $stmt->execute([$rider['rider_id'], $rider['rider_id']]);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get delivery success rate
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_assigned,
                COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as completed,
                COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled
            FROM orders 
            WHERE rider_id = ?
        ");
        $stmt->execute([$rider['rider_id']]);
        $deliveryStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $successRate = $deliveryStats['total_assigned'] > 0 
            ? ($deliveryStats['completed'] / $deliveryStats['total_assigned']) * 100 
            : 0;
        
        $metrics['success_rate'] = round($successRate, 2);
        $metrics['delivery_stats'] = $deliveryStats;
        
        echo json_encode([
            'success' => true,
            'performance' => $metrics
        ]);
        
    } catch (Exception $e) {
        error_log("Get performance metrics error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve performance metrics']);
    }
}

/**
 * Update rider availability status
 */
function updateAvailability($rider) {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status is required']);
        return;
    }
    
    $allowedStatuses = ['active', 'inactive', 'offline'];
    if (!in_array($input['status'], $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE riders SET status = ?, updated_at = NOW() WHERE rider_id = ?");
        $stmt->execute([$input['status'], $rider['rider_id']]);
        
        // Log the activity
        logRiderActivity($rider, 'status_change', 'Availability status changed to: ' . $input['status']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Availability status updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update availability error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update availability']);
    }
}

/**
 * Log rider activity for tracking
 */
function logRiderActivity($rider, $action, $description, $additionalData = []) {
    global $pdo;
    
    // Only log if profile_hash_logs table exists and we have profile_hash
    if (!isset($rider['profile_hash']) || empty($rider['profile_hash'])) {
        return; // Skip logging for session-based auth without profile hash
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO profile_hash_logs (profile_hash, user_type, action, details, ip_address, user_agent, created_at)
            VALUES (?, 'rider', ?, ?, ?, ?, NOW())
        ");
        
        $details = array_merge([
            'rider_id' => $rider['rider_id'],
            'description' => $description
        ], $additionalData);
        
        $stmt->execute([
            $rider['profile_hash'],
            $action,
            json_encode($details),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        // Log table might not exist - that's okay for now
        error_log("Failed to log rider activity (this is optional): " . $e->getMessage());
    }
}
?>