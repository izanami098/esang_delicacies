<?php
/**
 * Database setup script for Esang Delicacies Order Management System
 * This script creates all necessary tables with the correct structure
 * Run this script once to set up your database properly
 */

// Load database connection
require_once __DIR__ . '/app/config/database.php';

echo "Setting up Esang Delicacies database...\n";

try {
    $db = Database::getConnection();
    
    // Create customer table
    $db->query("CREATE TABLE IF NOT EXISTS customer (
        customerId INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    // Create rider table  
    $db->query("CREATE TABLE IF NOT EXISTS rider (
        empId INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE,
        phone VARCHAR(20),
        status ENUM('active', 'inactive', 'busy', 'offline') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    // Create products table
    $db->query("CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        category VARCHAR(100),
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    // Create orders table with all required columns
    $db->query("CREATE TABLE IF NOT EXISTS orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        rider_id INT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'confirmed', 'preparing', 'ready', 'on_delivery', 'completed', 'cancelled') DEFAULT 'pending',
        payment_method ENUM('Cash on Delivery', 'GCash', 'Bank Transfer') DEFAULT 'Cash on Delivery',
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        payment_verified BOOLEAN DEFAULT FALSE,
        payment_screenshot_path VARCHAR(500) NULL,
        payment_screenshot_original_name VARCHAR(255) NULL,
        delivery_address TEXT NOT NULL,
        customer_phone VARCHAR(20),
        special_instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE,
        FOREIGN KEY (rider_id) REFERENCES rider(empId) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    
    // Create order_items table
    $db->query("CREATE TABLE IF NOT EXISTS order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    
    echo "✓ Database tables created successfully!\n";
    
    // Insert sample data
    echo "Creating sample data...\n";
    
    // Insert sample customers
    $db->query("INSERT IGNORE INTO customer (customerId, name, email, phone, address) VALUES 
        (1, 'John Doe', 'john@example.com', '09123456789', '123 Main St, Manila'),
        (2, 'Jane Smith', 'jane@example.com', '09987654321', '456 Oak Ave, Quezon City'),
        (3, 'Bob Johnson', 'bob@example.com', '09555666777', '789 Pine St, Makati')");
    
    // Insert sample riders
    $db->query("INSERT IGNORE INTO rider (empId, name, email, phone, status) VALUES 
        (1, 'Mike Wilson', 'mike@rider.com', '09111222333', 'active'),
        (2, 'Sarah Connor', 'sarah@rider.com', '09444555666', 'active'),
        (3, 'Tom Hardy', 'tom@rider.com', '09777888999', 'offline')");
    
    // Insert sample products
    $db->query("INSERT IGNORE INTO products (product_id, product_name, description, price, category) VALUES 
        (1, 'Lechon Kawali', 'Crispy pork belly with rice', 250.00, 'Main Course'),
        (2, 'Adobo Combo', 'Chicken and pork adobo with rice', 180.00, 'Main Course'),
        (3, 'Sisig', 'Sizzling pork sisig', 220.00, 'Appetizer'),
        (4, 'Lumpia Shanghai', '10 pieces spring rolls', 120.00, 'Appetizer'),
        (5, 'Halo-Halo', 'Filipino mixed dessert', 95.00, 'Dessert')");
    
    // Insert sample orders with different statuses
    $db->query("INSERT IGNORE INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES 
        (1, 1, 1, 430.00, 'pending', 'Cash on Delivery', TRUE, '123 Main St, Manila', '09123456789'),
        (2, 2, NULL, 300.00, 'pending', 'GCash', FALSE, '456 Oak Ave, Quezon City', '09987654321'),
        (3, 3, 2, 515.00, 'preparing', 'Bank Transfer', TRUE, '789 Pine St, Makati', '09555666777'),
        (4, 1, 2, 220.00, 'on_delivery', 'Cash on Delivery', TRUE, '123 Main St, Manila', '09123456789'),
        (5, 2, 1, 375.00, 'completed', 'GCash', TRUE, '456 Oak Ave, Quezon City', '09987654321')");
    
    // Insert sample order items
    $db->query("INSERT IGNORE INTO order_items (order_id, product_id, quantity, price) VALUES 
        (1, 1, 1, 250.00), (1, 2, 1, 180.00),
        (2, 3, 1, 220.00), (2, 5, 1, 80.00),
        (3, 1, 2, 250.00), (3, 4, 1, 120.00),
        (4, 3, 1, 220.00),
        (5, 2, 2, 180.00), (5, 4, 1, 120.00)");
    
    echo "✓ Sample data created successfully!\n";
    echo "Database setup completed!\n\n";
    echo "Sample orders created:\n";
    echo "- Order #1: Pending (Cash on Delivery)\n";
    echo "- Order #2: Pending (GCash - payment not verified)\n";
    echo "- Order #3: Preparing (Bank Transfer - payment verified)\n";
    echo "- Order #4: On Delivery (Cash on Delivery)\n";
    echo "- Order #5: Completed (GCash - payment verified)\n\n";
    echo "You can now view these orders in your Order Management system!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>