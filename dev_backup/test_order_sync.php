<?php
// Test script to verify order synchronization
header('Content-Type: text/html; charset=UTF-8');

// Check if we can connect to the database
echo "<h2>Order Synchronization Test</h2>";

try {
    require_once 'app/config/database.php';
    $mysqli = Database::getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Test the order counts API
    echo "<h3>Order Counts API Test</h3>";
    
    // Get raw order counts from database
    $query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
    $result = $mysqli->query($query);
    
    echo "<h4>Raw Database Status Counts:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    
    $totalOrders = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['status']}</td><td>{$row['count']}</td></tr>";
        $totalOrders += $row['count'];
    }
    echo "</table>";
    echo "<p><strong>Total Orders: {$totalOrders}</strong></p>";
    
    // Test the API endpoint
    echo "<h4>API Endpoint Test:</h4>";
    
    // Try multiple possible ports for XAMPP
    $possiblePorts = ['', ':8080', ':80', ':3000'];
    $apiResponse = null;
    $successfulUrl = null;
    
    foreach ($possiblePorts as $port) {
        $testUrl = "http://{$_SERVER['HTTP_HOST']}{$port}/esang_delicacies/public/api/get_order_counts.php";
        $apiResponse = @file_get_contents($testUrl);
        if ($apiResponse !== false) {
            $successfulUrl = $testUrl;
            break;
        }
    }
    
    $apiUrl = $successfulUrl ?? "http://{$_SERVER['HTTP_HOST']}/esang_delicacies/public/api/get_order_counts.php";
    
    if ($apiResponse) {
        $apiData = json_decode($apiResponse, true);
        if ($apiData && $apiData['success']) {
            echo "<p style='color: green;'>✓ API endpoint working</p>";
            echo "<pre>" . json_encode($apiData, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color: red;'>✗ API returned error</p>";
            echo "<pre>{$apiResponse}</pre>";
        }
    } else {
        echo "<p style='color: red;'>✗ Could not reach API endpoint</p>";
        echo "<p>URL tested: {$apiUrl}</p>";
    }
    
    // Test order management API
    echo "<h3>Order Management API Test</h3>";
    $orderApiUrl = "http://{$_SERVER['HTTP_HOST']}/esang_delicacies/public/api/order_manager_orders.php";
    $orderApiResponse = @file_get_contents($orderApiUrl);
    
    if ($orderApiResponse) {
        $orderData = json_decode($orderApiResponse, true);
        if ($orderData && $orderData['ok']) {
            echo "<p style='color: green;'>✓ Order management API working</p>";
            echo "<p>Found " . count($orderData['data']) . " orders</p>";
            
            // Show first few orders for verification
            if (!empty($orderData['data'])) {
                echo "<h4>Sample Orders:</h4>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>Order ID</th><th>Status</th><th>Customer</th><th>Amount</th></tr>";
                
                foreach (array_slice($orderData['data'], 0, 5) as $order) {
                    echo "<tr>";
                    echo "<td>#{$order['order_id']}</td>";
                    echo "<td>{$order['status']}</td>";
                    echo "<td>" . ($order['customer_name'] ?? 'N/A') . "</td>";
                    echo "<td>₱" . number_format($order['total_amount'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: red;'>✗ Order management API error</p>";
            echo "<pre>{$orderApiResponse}</pre>";
        }
    } else {
        echo "<p style='color: red;'>✗ Could not reach order management API</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>If all tests show green checkmarks, the synchronization should be working</li>";
echo "<li>Go to your order management dashboard to verify the counts are updating</li>";
echo "<li>Try changing an order status and check if counts update automatically</li>";
echo "<li>Delete this test file after verification for security</li>";
echo "</ol>";
?>