<?php
/**
 * Test script to check riders table and create a test rider using MySQLi
 */

require_once 'app/views/_bootstrap.php';

try {
    echo "<h2>Rider Setup Test (MySQLi Version)</h2>";
    
    $mysqli = db();
    
    // Check if riders table exists
    $tableCheckResult = $mysqli->query("SHOW TABLES LIKE 'riders'");
    if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
        echo "<p style='color: green;'>✓ Modern 'riders' table found!</p>";
        
        // Check if riders exist and show them
        $result = $mysqli->query("SELECT COUNT(*) as rider_count FROM riders");
        if ($result) {
            $count = $result->fetch_assoc();
            echo "<p>Current riders in database: " . $count['rider_count'] . "</p>";
        }
        
        // Show existing riders
        $result = $mysqli->query("SELECT rider_id, name, email, status, is_approved FROM riders LIMIT 10");
        if ($result) {
            $riders = $result->fetch_all(MYSQLI_ASSOC);
            
            if ($riders) {
                echo "<h3>Existing Riders:</h3>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Approved</th></tr>";
                foreach ($riders as $rider) {
                    $approved = $rider['is_approved'] ? 'Yes' : 'No';
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($rider['rider_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($rider['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($rider['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($rider['status']) . "</td>";
                    echo "<td>" . $approved . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<h3>No riders found. Creating test rider...</h3>";
                createTestRider($mysqli);
            }
        }
    } else {
        // Check for old RIDER table
        $oldTableCheck = $mysqli->query("SHOW TABLES LIKE 'RIDER'");
        if ($oldTableCheck && $oldTableCheck->num_rows > 0) {
            echo "<p style='color: orange;'>⚠ Old 'RIDER' table found! Creating modern riders table...</p>";
            createRidersTable($mysqli);
            createTestRider($mysqli);
        } else {
            echo "<p style='color: orange;'>⚠ No riders table found! Creating riders table...</p>";
            createRidersTable($mysqli);
            createTestRider($mysqli);
        }
    }
    
    echo "<hr>";
    echo "<h3>Test Login:</h3>";
    echo "<p>You can now test rider login with:</p>";
    echo "<ul>";
    echo "<li><strong>Role:</strong> Rider (Email)</li>";
    echo "<li><strong>Email:</strong> testrider@example.com</li>";
    echo "<li><strong>Password:</strong> password123</li>";
    echo "</ul>";
    echo "<p><a href='app/views/auth/LogIn.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

function createRidersTable($mysqli) {
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS riders (
            rider_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            license_number VARCHAR(50),
            vehicle_type VARCHAR(50) DEFAULT 'motorcycle',
            vehicle_plate VARCHAR(20),
            password_hash VARCHAR(255) NOT NULL,
            profile_hash VARCHAR(64),
            status ENUM('active', 'inactive', 'busy', 'offline') DEFAULT 'offline',
            is_approved BOOLEAN DEFAULT FALSE,
            total_deliveries INT DEFAULT 0,
            current_location_lat DECIMAL(10, 8),
            current_location_lng DECIMAL(11, 8),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )
    ";
    
    if ($mysqli->query($createTableSQL)) {
        echo "<p style='color: green;'>✓ Riders table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create riders table: " . $mysqli->error . "</p>";
    }
}

function createTestRider($mysqli) {
    // Check if test rider already exists
    $stmt = $mysqli->prepare("SELECT rider_id FROM riders WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $testEmail = 'testrider@example.com');
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p style='color: blue;'>ℹ Test rider already exists!</p>";
            $stmt->close();
            return;
        }
        $stmt->close();
    }
    
    // Create test rider
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    $profileHash = 'rider_' . uniqid() . '_' . time();
    
    $stmt = $mysqli->prepare("
        INSERT INTO riders (name, email, phone, license_number, vehicle_type, vehicle_plate, 
                          password_hash, profile_hash, is_approved, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, 'offline')
    ");
    
    if ($stmt) {
        $stmt->bind_param('ssssssss', 
            $name = 'Test Rider',
            $email = 'testrider@example.com',
            $phone = '09123456789',
            $license = 'TEST123',
            $vehicle = 'motorcycle',
            $plate = 'TEST-001',
            $hashedPassword,
            $profileHash
        );
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Test rider created successfully!</p>";
            echo "<p><strong>Email:</strong> testrider@example.com</p>";
            echo "<p><strong>Password:</strong> password123</p>";
            echo "<p><strong>Status:</strong> Pre-approved for testing</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create test rider: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: red;'>✗ Failed to prepare statement: " . $mysqli->error . "</p>";
    }
}
?>
