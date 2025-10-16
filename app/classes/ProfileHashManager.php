<?php
/**
 * Profile Hash Manager
 * Handles secure hash-based user profile identification and isolation
 */

class ProfileHashManager {
    private $mysqli;
    private static $instance = null;
    
    private function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->mysqli = Database::getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate a unique profile hash for a new user
     */
    public function generateUniqueProfileHash($customerId = null) {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            // Generate hash using multiple entropy sources
            $hash = $this->createSecureHash($customerId);
            
            // Check if hash is unique
            if ($this->isHashUnique($hash)) {
                return $hash;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);
        
        throw new Exception('Failed to generate unique profile hash after ' . $maxAttempts . ' attempts');
    }
    
    /**
     * Create a secure hash using multiple entropy sources
     */
    private function createSecureHash($customerId = null) {
        $entropy = [
            $customerId ?? uniqid('', true),
            microtime(true),
            random_bytes(16),
            $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'esang_delicacies_salt_' . date('Y-m-d'),
            bin2hex(random_bytes(8))
        ];
        
        $rawHash = hash('sha256', implode('|', $entropy));
        
        // Use first 32 characters for manageable URLs
        return substr($rawHash, 0, 32);
    }
    
    /**
     * Check if a hash is unique in the database
     */
    private function isHashUnique($hash) {
        $stmt = $this->mysqli->prepare("SELECT COUNT(*) as count FROM customer WHERE profile_hash = ?");
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['count'] == 0;
    }
    
    /**
     * Get customer data by profile hash (secure access)
     */
    public function getCustomerByHash($profileHash) {
        $stmt = $this->mysqli->prepare("
            SELECT customerId, profile_hash, name, first_name, last_name, 
                   email, phone, address, created_at, updated_at
            FROM customer 
            WHERE profile_hash = ? AND profile_hash != ''
        ");
        $stmt->bind_param('s', $profileHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        return $customer;
    }
    
    /**
     * Get profile hash by customer ID (for internal use only)
     */
    public function getHashByCustomerId($customerId) {
        $stmt = $this->mysqli->prepare("SELECT profile_hash FROM customer WHERE customerId = ?");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['profile_hash'] : null;
    }
    
    /**
     * Create a new user session with profile hash
     */
    public function createUserSession($profileHash, $userRole = 'CUSTOMER') {
        $customer = $this->getCustomerByHash($profileHash);
        if (!$customer) {
            throw new Exception('Invalid profile hash');
        }
        
        $sessionId = $this->generateSessionId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Clean up expired sessions first
        $this->cleanupExpiredSessions();
        
        // Create new session
        $stmt = $this->mysqli->prepare("
            INSERT INTO user_sessions 
            (session_id, profile_hash, customer_id, user_role, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        $stmt->bind_param('ssiss', 
            $sessionId, 
            $profileHash, 
            $customer['customerId'], 
            $userRole, 
            $ipAddress, 
            $userAgent
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the login
            $this->logProfileAccess($profileHash, $customer['customerId'], 'LOGIN');
            
            return [
                'session_id' => $sessionId,
                'profile_hash' => $profileHash,
                'customer' => $customer,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ];
        } else {
            $stmt->close();
            throw new Exception('Failed to create user session');
        }
    }
    
    /**
     * Validate session with profile hash (for WebSocket authentication)
     */
    public function validateSession($sessionId, $profileHash) {
        $stmt = $this->mysqli->prepare("
            SELECT us.*, c.customerId, c.profile_hash
            FROM user_sessions us
            JOIN customer c ON us.customer_id = c.customerId
            WHERE us.session_id = ? 
            AND c.profile_hash = ?
            AND us.is_active = TRUE 
            AND us.expires_at > NOW()
        ");
        $stmt->bind_param('ss', $sessionId, $profileHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        if ($session) {
            // Update last activity
            $this->updateSessionActivity($sessionId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate and refresh user session
     */
    public function validateUserSession($sessionId) {
        $stmt = $this->mysqli->prepare("
            SELECT us.*, c.customerId, c.profile_hash, c.name, c.first_name, c.last_name, c.email
            FROM user_sessions us
            JOIN customer c ON us.customer_id = c.customerId
            WHERE us.session_id = ? 
            AND us.is_active = TRUE 
            AND us.expires_at > NOW()
        ");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        if ($session) {
            // Update last activity
            $this->updateSessionActivity($sessionId);
            return $session;
        }
        
        return false;
    }
    
    /**
     * Generate secure session ID
     */
    private function generateSessionId() {
        return hash('sha256', uniqid('session_', true) . random_bytes(32) . microtime(true));
    }
    
    /**
     * Update session last activity
     */
    private function updateSessionActivity($sessionId) {
        $stmt = $this->mysqli->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?");
        $stmt->bind_param('s', $sessionId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Logout user session
     */
    public function logoutUserSession($sessionId) {
        // Get session info for logging
        $session = $this->validateUserSession($sessionId);
        
        if ($session) {
            // Mark session as inactive
            $stmt = $this->mysqli->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?");
            $stmt->bind_param('s', $sessionId);
            $stmt->execute();
            $stmt->close();
            
            // Log the logout
            $this->logProfileAccess($session['profile_hash'], $session['customerId'], 'LOGOUT');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions() {
        $stmt = $this->mysqli->prepare("DELETE FROM user_sessions WHERE expires_at < NOW() OR is_active = FALSE");
        $stmt->execute();
        $deletedCount = $stmt->affected_rows;
        $stmt->close();
        
        return $deletedCount;
    }
    
    /**
     * Log profile access for security auditing
     */
    public function logProfileAccess($profileHash, $customerId, $accessType, $additionalData = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $additionalDataJson = $additionalData ? json_encode($additionalData) : null;
        
        $stmt = $this->mysqli->prepare("
            INSERT INTO profile_access_log 
            (profile_hash, customer_id, access_type, ip_address, user_agent, additional_data)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sissss', 
            $profileHash, 
            $customerId, 
            $accessType, 
            $ipAddress, 
            $userAgent, 
            $additionalDataJson
        );
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get customer orders using profile hash (secure access)
     */
    public function getCustomerOrders($profileHash, $limit = 50, $offset = 0) {
        $stmt = $this->mysqli->prepare("
            SELECT o.*, c.name as customer_name, c.first_name, c.last_name, c.email
            FROM orders o
            JOIN customer c ON o.customer_id = c.customerId
            WHERE c.profile_hash = ?
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('sii', $profileHash, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        
        // Log the access
        if (!empty($orders)) {
            $customer = $this->getCustomerByHash($profileHash);
            if ($customer) {
                $this->logProfileAccess($profileHash, $customer['customerId'], 'VIEW_ORDERS', [
                    'orders_count' => count($orders)
                ]);
            }
        }
        
        return $orders;
    }
    
    /**
     * Get specific order by profile hash and order ID (secure access)
     */
    public function getOrderByHashAndId($profileHash, $orderId) {
        $stmt = $this->mysqli->prepare("
            SELECT o.*, c.name as customer_name, c.first_name, c.last_name, c.email, c.phone
            FROM orders o
            JOIN customer c ON o.customer_id = c.customerId
            WHERE c.profile_hash = ? AND o.order_id = ?
        ");
        $stmt->bind_param('si', $profileHash, $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        return $order;
    }
    
    /**
     * Update customer profile using profile hash
     */
    public function updateCustomerProfile($profileHash, $profileData) {
        $customer = $this->getCustomerByHash($profileHash);
        if (!$customer) {
            throw new Exception('Invalid profile hash');
        }
        
        $allowedFields = ['name', 'first_name', 'last_name', 'email', 'phone', 'address'];
        $updateFields = [];
        $values = [];
        $types = '';
        
        foreach ($allowedFields as $field) {
            if (isset($profileData[$field])) {
                $updateFields[] = "$field = ?";
                $values[] = $profileData[$field];
                $types .= 's';
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No valid fields to update');
        }
        
        // Add profile hash to the end for WHERE clause
        $values[] = $profileHash;
        $types .= 's';
        
        $sql = "UPDATE customer SET " . implode(', ', $updateFields) . " WHERE profile_hash = ?";
        
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Log the update
            $this->logProfileAccess($profileHash, $customer['customerId'], 'UPDATE_PROFILE', [
                'updated_fields' => array_keys(array_intersect_key($profileData, array_flip($allowedFields)))
            ]);
            
            return true;
        } else {
            $stmt->close();
            throw new Exception('Failed to update profile');
        }
    }
    
    /**
     * Generate profile hash for new registration
     */
    public function assignProfileHashToNewCustomer($customerId) {
        $hash = $this->generateUniqueProfileHash($customerId);
        
        $stmt = $this->mysqli->prepare("UPDATE customer SET profile_hash = ? WHERE customerId = ?");
        $stmt->bind_param('si', $hash, $customerId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return $hash;
        } else {
            $stmt->close();
            throw new Exception('Failed to assign profile hash');
        }
    }
    
    /**
     * Get profile access statistics (for admin use)
     */
    public function getProfileStats($profileHash) {
        $customer = $this->getCustomerByHash($profileHash);
        if (!$customer) {
            return null;
        }
        
        // Get various statistics
        $stats = [];
        
        // Order count
        $stmt = $this->mysqli->prepare("
            SELECT COUNT(*) as order_count, 
                   COALESCE(SUM(total_amount), 0) as total_spent,
                   MAX(created_at) as last_order_date
            FROM orders 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('i', $customer['customerId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderStats = $result->fetch_assoc();
        $stmt->close();
        
        // Login count
        $stmt = $this->mysqli->prepare("
            SELECT COUNT(*) as login_count, MAX(access_time) as last_login
            FROM profile_access_log 
            WHERE profile_hash = ? AND access_type = 'LOGIN'
        ");
        $stmt->bind_param('s', $profileHash);
        $stmt->execute();
        $result = $stmt->get_result();
        $loginStats = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'profile_hash' => $profileHash,
            'customer_info' => $customer,
            'order_stats' => $orderStats,
            'login_stats' => $loginStats
        ];
    }
}
?>