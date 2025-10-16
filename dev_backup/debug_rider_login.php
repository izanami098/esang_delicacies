<?php
/**
 * Debug script to check rider login issues
 */

require_once 'app/views/_bootstrap.php';

echo "<h2>Rider Login Debug</h2>";

try {
    $mysqli = db();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check database name
    $result = $mysqli->query("SELECT DATABASE() as db_name");
    if ($result) {
        $db = $result->fetch_assoc();
        echo "<p><strong>Connected to database:</strong> " . $db['db_name'] . "</p>";
    }
    
    // Check what tables exist
    echo "<h3>Available Tables:</h3>";
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    // Check specifically for riders-related tables
    echo "<h3>Rider Tables Check:</h3>";
    
    // Check for 'riders' table
    $result = $mysqli->query("SHOW TABLES LIKE 'riders'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ 'riders' table exists</p>";
        
        // Show table structure
        $result = $mysqli->query("DESCRIBE riders");
        if ($result) {
            echo "<h4>Riders Table Structure:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show all riders data
        $result = $mysqli->query("SELECT * FROM riders");
        if ($result) {
            echo "<h4>All Riders Data:</h4>";
            if ($result->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password Field</th><th>Approved</th><th>Status</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['rider_id'] ?? $row['id'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                    
                    // Check what password field exists
                    $passwordField = 'N/A';
                    if (isset($row['password_hash'])) {
                        $passwordField = 'password_hash: ' . substr($row['password_hash'], 0, 20) . '...';
                    } elseif (isset($row['password'])) {
                        $passwordField = 'password: ' . substr($row['password'], 0, 20) . '...';
                    }
                    echo "<td>" . htmlspecialchars($passwordField) . "</td>";
                    
                    $approved = $row['is_approved'] ?? $row['approved'] ?? 'N/A';
                    echo "<td>" . ($approved === 1 || $approved === '1' || $approved === true ? 'Yes' : 'No/N/A') . "</td>";
                    
                    echo "<td>" . htmlspecialchars($row['status'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: orange;'>No riders found in the table.</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ 'riders' table not found</p>";
    }
    
    // Check for 'RIDER' table (uppercase)
    $result = $mysqli->query("SHOW TABLES LIKE 'RIDER'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ 'RIDER' table exists (old format)</p>";
        
        // Show table structure
        $result = $mysqli->query("DESCRIBE RIDER");
        if ($result) {
            echo "<h4>RIDER Table Structure:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show all RIDER data
        $result = $mysqli->query("SELECT * FROM RIDER");
        if ($result && $result->num_rows > 0) {
            echo "<h4>All RIDER Data:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password</th><th>Verified</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['empId'] ?? $row['id'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['name'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['email'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['password'] ?? 'N/A', 0, 20)) . "...</td>";
                echo "<td>" . ($row['verified'] == 1 ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: orange;'>ℹ 'RIDER' table not found</p>";
    }
    
    echo "<hr>";
    echo "<h3>Test a Manual Login:</h3>";
    
    if (isset($_POST['test_email']) && isset($_POST['test_password'])) {
        $testEmail = $_POST['test_email'];
        $testPassword = $_POST['test_password'];
        
        echo "<p><strong>Testing login for:</strong> " . htmlspecialchars($testEmail) . "</p>";
        
        // Test riders table first
        $tableCheckResult = $mysqli->query("SHOW TABLES LIKE 'riders'");
        if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
            echo "<p>Testing against 'riders' table...</p>";
            
            $stmt = $mysqli->prepare("SELECT rider_id, name, email, password_hash, is_approved, status FROM riders WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param('s', $testEmail);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($rider = $result->fetch_assoc()) {
                        echo "<p style='color: green;'>✓ Rider found in database!</p>";
                        echo "<p><strong>Name:</strong> " . htmlspecialchars($rider['name']) . "</p>";
                        echo "<p><strong>Email:</strong> " . htmlspecialchars($rider['email']) . "</p>";
                        echo "<p><strong>Approved:</strong> " . ($rider['is_approved'] ? 'Yes' : 'No') . "</p>";
                        echo "<p><strong>Status:</strong> " . htmlspecialchars($rider['status']) . "</p>";
                        
                        if ($rider['is_approved']) {
                            // Test password
                            if (password_verify($testPassword, $rider['password_hash'])) {
                                echo "<p style='color: green;'>✓ Password verification successful!</p>";
                                echo "<p style='color: blue;'><strong>Login should work! Try again with the main login form.</strong></p>";
                            } elseif ($testPassword === $rider['password_hash']) {
                                echo "<p style='color: orange;'>⚠ Plain text password match (needs hashing)</p>";
                                echo "<p style='color: blue;'><strong>Login should work with fallback check.</strong></p>";
                            } else {
                                echo "<p style='color: red;'>✗ Password verification failed</p>";
                                echo "<p>Stored hash: " . substr($rider['password_hash'], 0, 30) . "...</p>";
                                echo "<p>Test password: " . htmlspecialchars($testPassword) . "</p>";
                            }
                        } else {
                            echo "<p style='color: red;'>✗ Rider account not approved</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>✗ No rider found with email: " . htmlspecialchars($testEmail) . "</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ Query execution failed</p>";
                }
                $stmt->close();
            } else {
                echo "<p style='color: red;'>✗ Failed to prepare statement</p>";
            }
        }
        
        // Test RIDER table if riders table didn't work
        $oldTableCheck = $mysqli->query("SHOW TABLES LIKE 'RIDER'");
        if ($oldTableCheck && $oldTableCheck->num_rows > 0) {
            echo "<p>Testing against 'RIDER' table...</p>";
            
            $stmt = $mysqli->prepare("SELECT empId, name, email, verified FROM RIDER WHERE email = ? AND password = ?");
            if ($stmt) {
                $stmt->bind_param('ss', $testEmail, $testPassword);
                if ($stmt->execute()) {
                    $stmt->bind_result($empId, $name, $email, $verified);
                    if ($stmt->fetch()) {
                        echo "<p style='color: green;'>✓ Rider found in RIDER table!</p>";
                        echo "<p><strong>ID:</strong> " . $empId . "</p>";
                        echo "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
                        echo "<p><strong>Verified:</strong> " . ($verified == 1 ? 'Yes' : 'No') . "</p>";
                        
                        if ($verified == 1) {
                            echo "<p style='color: green;'>✓ Old-style login should work!</p>";
                        } else {
                            echo "<p style='color: red;'>✗ Account not verified</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>✗ No rider found in RIDER table</p>";
                    }
                } else {
                    echo "<p style='color: red;'>✗ RIDER query execution failed</p>";
                }
                $stmt->close();
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>Test Login Form:</h3>
<form method="POST" style="max-width: 400px;">
    <div style="margin-bottom: 10px;">
        <label>Email:</label><br>
        <input type="email" name="test_email" value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>" style="width: 100%; padding: 5px;" required>
    </div>
    <div style="margin-bottom: 10px;">
        <label>Password:</label><br>
        <input type="password" name="test_password" style="width: 100%; padding: 5px;" required>
    </div>
    <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none;">Test Login</button>
</form>

<p><a href="app/views/auth/LogIn.php">Go to Main Login Page</a></p>