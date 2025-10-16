<?php
/**
 * Database Setup Script for Profile Hash System
 * This script adds the necessary columns and tables for the profile hash system
 */

require_once 'app/views/_bootstrap.php';

echo "<h1>Profile Hash Database Setup</h1>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; margin: 20px;'>";

try {
    $db = db();
    
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }
    
    echo "<h3>Step 1: Check Current Customer Table Structure</h3>";
    
    // Check current table structure
    $result = $db->query("DESCRIBE customer");
    $columns = [];
    $hasProfileHash = false;
    
    echo "Current columns in customer table:<br>";
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "- {$row['Field']} ({$row['Type']})<br>";
        if ($row['Field'] === 'profile_hash') {
            $hasProfileHash = true;
        }
    }
    echo "<br>";
    
    // Add profile_hash column if it doesn't exist
    if (!$hasProfileHash) {
        echo "<h3>Step 2: Adding profile_hash Column</h3>";
        $sql = "ALTER TABLE customer ADD COLUMN profile_hash VARCHAR(32) DEFAULT NULL AFTER customerId";
        if ($db->query($sql)) {
            echo "✓ Added profile_hash column to customer table<br>";
        } else {
            throw new Exception("Failed to add profile_hash column: " . $db->error);
        }
        
        // Add index for better performance
        $sql = "ALTER TABLE customer ADD INDEX idx_profile_hash (profile_hash)";
        if ($db->query($sql)) {
            echo "✓ Added index on profile_hash column<br>";
        } else {
            echo "! Warning: Could not add index on profile_hash: " . $db->error . "<br>";
        }
        echo "<br>";
    } else {
        echo "<h3>Step 2: profile_hash Column Already Exists</h3>";
        echo "✓ profile_hash column is already present<br><br>";
    }
    
    // Check if user_sessions table exists
    echo "<h3>Step 3: Check/Create user_sessions Table</h3>";
    $result = $db->query("SHOW TABLES LIKE 'user_sessions'");
    if ($result->num_rows == 0) {
        // Create user_sessions table
        $sql = "
        CREATE TABLE user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL UNIQUE,
            profile_hash VARCHAR(32) NOT NULL,
            customer_id INT NOT NULL,
            user_role ENUM('CUSTOMER', 'ADMIN', 'CASHIER', 'RIDER', 'ORDER_MANAGER') DEFAULT 'CUSTOMER',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_session_id (session_id),
            INDEX idx_profile_hash (profile_hash),
            INDEX idx_customer_id (customer_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE
        )";
        
        if ($db->query($sql)) {
            echo "✓ Created user_sessions table<br>";
        } else {
            throw new Exception("Failed to create user_sessions table: " . $db->error);
        }
    } else {
        echo "✓ user_sessions table already exists<br>";
    }
    echo "<br>";
    
    // Check if profile_access_log table exists
    echo "<h3>Step 4: Check/Create profile_access_log Table</h3>";
    $result = $db->query("SHOW TABLES LIKE 'profile_access_log'");
    if ($result->num_rows == 0) {
        // Create profile_access_log table
        $sql = "
        CREATE TABLE profile_access_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            profile_hash VARCHAR(32) NOT NULL,
            customer_id INT NOT NULL,
            access_type ENUM('LOGIN', 'LOGOUT', 'VIEW_ORDERS', 'UPDATE_PROFILE', 'API_ACCESS', 'API_LOGOUT') NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            additional_data JSON,
            access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_profile_hash (profile_hash),
            INDEX idx_customer_id (customer_id),
            INDEX idx_access_type (access_type),
            INDEX idx_access_time (access_time),
            FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE
        )";
        
        if ($db->query($sql)) {
            echo "✓ Created profile_access_log table<br>";
        } else {
            throw new Exception("Failed to create profile_access_log table: " . $db->error);
        }
    } else {
        echo "✓ profile_access_log table already exists<br>";
    }
    echo "<br>";
    
    // Check if customer_notifications table exists (for WebSocket)
    echo "<h3>Step 5: Check/Create customer_notifications Table</h3>";
    $result = $db->query("SHOW TABLES LIKE 'customer_notifications'");
    if ($result->num_rows == 0) {
        $sql = "
        CREATE TABLE customer_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            notification_type ENUM('ORDER_UPDATE', 'PROMOTION', 'SYSTEM', 'REMINDER') DEFAULT 'SYSTEM',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_customer_id (customer_id),
            INDEX idx_order_id (order_id),
            INDEX idx_is_read (is_read),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE
        )";
        
        if ($db->query($sql)) {
            echo "✓ Created customer_notifications table<br>";
        } else {
            echo "! Warning: Could not create customer_notifications table: " . $db->error . "<br>";
        }
    } else {
        echo "✓ customer_notifications table already exists<br>";
    }
    echo "<br>";
    
    // Check if customer_websocket_connections table exists
    echo "<h3>Step 6: Check/Create customer_websocket_connections Table</h3>";
    $result = $db->query("SHOW TABLES LIKE 'customer_websocket_connections'");
    if ($result->num_rows == 0) {
        $sql = "
        CREATE TABLE customer_websocket_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            profile_hash VARCHAR(32) NOT NULL,
            connection_id VARCHAR(64) NOT NULL,
            session_id VARCHAR(64) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            connected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_customer_id (customer_id),
            INDEX idx_profile_hash (profile_hash),
            INDEX idx_connection_id (connection_id),
            INDEX idx_session_id (session_id),
            FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE
        )";
        
        if ($db->query($sql)) {
            echo "✓ Created customer_websocket_connections table<br>";
        } else {
            echo "! Warning: Could not create customer_websocket_connections table: " . $db->error . "<br>";
        }
    } else {
        echo "✓ customer_websocket_connections table already exists<br>";
    }
    echo "<br>";
    
    // Generate profile hashes for existing customers
    echo "<h3>Step 7: Generate Profile Hashes for Existing Customers</h3>";
    $result = $db->query("SELECT customerId, first_name, last_name, email FROM customer WHERE profile_hash IS NULL OR profile_hash = ''");
    $customersToUpdate = [];
    
    while ($row = $result->fetch_assoc()) {
        $customersToUpdate[] = $row;
    }
    
    if (count($customersToUpdate) > 0) {
        echo "Found " . count($customersToUpdate) . " customers without profile hashes:<br>";
        
        require_once 'app/classes/ProfileHashManager.php';
        $profileHashManager = ProfileHashManager::getInstance();
        
        foreach ($customersToUpdate as $customer) {
            try {
                $profileHash = $profileHashManager->assignProfileHashToNewCustomer($customer['customerId']);
                echo "✓ Generated profile hash for Customer ID {$customer['customerId']} ({$customer['first_name']} {$customer['last_name']}): " . substr($profileHash, 0, 16) . "...<br>";
            } catch (Exception $e) {
                echo "✗ Failed to generate profile hash for Customer ID {$customer['customerId']}: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "✓ All customers already have profile hashes<br>";
    }
    echo "<br>";
    
    // Final verification
    echo "<h3>Step 8: Final Verification</h3>";
    $result = $db->query("SELECT COUNT(*) as total_customers FROM customer");
    $totalCustomers = $result->fetch_assoc()['total_customers'];
    
    $result = $db->query("SELECT COUNT(*) as customers_with_hash FROM customer WHERE profile_hash IS NOT NULL AND profile_hash != ''");
    $customersWithHash = $result->fetch_assoc()['customers_with_hash'];
    
    echo "Total customers: {$totalCustomers}<br>";
    echo "Customers with profile hash: {$customersWithHash}<br>";
    
    if ($totalCustomers == $customersWithHash) {
        echo "✓ All customers have profile hashes!<br>";
    } else {
        echo "! " . ($totalCustomers - $customersWithHash) . " customers still need profile hashes<br>";
    }
    echo "<br>";
    
    // Show sample profile hashes
    echo "<h3>Sample Profile Hashes</h3>";
    $result = $db->query("SELECT customerId, first_name, last_name, email, profile_hash FROM customer WHERE profile_hash IS NOT NULL LIMIT 3");
    
    if ($result->num_rows > 0) {
        echo "Sample customers with profile hashes:<br>";
        while ($row = $result->fetch_assoc()) {
            echo "- ID {$row['customerId']}: {$row['first_name']} {$row['last_name']} → <code>{$row['profile_hash']}</code><br>";
        }
    }
    echo "<br>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<strong>✅ Database Setup Complete!</strong><br>";
    echo "Your profile hash system is now ready to use.<br>";
    echo "You can now test the login system at: <a href='app/views/auth/LogIn.php'>Login Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "</div>";

// Add styling
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1, h3 { color: #333; }
code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>";
?>