<?php
/**
 * Check table structures and add proper sample data
 */

require_once __DIR__ . '/app/config/database.php';

echo "Checking table structures and fixing sample data...\n\n";

try {
    $db = Database::getConnection();
    
    // Check users table structure
    echo "Users table structure:\n";
    $result = $db->query("DESCRIBE users");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
    
    // Check customer table structure
    echo "Customer table structure:\n";
    $result = $db->query("DESCRIBE customer");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
    
    // Disable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Disabled foreign key checks\n";
    
    // Clear existing sample data
    $db->query("DELETE FROM order_items WHERE order_id IN (1,2,3,4,5)");
    $db->query("DELETE FROM orders WHERE order_id IN (1,2,3,4,5)");
    echo "✓ Cleared existing sample orders\n";
    
    // Add sample users to users table (adjust based on actual columns)
    // Let's first check if we have the right columns and add users properly
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'username'");
    $hasUsername = $result->num_rows > 0;
    
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'email'");
    $hasEmail = $result->num_rows > 0;
    
    if ($hasUsername && $hasEmail) {
        // Use username and email columns
        $db->query("INSERT IGNORE INTO users (user_id, username, email, user_type, created_at) VALUES 
                    (11, 'mike_wilson', 'mike@rider.com', 'rider', NOW()),
                    (12, 'sarah_connor', 'sarah@rider.com', 'rider', NOW()),
                    (13, 'tom_hardy', 'tom@rider.com', 'rider', NOW())");
        echo "✓ Sample riders added to users table\n";
    } else {
        echo "⚠ Users table structure different than expected, skipping user inserts\n";
    }
    
    // Ensure customer data exists
    $db->query("INSERT IGNORE INTO customer (customerId, name, email, phone, address) VALUES 
                (1, 'John Doe', 'john@example.com', '09123456789', '123 Main St, Manila'),
                (2, 'Jane Smith', 'jane@example.com', '09987654321', '456 Oak Ave, Quezon City'),
                (3, 'Bob Johnson', 'bob@example.com', '09555666777', '789 Pine St, Makati')");
    echo "✓ Sample customers added\n";
    
    // Add sample orders (use NULL for rider_id if riders don't exist in users table)\n";
    $stmt = $db->prepare("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $orders = [
        [1, 1, null, 430.00, 'pending', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [2, 2, null, 300.00, 'pending', 'GCash', 0, '456 Oak Ave, Quezon City', '09987654321'],
        [3, 3, null, 515.00, 'preparing', 'Bank Transfer', 1, '789 Pine St, Makati', '09555666777'],
        [4, 1, null, 220.00, 'out_for_delivery', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [5, 2, null, 375.00, 'delivered', 'GCash', 1, '456 Oak Ave, Quezon City', '09987654321']
    ];
    
    foreach ($orders as $order) {
        $stmt->bind_param("iiidsssss", ...$order);
        $stmt->execute();
    }
    echo "✓ Sample orders added (without rider assignments for now)\n";
    
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
    
    echo "\n✅ Database setup completed successfully!\n\n";
    echo "Sample orders created:\n";
    echo "- Order #1: Pending (Cash on Delivery)\n";
    echo "- Order #2: Pending (GCash - payment not verified)\n";
    echo "- Order #3: Preparing (Bank Transfer - payment verified)\n";
    echo "- Order #4: Out for Delivery (Cash on Delivery)\n";
    echo "- Order #5: Delivered (GCash - payment verified)\n\n";
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