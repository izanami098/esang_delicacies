<?php
/**
 * Database update script for existing Esang Delicacies database
 * This script adds missing columns to existing tables
 */

require_once __DIR__ . '/app/config/database.php';

echo "Updating Esang Delicacies database...\n";

try {
    $db = Database::getConnection();
    
    // Check and add missing columns to orders table
    echo "Checking orders table structure...\n";
    
    // Add payment_verified column if it doesn't exist
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'payment_verified'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN payment_verified BOOLEAN DEFAULT FALSE");
        echo "✓ Added payment_verified column to orders table\n";
    } else {
        echo "✓ payment_verified column already exists\n";
    }
    
    // Add payment_screenshot_path column if it doesn't exist
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'payment_screenshot_path'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN payment_screenshot_path VARCHAR(500) NULL");
        echo "✓ Added payment_screenshot_path column to orders table\n";
    } else {
        echo "✓ payment_screenshot_path column already exists\n";
    }
    
    // Add payment_screenshot_original_name column if it doesn't exist
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'payment_screenshot_original_name'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN payment_screenshot_original_name VARCHAR(255) NULL");
        echo "✓ Added payment_screenshot_original_name column to orders table\n";
    } else {
        echo "✓ payment_screenshot_original_name column already exists\n";
    }
    
    // Update payment_method enum to match what the frontend expects
    $db->query("ALTER TABLE orders MODIFY payment_method ENUM('Cash on Delivery', 'GCash', 'Bank Transfer', 'cash', 'gcash', 'paymaya', 'card') DEFAULT 'Cash on Delivery'");
    echo "✓ Updated payment_method enum values\n";
    
    // Create customer table if it doesn't exist (with correct column name)
    $result = $db->query("SHOW TABLES LIKE 'customer'");
    if ($result->num_rows == 0) {
        $db->query("CREATE TABLE customer (
            customerId INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            phone VARCHAR(20),
            address TEXT,
            city VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        echo "✓ Created customer table\n";
    } else {
        echo "✓ customer table already exists\n";
    }
    
    // Create rider table if it doesn't exist (with correct column name)
    $result = $db->query("SHOW TABLES LIKE 'rider'");
    if ($result->num_rows == 0) {
        $db->query("CREATE TABLE rider (
            empId INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) UNIQUE,
            phone VARCHAR(20),
            status ENUM('active', 'inactive', 'busy', 'offline') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        echo "✓ Created rider table\n";
    } else {
        echo "✓ rider table already exists\n";
    }
    
    // Create products table if it doesn't exist
    $result = $db->query("SHOW TABLES LIKE 'products'");
    if ($result->num_rows == 0) {
        $db->query("CREATE TABLE products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            category VARCHAR(100),
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        echo "✓ Created products table\n";
    } else {
        echo "✓ products table already exists\n";
    }
    
    echo "\n--- Adding sample data ---\n";
    
    // Insert sample customers
    $db->query("INSERT IGNORE INTO customer (customerId, name, email, phone, address) VALUES 
        (1, 'John Doe', 'john@example.com', '09123456789', '123 Main St, Manila'),
        (2, 'Jane Smith', 'jane@example.com', '09987654321', '456 Oak Ave, Quezon City'),
        (3, 'Bob Johnson', 'bob@example.com', '09555666777', '789 Pine St, Makati')");
    echo "✓ Sample customers added\n";
    
    // Insert sample riders
    $db->query("INSERT IGNORE INTO rider (empId, name, email, phone, status) VALUES 
        (1, 'Mike Wilson', 'mike@rider.com', '09111222333', 'active'),
        (2, 'Sarah Connor', 'sarah@rider.com', '09444555666', 'active'),
        (3, 'Tom Hardy', 'tom@rider.com', '09777888999', 'offline')");
    echo "✓ Sample riders added\n";
    
    // Insert sample products
    $db->query("INSERT IGNORE INTO products (product_id, product_name, description, price, category) VALUES 
        (1, 'Lechon Kawali', 'Crispy pork belly with rice', 250.00, 'Main Course'),
        (2, 'Adobo Combo', 'Chicken and pork adobo with rice', 180.00, 'Main Course'),
        (3, 'Sisig', 'Sizzling pork sisig', 220.00, 'Appetizer'),
        (4, 'Lumpia Shanghai', '10 pieces spring rolls', 120.00, 'Appetizer'),
        (5, 'Halo-Halo', 'Filipino mixed dessert', 95.00, 'Dessert')");
    echo "✓ Sample products added\n";
    
    // Clear existing orders first to avoid conflicts
    $db->query("DELETE FROM order_items WHERE order_id IN (1,2,3,4,5)");
    $db->query("DELETE FROM orders WHERE order_id IN (1,2,3,4,5)");
    
    // Insert sample orders with different statuses
    $db->query("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES 
        (1, 1, 1, 430.00, 'pending', 'Cash on Delivery', TRUE, '123 Main St, Manila', '09123456789'),
        (2, 2, NULL, 300.00, 'pending', 'GCash', FALSE, '456 Oak Ave, Quezon City', '09987654321'),
        (3, 3, 2, 515.00, 'preparing', 'Bank Transfer', TRUE, '789 Pine St, Makati', '09555666777'),
        (4, 1, 2, 220.00, 'on_delivery', 'Cash on Delivery', TRUE, '123 Main St, Manila', '09123456789'),
        (5, 2, 1, 375.00, 'completed', 'GCash', TRUE, '456 Oak Ave, Quezon City', '09987654321')");
    echo "✓ Sample orders added\n";
    
    // Insert sample order items
    $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES 
        (1, 1, 1, 250.00), (1, 2, 1, 180.00),
        (2, 3, 1, 220.00), (2, 5, 1, 80.00),
        (3, 1, 2, 250.00), (3, 4, 1, 120.00),
        (4, 3, 1, 220.00),
        (5, 2, 2, 180.00), (5, 4, 1, 120.00)");
    echo "✓ Sample order items added\n";
    
    echo "\n✅ Database update completed successfully!\n\n";
    echo "Sample orders created:\n";
    echo "- Order #1: Pending (Cash on Delivery) - Assigned to Mike Wilson\n";
    echo "- Order #2: Pending (GCash - payment not verified) - No rider assigned\n";
    echo "- Order #3: Preparing (Bank Transfer - payment verified) - Assigned to Sarah Connor\n";
    echo "- Order #4: On Delivery (Cash on Delivery) - Assigned to Sarah Connor\n";
    echo "- Order #5: Completed (GCash - payment verified) - Assigned to Mike Wilson\n\n";
    echo "🎉 You can now view these orders in your Order Management system!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>