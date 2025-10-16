<?php
require_once 'app/config/database.php';

try {
    $conn = Database::getConnection();
    
    echo "Connected to database successfully.\n\n";
    
    // Check if rider table exists
    $result = $conn->query("SHOW TABLES LIKE 'rider'");
    if ($result->num_rows > 0) {
        echo "Rider table exists.\n";
        
        // Check rider table structure
        $result = $conn->query("DESCRIBE rider");
        echo "\nRider table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
        
        // Check existing riders
        $result = $conn->query("SELECT * FROM rider LIMIT 5");
        echo "\nExisting riders:\n";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "ID: {$row['empId']}, Name: {$row['name']}, Phone: {$row['phone']}\n";
            }
        } else {
            echo "No riders found.\n";
            
            // Insert sample rider
            $stmt = $conn->prepare("INSERT INTO rider (name, phone, plateNum, email) VALUES (?, ?, ?, ?)");
            $name = "Ivan Francis";
            $phone = "09604567862";  
            $plate = "SFX1234G";
            $email = "ivan.francis@example.com";
            $stmt->bind_param("ssss", $name, $phone, $plate, $email);
            
            if ($stmt->execute()) {
                echo "Sample rider inserted successfully!\n";
                $riderId = $conn->insert_id;
                echo "Rider ID: {$riderId}\n";
            } else {
                echo "Error inserting rider: " . $stmt->error . "\n";
            }
        }
        
    } else {
        echo "Rider table does not exist. Creating it...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS rider (
            empId INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            plateNum VARCHAR(20),
            email VARCHAR(255),
            status ENUM('available', 'busy', 'offline') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "Rider table created successfully.\n";
            
            // Insert sample rider
            $stmt = $conn->prepare("INSERT INTO rider (name, phone, plateNum, email) VALUES (?, ?, ?, ?)");
            $name = "Ivan Francis";
            $phone = "09604567862";  
            $plate = "SFX1234G";
            $email = "ivan.francis@example.com";
            $stmt->bind_param("ssss", $name, $phone, $plate, $email);
            
            if ($stmt->execute()) {
                echo "Sample rider inserted successfully!\n";
                $riderId = $conn->insert_id;
                echo "Rider ID: {$riderId}\n";
            }
        }
    }
    
    // Check orders table
    $result = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($result->num_rows > 0) {
        echo "\n\nOrders table exists.\n";
        
        // Check recent orders
        $result = $conn->query("SELECT order_id, customer_id, status, rider_id, total_amount FROM orders ORDER BY created_at DESC LIMIT 5");
        echo "\nRecent orders:\n";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "Order ID: {$row['order_id']}, Customer: {$row['customer_id']}, Status: {$row['status']}, Rider: " . ($row['rider_id'] ?: 'None') . ", Total: {$row['total_amount']}\n";
            }
            
            // Update first order to have rider assigned if no rider assigned yet
            $result = $conn->query("SELECT order_id FROM orders WHERE rider_id IS NULL AND status IN ('pending', 'confirmed') ORDER BY created_at DESC LIMIT 1");
            if ($result->num_rows > 0) {
                $order = $result->fetch_assoc();
                $orderId = $order['order_id'];
                
                // Get first rider
                $riderResult = $conn->query("SELECT empId FROM rider LIMIT 1");
                if ($riderResult->num_rows > 0) {
                    $rider = $riderResult->fetch_assoc();
                    $riderId = $rider['empId'];
                    
                    $stmt = $conn->prepare("UPDATE orders SET rider_id = ?, status = 'confirmed' WHERE order_id = ?");
                    $stmt->bind_param("ii", $riderId, $orderId);
                    
                    if ($stmt->execute()) {
                        echo "\nOrder {$orderId} updated with rider {$riderId} and status 'confirmed'.\n";
                    }
                }
            }
            
        } else {
            echo "No orders found.\n";
        }
        
    } else {
        echo "\nOrders table does not exist.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>