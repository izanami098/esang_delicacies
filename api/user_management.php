<?php
/**
 * User Management API
 * Handles user creation, management, and authentication for all user types
 */

session_start();
require_once '../app/config/database.php';
require_once '../app/auth/HashBasedAuth.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new HashBasedAuth();
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
        case 'PUT':
            handlePutRequest($action, $auth);
            break;
        case 'DELETE':
            handleDeleteRequest($action, $auth);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("User Management API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handlePostRequest($action, $auth) {
    switch ($action) {
        case 'create_customer':
            createCustomer($auth);
            break;
        case 'create_admin':
            createAdmin($auth);
            break;
        case 'create_cashier':
            createCashier($auth);
            break;
        case 'create_order_manager':
            createOrderManager($auth);
            break;
        case 'create_rider':
            createRider($auth);
            break;
        case 'login':
            authenticateUser($auth);
            break;
        case 'logout':
            logoutUser($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleGetRequest($action, $auth) {
    switch ($action) {
        case 'list_users':
            listUsers($auth);
            break;
        case 'user_profile':
            getUserProfile($auth);
            break;
        case 'check_session':
            checkSession($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePutRequest($action, $auth) {
    switch ($action) {
        case 'update_user':
            updateUser($auth);
            break;
        case 'change_password':
            changePassword($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDeleteRequest($action, $auth) {
    switch ($action) {
        case 'deactivate_user':
            deactivateUser($auth);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

/**
 * Create a new customer
 */
function createCustomer($auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['name', 'email', 'phone', 'password'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        return;
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Validate password strength
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        return;
    }
    
    // Prepare customer data
    $customerData = [
        'username' => $input['name'],
        'first_name' => $input['first_name'] ?? '',
        'last_name' => $input['last_name'] ?? '',
        'email' => $input['email'],
        'phone' => $input['phone'],
        'address' => $input['address'] ?? '',
        'password' => $input['password']
    ];
    
    $result = $auth->registerCustomer($customerData);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Create a new admin (requires admin privileges)
 */
function createAdmin($auth) {
    // Check if user is authenticated as admin
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $result = createStaffUser($input, 'admin');
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Create a new cashier (requires admin privileges)
 */
function createCashier($auth) {
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $result = createStaffUser($input, 'cashier');
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Create a new order manager (requires admin privileges)
 */
function createOrderManager($auth) {
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $result = createStaffUser($input, 'order_manager');
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Create a new rider (requires admin privileges)
 */
function createRider($auth) {
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Riders need additional validation for plate number
    if (empty($input['plateNum'])) {
        $input['plateNum'] = 'N/A'; // Default value as seen in your database
    }
    
    $result = createStaffUser($input, 'rider');
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Helper function to create staff users (admin, cashier, order_manager, rider)
 */
function createStaffUser($input, $userType) {
    // Validate required fields
    $required = ['name', 'password'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        return [
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ];
    }
    
    // Validate email if provided
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Validate password strength
    if (strlen($input['password']) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
    }
    
    try {
        $db = Database::getConnection();
        
        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Prepare data based on user type
        switch ($userType) {
            case 'admin':
                $stmt = $db->prepare("
                    INSERT INTO admin (name, password, email, phoneNum, created_at, status) 
                    VALUES (?, ?, ?, ?, NOW(), 'active')
                ");
                $stmt->execute([
                    $input['name'],
                    $hashedPassword,
                    $input['email'] ?? null,
                    $input['phone'] ?? null
                ]);
                break;
                
            case 'cashier':
                $stmt = $db->prepare("
                    INSERT INTO cashier (name, password, email, phone, created_at, status) 
                    VALUES (?, ?, ?, ?, NOW(), 'active')
                ");
                $stmt->execute([
                    $input['name'],
                    $hashedPassword,
                    $input['email'] ?? null,
                    $input['phone'] ?? ''
                ]);
                break;
                
            case 'order_manager':
                $stmt = $db->prepare("
                    INSERT INTO order_manager (name, password, email, phoneNum, created_at, status) 
                    VALUES (?, ?, ?, ?, NOW(), 'active')
                ");
                $stmt->execute([
                    $input['name'],
                    $hashedPassword,
                    $input['email'] ?? null,
                    $input['phone'] ?? null
                ]);
                break;
                
            case 'rider':
                $stmt = $db->prepare("
                    INSERT INTO rider (name, plateNum, password, email, phone, created_at, status) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'active')
                ");
                $stmt->execute([
                    $input['name'],
                    $input['plateNum'] ?? 'N/A',
                    $hashedPassword,
                    $input['email'] ?? null,
                    $input['phone'] ?? ''
                ]);
                break;
        }
        
        $userId = $db->lastInsertId();
        
        return [
            'success' => true,
            'message' => ucfirst($userType) . ' created successfully',
            'user_id' => $userId,
            'user_type' => $userType
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to create ' . $userType . ': ' . $e->getMessage()
        ];
    }
}

/**
 * Authenticate user (universal login)
 */
function authenticateUser($auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        return;
    }
    
    $userType = $input['user_type'] ?? 'CUSTOMER';
    
    $result = $auth->login($input['email'], $input['password'], $userType);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(401);
        echo json_encode($result);
    }
}

/**
 * Logout current user
 */
function logoutUser($auth) {
    $result = $auth->logout();
    
    http_response_code(200);
    echo json_encode($result);
}

/**
 * Check current session status
 */
function checkSession($auth) {
    if ($auth->validateSession()) {
        $profile = $auth->getCurrentUserProfile();
        
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'role' => $_SESSION['role'] ?? 'unknown',
                'name' => $_SESSION['user_name'] ?? 'User',
                'email' => $_SESSION['email'] ?? '',
                'profile' => $profile
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
 * Get user profile information
 */
function getUserProfile($auth) {
    if (!$auth->validateSession()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        return;
    }
    
    $profile = $auth->getCurrentUserProfile();
    
    if ($profile) {
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Profile not found']);
    }
}

/**
 * List users (admin only)
 */
function listUsers($auth) {
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        $userType = $_GET['type'] ?? 'all';
        
        $users = [];
        
        if ($userType === 'all' || $userType === 'customer') {
            $stmt = $db->prepare("SELECT customerId as id, name, email, phone, created_at, status, 'customer' as type FROM customer WHERE status = 'active'");
            $stmt->execute();
            $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        if ($userType === 'all' || $userType === 'admin') {
            $stmt = $db->prepare("SELECT empId as id, name, email, phoneNum as phone, created_at, status, 'admin' as type FROM admin WHERE status = 'active'");
            $stmt->execute();
            $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        if ($userType === 'all' || $userType === 'cashier') {
            $stmt = $db->prepare("SELECT empId as id, name, email, phone, created_at, status, 'cashier' as type FROM cashier WHERE status = 'active'");
            $stmt->execute();
            $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        if ($userType === 'all' || $userType === 'order_manager') {
            $stmt = $db->prepare("SELECT empId as id, name, email, phoneNum as phone, created_at, status, 'order_manager' as type FROM order_manager WHERE status = 'active'");
            $stmt->execute();
            $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        if ($userType === 'all' || $userType === 'rider') {
            $stmt = $db->prepare("SELECT empId as id, name, email, phone, plateNum, created_at, status, 'rider' as type FROM rider WHERE status = 'active'");
            $stmt->execute();
            $users = array_merge($users, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'total' => count($users)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
}

/**
 * Change user password
 */
function changePassword($auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['old_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Both old and new passwords are required']);
        return;
    }
    
    $result = $auth->changePassword($input['old_password'], $input['new_password']);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Deactivate user (admin only)
 */
function deactivateUser($auth) {
    if (!$auth->validateSession('ADMIN')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin privileges required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['user_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID and type are required']);
        return;
    }
    
    try {
        $db = Database::getConnection();
        
        switch ($input['user_type']) {
            case 'customer':
                $stmt = $db->prepare("UPDATE customer SET status = 'inactive' WHERE customerId = ?");
                break;
            case 'admin':
                $stmt = $db->prepare("UPDATE admin SET status = 'inactive' WHERE empId = ?");
                break;
            case 'cashier':
                $stmt = $db->prepare("UPDATE cashier SET status = 'inactive' WHERE empId = ?");
                break;
            case 'order_manager':
                $stmt = $db->prepare("UPDATE order_manager SET status = 'inactive' WHERE empId = ?");
                break;
            case 'rider':
                $stmt = $db->prepare("UPDATE rider SET status = 'inactive' WHERE empId = ?");
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid user type']);
                return;
        }
        
        $stmt->execute([$input['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User deactivated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to deactivate user: ' . $e->getMessage()]);
    }
}
?>