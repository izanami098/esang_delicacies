<?php
/**
 * Public Customer Registration API
 * Allows public registration of new customers without admin privileges
 */

session_start();
require_once '../db_connection.php';
require_once '../app/auth/HashBasedAuth.php';

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$auth = new HashBasedAuth();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            registerCustomer($auth);
            break;
        case 'GET':
            checkAvailability();
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Customer Registration API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Register a new customer (public endpoint)
 */
function registerCustomer($auth) {
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
    
    // Validate phone number (Philippine format)
    if (!preg_match('/^(09|\+639)\d{9}$/', $input['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX']);
        return;
    }
    
    // Validate password strength
    if (strlen($input['password']) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        return;
    }
    
    // Check if password contains at least one number and one letter
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must contain at least one letter and one number']);
        return;
    }
    
    // Check if email already exists
    if (emailExists($input['email'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }
    
    // Check if phone already exists
    if (phoneExists($input['phone'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Phone number already registered']);
        return;
    }
    
    // Prepare customer data
    $customerData = [
        'username' => $input['name'],
        'first_name' => $input['first_name'] ?? extractFirstName($input['name']),
        'last_name' => $input['last_name'] ?? extractLastName($input['name']),
        'email' => $input['email'],
        'phone' => $input['phone'],
        'address' => $input['address'] ?? '',
        'password' => $input['password']
    ];
    
    $result = $auth->registerCustomer($customerData);
    
    if ($result['success']) {
        // Log the registration
        logCustomerRegistration($result['customer_id'], $input['email']);
        
        // Remove sensitive data from response
        unset($result['profile_hash']);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! You can now login with your email and password.',
            'customer_id' => $result['customer_id']
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Check email/phone availability (GET endpoint)
 */
function checkAvailability() {
    $email = $_GET['email'] ?? '';
    $phone = $_GET['phone'] ?? '';
    
    if (empty($email) && empty($phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email or phone parameter required']);
        return;
    }
    
    $availability = [];
    
    if (!empty($email)) {
        $availability['email_available'] = !emailExists($email);
    }
    
    if (!empty($phone)) {
        $availability['phone_available'] = !phoneExists($phone);
    }
    
    echo json_encode([
        'success' => true,
        'availability' => $availability
    ]);
}

/**
 * Check if email already exists in the database
 */
function emailExists($email) {
    global $conn;
    try {
        
        // Check in customer table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_row()[0] > 0) {
            return true;
        }
        
        // Check in other user tables
        $tables = ['admin', 'cashier', 'order_manager', 'rider'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->fetch_row()[0] > 0) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error checking email existence: " . $e->getMessage());
        return true; // Assume exists to be safe
    }
}

/**
 * Check if phone already exists in the database
 */
function phoneExists($phone) {
    global $conn;
    try {
        
        // Check in customer table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE phone = ?");
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_row()[0] > 0) {
            return true;
        }
        
        // Check in other user tables (different column names)
        $stmt = $db->prepare("SELECT COUNT(*) FROM admin WHERE phoneNum = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM cashier WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM order_manager WHERE phoneNum = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM rider WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error checking phone existence: " . $e->getMessage());
        return true; // Assume exists to be safe
    }
}

/**
 * Extract first name from full name
 */
function extractFirstName($fullName) {
    $parts = explode(' ', trim($fullName));
    return $parts[0] ?? '';
}

/**
 * Extract last name from full name
 */
function extractLastName($fullName) {
    $parts = explode(' ', trim($fullName));
    if (count($parts) > 1) {
        array_shift($parts); // Remove first name
        return implode(' ', $parts);
    }
    return '';
}

/**
 * Log customer registration for analytics
 */
function logCustomerRegistration($customerId, $email) {
    try {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO user_activity_log (user_id, action, description, ip_address, user_agent, created_at)
            VALUES (?, 'REGISTRATION', ?, ?, ?, NOW())
        ");
        
        $description = json_encode([
            'customer_id' => $customerId,
            'email' => $email,
            'registration_type' => 'public'
        ]);
        
        $stmt->execute([
            $customerId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (Exception $e) {
        error_log("Failed to log customer registration: " . $e->getMessage());
    }
}
?>