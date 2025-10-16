<?php
/**
 * Hash-Based Authentication System
 * Provides secure user authentication using unique profile hashes
 */

require_once __DIR__ . '/../classes/ProfileHashManager.php';

class HashBasedAuth {
    private $profileManager;
    private $mysqli;
    
    public function __construct() {
        $this->profileManager = ProfileHashManager::getInstance();
        require_once __DIR__ . '/../config/database.php';
        $this->mysqli = Database::getConnection();
    }
    
    /**
     * Authenticate user login with hash-based session
     */
    public function login($username, $password, $userType = 'CUSTOMER') {
        try {
            // Find user based on type
            $user = $this->findUser($username, $userType);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
            
            // Verify password
            if (!$this->verifyPassword($password, $user, $userType)) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
            
            // For customers, ensure they have a profile hash
            if ($userType === 'CUSTOMER') {
                if (empty($user['profile_hash'])) {
                    $user['profile_hash'] = $this->profileManager->assignProfileHashToNewCustomer($user['customerId']);
                }
                
                // Create secure session
                $sessionData = $this->profileManager->createUserSession($user['profile_hash'], $userType);
                
                // Set PHP session variables
                session_start();
                $_SESSION = array_merge($_SESSION, [
                    'session_id' => $sessionData['session_id'],
                    'profile_hash' => $sessionData['profile_hash'],
                    'customerId' => $user['customerId'],
                    'role' => $userType,
                    'user_name' => $user['name'] ?? ($user['first_name'] . ' ' . $user['last_name']),
                    'email' => $user['email'],
                    'login_time' => time(),
                    'expires_at' => $sessionData['expires_at']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'profile_hash' => $sessionData['profile_hash'],
                    'redirect_url' => $this->getRedirectUrl($userType),
                    'session_data' => $sessionData
                ];
                
            } else {
                // For non-customer users (admin, order manager, rider)
                session_start();
                $_SESSION = array_merge($_SESSION, [
                    'empId' => $user['empId'] ?? $user['id'],
                    'role' => $userType,
                    'user_name' => $user['name'],
                    'email' => $user['email'],
                    'login_time' => time()
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect_url' => $this->getRedirectUrl($userType)
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Find user in database based on type
     */
    private function findUser($username, $userType) {
        switch ($userType) {
            case 'CUSTOMER':
                $stmt = $this->mysqli->prepare("
                    SELECT customerId, profile_hash, name, first_name, last_name, 
                           email, phone, address, password
                    FROM customer 
                    WHERE email = ? OR name = ?
                ");
                $stmt->bind_param('ss', $username, $username);
                break;
                
            case 'ADMIN':
                $stmt = $this->mysqli->prepare("
                    SELECT empId, name, email, password
                    FROM admin 
                    WHERE email = ? OR name = ?
                ");
                $stmt->bind_param('ss', $username, $username);
                break;
                
            case 'ORDER_MANAGER':
                $stmt = $this->mysqli->prepare("
                    SELECT empId, name, email, password
                    FROM order_manager 
                    WHERE email = ? OR name = ?
                ");
                $stmt->bind_param('ss', $username, $username);
                break;
                
            case 'RIDER':
                $stmt = $this->mysqli->prepare("
                    SELECT empId, name, email, password
                    FROM rider 
                    WHERE email = ? OR name = ?
                ");
                $stmt->bind_param('ss', $username, $username);
                break;
                
            default:
                return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    /**
     * Verify user password
     */
    private function verifyPassword($password, $user, $userType) {
        if (!$user || !isset($user['password'])) {
            return false;
        }
        
        // If password is hashed
        if (password_verify($password, $user['password'])) {
            return true;
        }
        
        // If password is plain text (for backwards compatibility)
        if ($password === $user['password']) {
            // Consider hashing the password for security
            $this->updatePasswordHash($user, $password, $userType);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update plain text password to hashed version
     */
    private function updatePasswordHash($user, $password, $userType) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        switch ($userType) {
            case 'CUSTOMER':
                $stmt = $this->mysqli->prepare("UPDATE customer SET password = ? WHERE customerId = ?");
                $stmt->bind_param('si', $hashedPassword, $user['customerId']);
                break;
                
            case 'ADMIN':
                $stmt = $this->mysqli->prepare("UPDATE admin SET password = ? WHERE empId = ?");
                $stmt->bind_param('si', $hashedPassword, $user['empId']);
                break;
                
            case 'ORDER_MANAGER':
                $stmt = $this->mysqli->prepare("UPDATE order_manager SET password = ? WHERE empId = ?");
                $stmt->bind_param('si', $hashedPassword, $user['empId']);
                break;
                
            case 'RIDER':
                $stmt = $this->mysqli->prepare("UPDATE rider SET password = ? WHERE empId = ?");
                $stmt->bind_param('si', $hashedPassword, $user['empId']);
                break;
        }
        
        if (isset($stmt)) {
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Validate current session using hash-based authentication
     */
    public function validateSession($requiredRole = null) {
        session_start();
        
        // For customer sessions, validate using profile hash
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'CUSTOMER') {
            if (!isset($_SESSION['session_id']) || !isset($_SESSION['profile_hash'])) {
                return false;
            }
            
            $session = $this->profileManager->validateUserSession($_SESSION['session_id']);
            
            if (!$session) {
                $this->logout();
                return false;
            }
            
            // Update session data if needed
            $_SESSION['user_name'] = $session['name'] ?? ($_SESSION['user_name'] ?? 'Customer');
            $_SESSION['email'] = $session['email'] ?? $_SESSION['email'];
            
            // Check role requirement
            if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
                return false;
            }
            
            return true;
            
        } else {
            // For non-customer users, use traditional validation
            if (!isset($_SESSION['role']) || !isset($_SESSION['empId'])) {
                return false;
            }
            
            // Check role requirement
            if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
                return false;
            }
            
            return true;
        }
    }
    
    /**
     * Logout current user
     */
    public function logout() {
        session_start();
        
        // If customer session, properly close hash-based session
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'CUSTOMER' && isset($_SESSION['session_id'])) {
            $this->profileManager->logoutUserSession($_SESSION['session_id']);
        }
        
        // Clear PHP session
        session_unset();
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect_url' => '/esang_delicacies/public/Index.php'
        ];
    }
    
    /**
     * Get current user profile using hash
     */
    public function getCurrentUserProfile() {
        if (!$this->validateSession()) {
            return null;
        }
        
        if ($_SESSION['role'] === 'CUSTOMER' && isset($_SESSION['profile_hash'])) {
            return $this->profileManager->getCustomerByHash($_SESSION['profile_hash']);
        }
        
        return null;
    }
    
    /**
     * Get redirect URL based on user type
     */
    private function getRedirectUrl($userType) {
        switch ($userType) {
            case 'CUSTOMER':
                return '/esang_delicacies/app/views/customer/customer_dashboard.php';
            case 'ADMIN':
                return '/esang_delicacies/app/views/admin/admin_dashboard.php';
            case 'ORDER_MANAGER':
                return '/esang_delicacies/app/views/order_manager/order_management.php';
            case 'RIDER':
                return '/esang_delicacies/app/views/rider/order_assignments.php';
            default:
                return '/esang_delicacies/public/Index.php';
        }
    }
    
    /**
     * Register new customer with profile hash
     */
    public function registerCustomer($customerData) {
        try {
            // Hash password
            $hashedPassword = password_hash($customerData['password'], PASSWORD_DEFAULT);
            
            // Insert customer
            $stmt = $this->mysqli->prepare("
                INSERT INTO customer (name, first_name, last_name, email, phone, address, password, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('sssssss',
                $customerData['username'],
                $customerData['first_name'],
                $customerData['last_name'],
                $customerData['email'],
                $customerData['phone'],
                $customerData['address'],
                $hashedPassword
            );
            
            if ($stmt->execute()) {
                $customerId = $stmt->insert_id;
                $stmt->close();
                
                // Generate and assign profile hash
                $profileHash = $this->profileManager->assignProfileHashToNewCustomer($customerId);
                
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'customer_id' => $customerId,
                    'profile_hash' => $profileHash
                ];
                
            } else {
                $stmt->close();
                throw new Exception('Failed to create customer account');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Change customer password using profile hash
     */
    public function changePassword($oldPassword, $newPassword) {
        if (!$this->validateSession('CUSTOMER')) {
            return [
                'success' => false,
                'message' => 'Not authenticated'
            ];
        }
        
        $profileHash = $_SESSION['profile_hash'];
        $customer = $this->profileManager->getCustomerByHash($profileHash);
        
        if (!$customer) {
            return [
                'success' => false,
                'message' => 'Customer not found'
            ];
        }
        
        // Verify old password
        $stmt = $this->mysqli->prepare("SELECT password FROM customer WHERE customerId = ?");
        $stmt->bind_param('i', $customer['customerId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $passwordData = $result->fetch_assoc();
        $stmt->close();
        
        if (!$passwordData || !$this->verifyPassword($oldPassword, $passwordData, 'CUSTOMER')) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->mysqli->prepare("UPDATE customer SET password = ? WHERE customerId = ?");
        $stmt->bind_param('si', $hashedPassword, $customer['customerId']);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the password change
            $this->profileManager->logProfileAccess($profileHash, $customer['customerId'], 'PASSWORD_CHANGE');
            
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
        } else {
            $stmt->close();
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }
    }
    
    /**
     * Get user access logs
     */
    public function getUserAccessLogs($limit = 20) {
        if (!$this->validateSession('CUSTOMER')) {
            return [];
        }
        
        $profileHash = $_SESSION['profile_hash'];
        
        $stmt = $this->mysqli->prepare("
            SELECT access_type, ip_address, access_time, additional_data
            FROM profile_access_log 
            WHERE profile_hash = ?
            ORDER BY access_time DESC
            LIMIT ?
        ");
        $stmt->bind_param('si', $profileHash, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = [
                'access_type' => $row['access_type'],
                'ip_address' => $row['ip_address'],
                'access_time' => $row['access_time'],
                'additional_data' => json_decode($row['additional_data'], true)
            ];
        }
        $stmt->close();
        
        return $logs;
    }
}
?>