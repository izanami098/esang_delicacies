<?php
/**
 * Simple database connection test
 */

echo "<h1>Database Connection Test</h1>";

// Test the main database configuration
try {
    require_once 'app/config/database.php';
    $db = Database::getConnection();
    echo "<p style='color: green;'>✅ Main Database Connection: SUCCESS</p>";
    echo "<p>Connected to database: " . $db->get_server_info() . "</p>";
    
    // Test if esangdel_esang_db exists
    $result = $db->query("SHOW DATABASES LIKE 'esangdel_esang_db'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Database 'esangdel_esang_db' exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Database 'esangdel_esang_db' does not exist - it will be created automatically</p>";
    }
    
    // Test customer table
    $result = $db->query("SHOW TABLES LIKE 'customer'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Customer table exists</p>";
        
        // Check sample customers
        $result = $db->query("SELECT COUNT(*) as count FROM customer");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Total customers: " . $row['count'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Customer table does not exist</p>";
    }
    
    // Test RIDER table
    $result = $db->query("SHOW TABLES LIKE 'RIDER'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ RIDER table exists</p>";
        
        // Check sample riders
        $result = $db->query("SELECT COUNT(*) as count FROM RIDER");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Total riders: " . $row['count'] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ RIDER table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Main Database Connection: FAILED</p>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test includes/db.php (PDO connection)
try {
    require_once 'includes/db.php';
    echo "<p style='color: green;'>✅ PDO Database Connection: SUCCESS</p>";
    
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "<p>Current database: " . $result['current_db'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ PDO Database Connection: FAILED</p>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Environment Check</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>MySQL Extensions:</strong> " . (extension_loaded('mysqli') ? '✅ MySQLi' : '❌ MySQLi') . " | " . (extension_loaded('pdo_mysql') ? '✅ PDO MySQL' : '❌ PDO MySQL') . "</p>";

// Check environment variables
$env_vars = ['ESANG_DB_HOST', 'ESANG_DB_USER', 'ESANG_DB_PASS', 'ESANG_DB_NAME'];
echo "<h3>Environment Variables</h3>";
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo "<p><strong>$var:</strong> " . ($value ? $value : 'Not set (using default)') . "</p>";
}
?>