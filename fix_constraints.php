<?php
/**
 * Fix foreign key constraints and add sample data
 */

require_once __DIR__ . '/app/config/database.php';

echo "Fixing foreign key constraints and adding sample data...\n\n";

try {
    $db = Database::getConnection();
    
    // Show all tables to understand the current structure
    echo "Current tables in database:\n";
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    echo "\n";
    
    // Check foreign key constraints on orders table
    echo "Foreign key constraints on orders table:\n";
    $result = $db->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                         FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                         WHERE TABLE_NAME = 'orders' AND TABLE_SCHEMA = 'esangdel_esang_db'
                         AND REFERENCED_TABLE_NAME IS NOT NULL");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['CONSTRAINT_NAME']}: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
    echo "\n";
    
    // Disable foreign key checks temporarily
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Disabled foreign key checks\n";
    
    // Clear existing data
    $db->query("DELETE FROM order_items WHERE order_id IN (1,2,3,4,5)");
    $db->query("DELETE FROM orders WHERE order_id IN (1,2,3,4,5)");
    echo "✓ Cleared existing sample orders\n";
    
    // Insert users/customers (check if users table exists)
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "Users table exists, adding sample users...\n";
        $db->query("INSERT IGNORE INTO users (user_id, name, email, phone, user_type, created_at) VALUES 
                    (1, 'John Doe', 'john@example.com', '09123456789', 'customer', NOW()),
                    (2, 'Jane Smith', 'jane@example.com', '09987654321', 'customer', NOW()),
                    (3, 'Bob Johnson', 'bob@example.com', '09555666777', 'customer', NOW()),
                    (11, 'Mike Wilson', 'mike@rider.com', '09111222333', 'rider', NOW()),
                    (12, 'Sarah Connor', 'sarah@rider.com', '09444555666', 'rider', NOW()),
                    (13, 'Tom Hardy', 'tom@rider.com', '09777888999', 'rider', NOW())");
        echo "✓ Sample users added\n";
    }
    
    // Check if customer table exists
    $result = $db->query("SHOW TABLES LIKE 'customer'");
    if ($result->num_rows > 0) {
        echo "Customer table exists, adding sample customers...\n";
        $db->query("INSERT IGNORE INTO customer (customerId, name, email, phone, address) VALUES 
                    (1, 'John Doe', 'john@example.com', '09123456789', '123 Main St, Manila'),
                    (2, 'Jane Smith', 'jane@example.com', '09987654321', '456 Oak Ave, Quezon City'),  
                    (3, 'Bob Johnson', 'bob@example.com', '09555666777', '789 Pine St, Makati')");
        echo "✓ Sample customers added\n";
    }
    
    // Add sample orders with rider IDs that exist in users table (if it exists)
    $stmt = $db->prepare("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $orders = [
        [1, 1, 11, 430.00, 'pending', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [2, 2, null, 300.00, 'pending', 'GCash', 0, '456 Oak Ave, Quezon City', '09987654321'],
        [3, 3, 12, 515.00, 'preparing', 'Bank Transfer', 1, '789 Pine St, Makati', '09555666777'],
        [4, 1, 12, 220.00, 'out_for_delivery', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [5, 2, 11, 375.00, 'delivered', 'GCash', 1, '456 Oak Ave, Quezon City', '09987654321']
    ];
    
    foreach ($orders as $order) {
        $stmt->bind_param("iiidsssss", ...$order);
        $stmt->execute();
    }
    echo "✓ Sample orders added\n";
    
    // Add sample order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    $items = [
        [1, 1, 1, 250.00], [1, 2, 1, 180.00],
        [2, 3, 1, 220.00], [2, 5, 1, 80.00],
        [3, 1, 2, 250.00], [3, 4, 1, 120.00],
        [4, 3, 1, 220.00],
        [5, 2, 2, 180.00], [5, 4, 1, 120.00]
    ];
    
    foreach ($items as $item) {
        $stmt->bind_param("iiid", ...$item);
        $stmt->execute();
    }
    echo "✓ Sample order items added\n";
    
    // Re-enable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Re-enabled foreign key checks\n";
    
    echo "\n✅ Database fix completed successfully!\n\n";
    echo "Sample orders created:\n";
    echo "- Order #1: Pending (Cash on Delivery) - Rider ID 11\n";
    echo "- Order #2: Pending (GCash - payment not verified) - No rider assigned\n";
    echo "- Order #3: Preparing (Bank Transfer - payment verified) - Rider ID 12\n";
    echo "- Order #4: Out for Delivery (Cash on Delivery) - Rider ID 12\n";
    echo "- Order #5: Delivered (GCash - payment verified) - Rider ID 11\n\n";
    echo "🎉 You can now view these orders in your Order Management system!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    // Re-enable foreign key checks even on error
    try {
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignore) {}
    exit(1);
}
?>