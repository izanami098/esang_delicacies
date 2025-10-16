<?php
/**
 * User Login API - TrueHost Compatible
 * Universal login for all user types (customer, admin, rider, etc.)
 */

require_once '_api_config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $action = $_GET['action'] ?? 'login';
            switch ($action) {
                case 'login':
                    loginUser();
                    break;
                case 'logout':
                    logoutUser();
                    break;
                case 'check_session':
                    checkSession();
                    break;
                default:
                    apiError(400, 'Invalid action');
            }
            break;
        case 'GET':
            checkSession();
            break;
        default:
            apiError(405, 'Method not allowed');
    }
} catch (Exception $e) {
    error_log("User Login API error: " . $e->getMessage());
    apiError(500, 'Internal server error');
}

/**
 * Login user (universal for all user types)
 */
function loginUser() {
    global $conn;
    
    $input = getJsonInput();
    
    // Validate required fields
    $required = ['email', 'password'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        apiError(400, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        apiError(400, 'Invalid email format');
    }
    
    $userType = $input['user_type'] ?? 'customer';
    $email = $input['email'];
    $password = $input['password'];
    
    // Try to authenticate user
    $user = authenticateUser($email, $password, $userType);
    
    if ($user) {
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_hash'] = $user['profile_hash'] ?? null;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Remove sensitive data from response
        unset($user['password']);
        
        apiSuccess([
            'user' => $user,
            'session_id' => session_id(),
            'user_type' => $userType
        ], 'Login successful');
    } else {
        apiError(401, 'Invalid email or password');
    }
}

/**
 * Logout current user
 */
function logoutUser() {
    // Destroy session
    session_destroy();
    
    apiSuccess(null, 'Logout successful');
}

/**
 * Check current session status
 */
function checkSession() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        apiSuccess([
            'authenticated' => true,
            'user_id' => $_SESSION['user_id'],
            'user_type' => $_SESSION['user_type'],
            'user_name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['email'] ?? ''
        ]);
    } else {
        apiSuccess([
            'authenticated' => false
        ]);
    }
}

/**
 * Authenticate user across all user types
 */
function authenticateUser($email, $password, $userType) {
    global $conn;
    
    $user = null;
    
    switch ($userType) {
        case 'customer':
            $stmt = $conn->prepare("SELECT customerId as id, name, email, phone, password, profile_hash, status FROM customer WHERE email = ? AND status = 'active'");
            break;
        case 'admin':
            $stmt = $conn->prepare("SELECT empId as id, name, email, phoneNum as phone, password, NULL as profile_hash, status FROM admin WHERE email = ? AND status = 'active'");
            break;
        case 'rider':
            $stmt = $conn->prepare("SELECT empId as id, name, email, phone, password, profile_hash, status FROM rider WHERE email = ? AND status = 'active'");
            break;
        case 'cashier':
            $stmt = $conn->prepare("SELECT empId as id, name, email, phone, password, NULL as profile_hash, status FROM cashier WHERE email = ? AND status = 'active'");
            break;
        case 'order_manager':
            $stmt = $conn->prepare("SELECT empId as id, name, email, phoneNum as phone, password, NULL as profile_hash, status FROM order_manager WHERE email = ? AND status = 'active'");
            break;
        default:
            return null;
    }
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    
    return null;
}
?>