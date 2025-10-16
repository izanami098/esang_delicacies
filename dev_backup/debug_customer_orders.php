<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $mysqli = Database::getConnection();
    
    echo "<h2>Customer Orders Debug Report</h2>";
    echo "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
    
    // Check both customers' orders
    $customerIds = [20, 21];
    
    foreach ($customerIds as $customerId) {
        echo "<h3>Customer ID: $customerId</h3>";
        
        // Check customer exists
        $stmt = $mysqli->prepare("SELECT customerId, name, first_name, last_name, email FROM customer WHERE customerId = ?");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        if (!$customer) {
            echo "<p style='color: red;'>❌ Customer does not exist!</p>";
            continue;
        }
        
        echo "<p>✅ Customer exists: " . ($customer['first_name'] ? $customer['first_name'] . ' ' . $customer['last_name'] : $customer['name']) . "</p>";
        echo "<p>Email: " . $customer['email'] . "</p>";
        
        // Check orders for this customer
        $stmt = $mysqli->prepare("
            SELECT order_id, customer_id, total_amount, status, order_type, 
                   payment_method, payment_status, created_at, updated_at
            FROM orders 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        
        if (empty($orders)) {
            echo "<p style='color: orange;'>⚠️ No orders found for this customer</p>";
        } else {
            echo "<p>✅ Found " . count($orders) . " order(s):</p>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Order ID</th><th>Status</th><th>Amount</th><th>Created</th><th>Updated</th></tr>";
            
            foreach ($orders as $order) {
                echo "<tr>";
                echo "<td>" . $order['order_id'] . "</td>";
                echo "<td>" . $order['status'] . "</td>";
                echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
                echo "<td>" . $order['created_at'] . "</td>";
                echo "<td>" . $order['updated_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check order items for the most recent order
            $latestOrder = $orders[0];
            echo "<h4>Items for Order #" . $latestOrder['order_id'] . ":</h4>";
            
            $stmt = $mysqli->prepare("
                SELECT oi.*, p.product_name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?
            ");
            $stmt->bind_param('i', $latestOrder['order_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $stmt->close();
            
            if (empty($items)) {
                echo "<p style='color: red;'>❌ No items found for this order!</p>";
            } else {
                echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>";
                
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>" . ($item['product_name'] ?? 'Product #' . $item['product_id']) . "</td>";
                    echo "<td>" . $item['quantity'] . "</td>";
                    echo "<td>₱" . number_format($item['price'], 2) . "</td>";
                    echo "<td>₱" . number_format($item['subtotal'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "<hr>";
    }
    
    // Test the API endpoint directly
    echo "<h3>API Endpoint Tests</h3>";
    
    foreach ($customerIds as $customerId) {
        echo "<h4>Testing API for Customer $customerId</h4>";
        
        // Simulate API call
        $_SESSION['role'] = 'CUSTOMER';
        $_SESSION['customerId'] = $customerId;
        
        $stmt = $mysqli->prepare("
            SELECT 
                o.order_id,
                o.customer_id,
                o.total_amount,
                o.status,
                o.order_type,
                o.delivery_address,
                o.special_instructions,
                o.payment_method,
                o.payment_status,
                o.rider_id,
                o.notes,
                o.created_at,
                o.updated_at,
                r.name as rider_name,
                r.phone as rider_phone,
                r.plateNum as rider_plate,
                r.email as rider_email
            FROM orders o
            LEFT JOIN rider r ON o.rider_id = r.empId
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            echo "<p style='color: red;'>❌ API would return: No orders found</p>";
        } else {
            echo "<p style='color: green;'>✅ API would return order #" . $order['order_id'] . " with status: " . $order['status'] . "</p>";
            
            // Check progress step calculation
            $progressSteps = [
                'pending' => 0,
                'confirmed' => 1,
                'preparing' => 2,
                'ready_for_pickup' => 3,
                'out_for_delivery' => 3,
                'delivered' => 4,
                'cancelled' => -1
            ];
            
            $currentStep = $progressSteps[$order['status']] ?? 0;
            echo "<p>Progress step would be: $currentStep (Status: " . $order['status'] . ")</p>";
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>