<?php
/**
 * Database fix script - check current structure and add missing columns
 */

require_once __DIR__ . '/app/config/database.php';

echo "Checking and fixing Esang Delicacies database...\n\n";

try {
    $db = Database::getConnection();
    
    // Show current orders table structure
    echo "Current orders table structure:\n";
    $result = $db->query("DESCRIBE orders");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
    
    // Add customer_phone column if it doesn't exist
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_phone'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20)");
        echo "✓ Added customer_phone column to orders table\n";
    } else {
        echo "✓ customer_phone column already exists\n";
    }
    
    // Add delivery_address column if it doesn't exist  
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN delivery_address TEXT");
        echo "✓ Added delivery_address column to orders table\n";
    } else {
        echo "✓ delivery_address column already exists\n";
    }
    
    // Add customer_id column if it doesn't exist but customerId does
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'customer_id'");
    $result2 = $db->query("SHOW COLUMNS FROM orders LIKE 'customerId'");
    
    if ($result->num_rows == 0 && $result2->num_rows > 0) {
        // We have customerId but need customer_id for the API
        $db->query("ALTER TABLE orders ADD COLUMN customer_id INT");
        $db->query("UPDATE orders SET customer_id = customerId WHERE customer_id IS NULL");
        echo "✓ Added customer_id column and copied data from customerId\n";
    } else if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN customer_id INT");
        echo "✓ Added customer_id column to orders table\n";
    } else {
        echo "✓ customer_id column already exists\n";
    }
    
    // Fix rider_id if needed
    $result = $db->query("SHOW COLUMNS FROM orders LIKE 'rider_id'");
    if ($result->num_rows == 0) {
        $db->query("ALTER TABLE orders ADD COLUMN rider_id INT NULL");
        echo "✓ Added rider_id column to orders table\n";
    } else {
        echo "✓ rider_id column already exists\n";
    }
    
    echo "\n--- Clearing existing sample data ---\n";
    
    // Clear existing orders first to avoid conflicts
    $db->query("DELETE FROM order_items WHERE order_id IN (1,2,3,4,5)");
    $db->query("DELETE FROM orders WHERE order_id IN (1,2,3,4,5)");
    echo "✓ Cleared existing sample orders\n";
    
    echo "\n--- Adding fresh sample data ---\n";
    
    // Insert sample orders with correct column names
    $stmt = $db->prepare("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $orders = [
        [1, 1, 1, 430.00, 'pending', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [2, 2, null, 300.00, 'pending', 'GCash', 0, '456 Oak Ave, Quezon City', '09987654321'],
        [3, 3, 2, 515.00, 'preparing', 'Bank Transfer', 1, '789 Pine St, Makati', '09555666777'],
        [4, 1, 2, 220.00, 'on_delivery', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [5, 2, 1, 375.00, 'completed', 'GCash', 1, '456 Oak Ave, Quezon City', '09987654321']
    ];
    
    foreach ($orders as $order) {
        $stmt->bind_param("iiidsssss", ...$order);
        $stmt->execute();
    }
    echo "✓ Sample orders added\n";
    
    // Insert sample order items
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
    
    echo "\n✅ Database fix completed successfully!\n\n";
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