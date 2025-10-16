<?php
/**
 * Rider Authentication API
 * Handles rider login, logout, registration, and session management
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/HashBasedAuth.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new HashBasedAuth($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'POST':
            handlePostRequest($action, $auth);
            break;
        case 'GET':
            handleGetRequest($action, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Rider Auth API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePostRequest($action, $auth) {
    switch ($action) {
        case 'login':
            riderLogin($auth);
            break;
        case 'logout':
            riderLogout($auth);
            break;
        case 'register':
            riderRegister($auth);
            break;
        case 'refresh_session':
            refreshSession($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleGetRequest($action, $auth) {
    switch ($action) {
        case 'check_session':
            checkSession($auth);
            break;
        case 'profile':
            getRiderProfile($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Handle rider login
 */
function riderLogin($auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        return;
    }
    
    $result = $auth->loginRider($input['email'], $input['password']);
    
    if ($result['success']) {
        // Log successful login
        logRiderActivity($auth, 'login', 'Rider logged in successfully');
        
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
}

/**
 * Handle rider logout
 */
function riderLogout($auth) {
    // Log logout activity before destroying session
    if ($auth->isRiderAuthenticated()) {
        logRiderActivity($auth, 'logout', 'Rider logged out');
    }
    
    $result = $auth->logout();
    
    http_response_code(200);
    echo json_encode($result);
}

/**
 * Handle rider registration
 */
function riderRegister($auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Required fields validation
    $required = ['name', 'email', 'phone', 'password'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
            return;
        }
    }
    
    // Email validation
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Phone validation (basic)
    if (!preg_match('/^(09|\+639)\d{9}$/', $input['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        return;
    }
    
    // Password strength validation
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        return;
    }
    
    $result = $auth->registerRider($input);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Check current session status
 */
function checkSession($auth) {
    $isAuthenticated = $auth->isRiderAuthenticated();
    
    if ($isAuthenticated) {
        $rider = $auth->getAuthenticatedRider();
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'rider' => [
                'rider_id' => $rider['rider_id'],
                'name' => $rider['name'],
                'email' => $rider['email'],
                'status' => $rider['status'],
                'profile_hash' => $rider['profile_hash']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }
}

/**
 * Get rider profile information
 */
function getRiderProfile($auth) {
    if (!$auth->isRiderAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }
    
    $rider = $auth->getAuthenticatedRider();
    
    if (!$rider) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Rider not found']);
        return;
    }
    
    // Remove sensitive information
    unset($rider['password_hash']);
    
    echo json_encode([
        'success' => true,
        'rider' => $rider
    ]);
}

/**
 * Refresh session token
 */
function refreshSession($auth) {
    if (!$auth->isRiderAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }
    
    $rider = $auth->getAuthenticatedRider();
    
    // Update session timestamp
    $_SESSION['login_time'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session refreshed',
        'rider_id' => $rider['rider_id']
    ]);
}

/**
 * Log rider activity for security and tracking
 */
function logRiderActivity($auth, $action, $description, $additionalData = []) {
    global $pdo;
    
    if (!$auth->isRiderAuthenticated()) {
        return;
    }
    
    $rider = $auth->getAuthenticatedRider();
    if (!$rider) {
        return;
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
        error_log("Failed to log rider activity: " . $e->getMessage());
    }
}
?>