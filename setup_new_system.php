<?php
/**
 * Setup script to initialize the new rider and customer system
 * Run this once to set up the database and create sample data
 */

echo "<!DOCTYPE html><html><head><title>System Setup</title><style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
.container { max-width: 800px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { color: #28a745; }
.error { color: #dc3545; }
.info { color: #007bff; }
</style></head><body><div class='container'>";

echo "<h1>🚀 Esang Delicacies System Setup</h1>";

try {
    require_once 'includes/db.php';
    
    echo "<div class='info'>✅ Database connection established!</div><br>";
    
    // Check if tables exist
    $tables = ['customers', 'riders', 'orders', 'order_items'];
    $tablesExist = true;
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() == 0) {
            $tablesExist = false;
            echo "<div class='error'>❌ Table '$table' does not exist</div>";
        } else {
            echo "<div class='success'>✅ Table '$table' exists</div>";
        }
    }
    
    if ($tablesExist) {
        echo "<br><div class='success'>🎉 <strong>All database tables are set up correctly!</strong></div><br>";
        
        // Check if sample data exists
        $orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        if ($orderCount == 0) {
            echo "<div class='info'>📝 No sample orders found. Creating sample data...</div><br>";
            
            // Create sample data
            include 'create_sample_data.php';
            
            echo "<br><div class='success'>✅ Sample data created successfully!</div><br>";
        } else {
            echo "<div class='info'>📊 Found $orderCount existing orders</div><br>";
        }
        
        // Show login credentials
        echo "<h2>🔐 Login Credentials</h2>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3>Rider Login:</h3>";
        echo "<strong>Email:</strong> admin@esang.com<br>";
        echo "<strong>Password:</strong> admin123<br><br>";
        
        echo "<h3>Customer Login:</h3>";
        echo "<strong>Email:</strong> customer@test.com<br>";
        echo "<strong>Password:</strong> customer123<br>";
        echo "</div>";
        
        // Show next steps
        echo "<h2>🎯 Next Steps</h2>";
        echo "<ol>";
        echo "<li><a href='app/views/auth/LogIn.php'>Go to Login Page</a></li>";
        echo "<li>Select 'Rider (Email)' and login with rider credentials</li>";
        echo "<li>View and accept available delivery orders</li>";
        echo "<li>Test the complete rider workflow</li>";
        echo "</ol>";
        
        echo "<br><div class='success'><strong>🚀 System is ready to use!</strong></div>";
        
    } else {
        echo "<br><div class='error'>❌ <strong>Some tables are missing.</strong></div>";
        echo "<div class='info'>The database tables will be created automatically when you visit any page that requires them.</div>";
        echo "<br><a href='app/views/auth/LogIn.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page to Auto-Create Tables</a>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Make sure XAMPP is running and the database is accessible.</div>";
}

echo "</div></body></html>";
?>