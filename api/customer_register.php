<?php
/**
 * Customer Registration API - TrueHost Compatible
 */

require_once '_api_config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            registerCustomer();
            break;
        case 'GET':
            checkAvailability();
            break;
        default:
            apiError(405, 'Method not allowed');
    }
} catch (Exception $e) {
    error_log("Customer Registration API error: " . $e->getMessage());
    apiError(500, 'Internal server error');
}

/**
 * Register a new customer
 */
function registerCustomer() {
    global $conn;
    
    $input = getJsonInput();
    
    // Validate required fields
    $required = ['name', 'email', 'phone', 'password'];
    $missing = validateRequired($input, $required);
    
    if (!empty($missing)) {
        apiError(400, 'Missing required fields: ' . implode(', ', $missing));
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        apiError(400, 'Invalid email format');
    }
    
    // Validate phone number (Philippine format)
    if (!preg_match('/^(09|\+639)\d{9}$/', $input['phone'])) {
        apiError(400, 'Invalid phone number format. Use 09XXXXXXXXX or +639XXXXXXXXX');
    }
    
    // Validate password strength
    if (strlen($input['password']) < 8) {
        apiError(400, 'Password must be at least 8 characters long');
    }
    
    // Check if email already exists
    if (emailExists($input['email'])) {
        apiError(409, 'Email already registered');
    }
    
    // Check if phone already exists
    if (phoneExists($input['phone'])) {
        apiError(409, 'Phone number already registered');
    }
    
    // Create customer
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    $profileHash = hash('sha256', $input['email'] . time() . rand());
    
    $stmt = $conn->prepare("INSERT INTO customer (name, email, phone, address, password, profile_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssssss', 
        $input['name'],
        $input['email'], 
        $input['phone'],
        $input['address'] ?? '',
        $hashedPassword,
        $profileHash
    );
    
    if ($stmt->execute()) {
        $customerId = $conn->insert_id;
        apiSuccess([
            'customer_id' => $customerId,
            'profile_hash' => $profileHash
        ], 'Registration successful! You can now login with your email and password.', 201);
    } else {
        apiError(500, 'Registration failed: ' . $conn->error);
    }
}

/**
 * Check email/phone availability
 */
function checkAvailability() {
    $email = $_GET['email'] ?? '';
    $phone = $_GET['phone'] ?? '';
    
    if (empty($email) && empty($phone)) {
        apiError(400, 'Email or phone parameter required');
    }
    
    $availability = [];
    
    if (!empty($email)) {
        $availability['email_available'] = !emailExists($email);
    }
    
    if (!empty($phone)) {
        $availability['phone_available'] = !phoneExists($phone);
    }
    
    apiSuccess($availability);
}

/**
 * Check if email already exists
 */
function emailExists($email) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_row()[0] > 0;
}

/**
 * Check if phone already exists
 */
function phoneExists($phone) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE phone = ?");
    $stmt->bind_param('s', $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_row()[0] > 0;
}
?>