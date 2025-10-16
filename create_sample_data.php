<?php
/**
 * Create sample data for demonstrating the rider module
 * This script creates sample orders that riders can see and accept
 */

require_once 'includes/db.php';

try {
    // Check if we already have sample data
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $orderCount = $stmt->fetchColumn();
    
    if ($orderCount > 0) {
        echo "Sample data already exists. Order count: $orderCount\n";
        exit;
    }
    
    // Get a customer ID to use for orders
    $stmt = $pdo->query("SELECT customer_id FROM customers LIMIT 1");
    $customer = $stmt->fetch();
    
    if (!$customer) {
        echo "No customers found. Please ensure database is initialized.\n";
        exit;
    }
    
    $customerId = $customer['customer_id'];
    
    // Create sample orders
    $sampleOrders = [
        [
            'pickup_address' => 'Esang Delicacies Main Branch, City Mall, Talamban',
            'delivery_address' => '123 Lahug Street, Lahug, Cebu City',
            'customer_name' => 'John Doe',
            'customer_phone' => '09171234567',
            'total_amount' => 450.00,
            'delivery_fee' => 50.00,
            'special_instructions' => 'Please call when you arrive. Gate is brown in color.',
            'items' => [
                ['item_name' => 'Lechon Paksiw', 'quantity' => 2, 'unit_price' => 150.00, 'subtotal' => 300.00],
                ['item_name' => 'Pork Adobo', 'quantity' => 1, 'unit_price' => 100.00, 'subtotal' => 100.00],
                ['item_name' => 'Rice', 'quantity' => 1, 'unit_price' => 50.00, 'subtotal' => 50.00]
            ]
        ],
        [
            'pickup_address' => 'Esang Delicacies Main Branch, City Mall, Talamban',
            'delivery_address' => '456 IT Park Avenue, Lahug, Cebu City',
            'customer_name' => 'Maria Santos',
            'customer_phone' => '09987654321',
            'total_amount' => 750.00,
            'delivery_fee' => 60.00,
            'special_instructions' => 'Office delivery - Building A, 5th floor',
            'items' => [
                ['item_name' => 'Crispy Pata', 'quantity' => 1, 'unit_price' => 450.00, 'subtotal' => 450.00],
                ['item_name' => 'Kare-kare', 'quantity' => 1, 'unit_price' => 200.00, 'subtotal' => 200.00],
                ['item_name' => 'Rice', 'quantity' => 2, 'unit_price' => 50.00, 'subtotal' => 100.00]
            ]
        ],
        [
            'pickup_address' => 'Esang Delicacies Main Branch, City Mall, Talamban',
            'delivery_address' => '789 Capitol Site, Cebu City',
            'customer_name' => 'Carlos Rodriguez',
            'customer_phone' => '09123456789',
            'total_amount' => 320.00,
            'delivery_fee' => 40.00,
            'special_instructions' => null,
            'items' => [
                ['item_name' => 'Chicken Inasal', 'quantity' => 2, 'unit_price' => 120.00, 'subtotal' => 240.00],
                ['item_name' => 'Rice', 'quantity' => 2, 'unit_price' => 40.00, 'subtotal' => 80.00]
            ]
        ],
        [
            'pickup_address' => 'Esang Delicacies Main Branch, City Mall, Talamban',
            'delivery_address' => '321 Banilad Road, Banilad, Cebu City',
            'customer_name' => 'Ana dela Cruz',
            'customer_phone' => '09876543210',
            'total_amount' => 580.00,
            'delivery_fee' => 70.00,
            'special_instructions' => 'Please deliver to the guardhouse. Tell them it\'s for unit 205.',
            'items' => [
                ['item_name' => 'Beef Caldereta', 'quantity' => 1, 'unit_price' => 280.00, 'subtotal' => 280.00],
                ['item_name' => 'Pork Sisig', 'quantity' => 1, 'unit_price' => 180.00, 'subtotal' => 180.00],
                ['item_name' => 'Rice', 'quantity' => 3, 'unit_price' => 40.00, 'subtotal' => 120.00]
            ]
        ],
        [
            'pickup_address' => 'Esang Delicacies Main Branch, City Mall, Talamban',
            'delivery_address' => '555 Colon Street, Downtown, Cebu City',
            'customer_name' => 'Roberto Tan',
            'customer_phone' => '09192837465',
            'total_amount' => 290.00,
            'delivery_fee' => 30.00,
            'special_instructions' => 'Cash payment. No change needed.',
            'items' => [
                ['item_name' => 'Pancit Canton', 'quantity' => 1, 'unit_price' => 150.00, 'subtotal' => 150.00],
                ['item_name' => 'Lumpia', 'quantity' => 10, 'unit_price' => 14.00, 'subtotal' => 140.00]
            ]
        ]
    ];
    
    $pdo->beginTransaction();
    
    foreach ($sampleOrders as $orderData) {
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                customerId, pickup_address, delivery_address, customer_name, 
                customer_phone, total_amount, delivery_fee, order_status, 
                payment_status, special_instructions, estimated_delivery_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ready_for_delivery', 'completed', ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
        ");
        
        $stmt->execute([
            $customerId,
            $orderData['pickup_address'],
            $orderData['delivery_address'],
            $orderData['customer_name'],
            $orderData['customer_phone'],
            $orderData['total_amount'],
            $orderData['delivery_fee'],
            $orderData['special_instructions']
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        foreach ($orderData['items'] as $item) {
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, item_name, quantity, unit_price, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $itemStmt->execute([
                $orderId,
                $item['item_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            ]);
        }
        
        echo "Created order #$orderId for {$orderData['customer_name']}\n";
    }
    
    $pdo->commit();
    
    echo "\nSample data created successfully!\n";
    echo "Created " . count($sampleOrders) . " sample orders ready for delivery.\n";
    echo "\nYou can now:\n";
    echo "1. Login as rider using: admin@esang.com / admin123\n";
    echo "2. View available orders in the rider dashboard\n";
    echo "3. Accept orders and test the delivery workflow\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error creating sample data: " . $e->getMessage() . "\n";
}
?>