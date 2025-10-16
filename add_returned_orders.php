<?php
/**
 * Add sample returned orders to demonstrate the returned orders functionality
 */

require_once __DIR__ . '/app/config/database.php';

echo "Adding sample returned orders...\n\n";

try {
    $db = Database::getConnection();
    
    // Disable foreign key checks temporarily
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Disabled foreign key checks\n";
    
    // Add some cancelled/returned orders
    $stmt = $db->prepare("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $returnedOrders = [
        [101, 1, null, 180.00, 'cancelled', 'GCash', 0, '123 Main St, Manila', '09123456789'],
        [102, 2, null, 350.00, 'returned', 'Cash on Delivery', 1, '456 Oak Ave, Quezon City', '09987654321'],
        [103, 3, null, 220.00, 'cancelled', 'Bank Transfer', 0, '789 Pine St, Makati', '09555666777']
    ];
    
    foreach ($returnedOrders as $order) {
        $stmt->bind_param("iiidsssss", ...$order);
        $stmt->execute();
    }
    echo "✓ Sample returned/cancelled orders added\n";
    
    // Add corresponding order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    $items = [
        [101, 2, 1, 180.00], // Adobo Combo
        [102, 1, 1, 250.00], [102, 4, 1, 100.00], // Lechon Kawali + Lumpia
        [103, 3, 1, 220.00] // Sisig
    ];
    
    foreach ($items as $item) {
        $stmt->bind_param("iiid", ...$item);
        $stmt->execute();
    }
    echo "✓ Sample returned order items added\n";
    
    // Re-enable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Re-enabled foreign key checks\n";
    
    echo "\n✅ Sample returned orders added successfully!\n\n";
    echo "Added returned orders:\n";
    echo "- Order #101: Cancelled (GCash payment failed)\n";
    echo "- Order #102: Returned (Customer complaint about quality)\n";
    echo "- Order #103: Cancelled (Customer changed mind)\n\n";
    echo "🎉 You can now view these in the Returned Orders tab!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    // Re-enable foreign key checks even on error
    try {
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignore) {}
    exit(1);
}
?>