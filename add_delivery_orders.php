<?php
/**
 * Add sample delivery orders to demonstrate the delivery tab functionality
 */

require_once __DIR__ . '/app/config/database.php';

echo "Adding sample delivery orders...\n\n";

try {
    $db = Database::getConnection();
    
    // Disable foreign key checks temporarily
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Disabled foreign key checks\n";
    
    // Add some out for delivery orders
    $stmt = $db->prepare("INSERT INTO orders (order_id, customer_id, rider_id, total_amount, status, payment_method, payment_verified, delivery_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $deliveryOrders = [
        [201, 1, null, 280.00, 'out_for_delivery', 'Cash on Delivery', 1, '123 Main St, Manila', '09123456789'],
        [202, 2, null, 450.00, 'on_delivery', 'GCash', 1, '456 Oak Ave, Quezon City', '09987654321']
    ];
    
    foreach ($deliveryOrders as $order) {
        $stmt->bind_param("iiidsssss", ...$order);
        $stmt->execute();
    }
    echo "✓ Sample delivery orders added\n";
    
    // Add corresponding order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    $items = [
        [201, 1, 1, 250.00], [201, 5, 1, 30.00], // Lechon Kawali + Halo-Halo
        [202, 2, 2, 180.00], [202, 3, 1, 220.00] // 2 Adobo Combo + Sisig
    ];
    
    foreach ($items as $item) {
        $stmt->bind_param("iiid", ...$item);
        $stmt->execute();
    }
    echo "✓ Sample delivery order items added\n";
    
    // Re-enable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Re-enabled foreign key checks\n";
    
    echo "\n✅ Sample delivery orders added successfully!\n\n";
    echo "Added delivery orders:\n";
    echo "- Order #201: Out for Delivery (Cash on Delivery)\n";
    echo "- Order #202: On Delivery (GCash - verified)\n\n";
    echo "🚚 You can now view these in the Out for Delivery tab!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    // Re-enable foreign key checks even on error
    try {
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $ignore) {}
    exit(1);
}
?>