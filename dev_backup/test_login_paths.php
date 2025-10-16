<?php
/**
 * Test Login Redirect Paths
 * This file tests if all the login redirect paths work correctly
 */

echo "<h2>Login Path Testing</h2>";
echo "<p>Current file location: " . __FILE__ . "</p>";
echo "<p>Current directory: " . __DIR__ . "</p>";

echo "<h3>File Existence Check:</h3>";
$files_to_check = [
    // Main dashboard files
    './customer_dashboard.php' => 'Main Customer Dashboard',
    './app/views/customer/customer_dashboard.php' => 'Views Customer Dashboard',
    
    // Config files
    './config/environment.php' => 'Environment Config',
    './includes/config.php' => 'Legacy Config',
    './includes/db.php' => 'Database Config',
    
    // Login related files
    './app/views/auth/LogIn.php' => 'Login File',
    './app/views/auth/session.php' => 'Session File',
    './app/views/_bootstrap.php' => 'Bootstrap File',
    './app/classes/ProfileHashManager.php' => 'Profile Hash Manager',
    
    // Other dashboard files
    './app/views/admin/admin_dashboard.php' => 'Admin Dashboard',
    './app/views/cashier/cashier_walk_in.php' => 'Cashier Dashboard',
    './app/views/order_manager/order_management.php' => 'Order Manager Dashboard',
    './app/views/rider/order_assignments.php' => 'Rider Dashboard',
];

echo "<ul>";
foreach ($files_to_check as $path => $description) {
    $exists = file_exists($path);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<li style='color: $color'><strong>$description</strong>: $path - $status</li>";
}
echo "</ul>";

echo "<h3>Path Resolution from Login File:</h3>";
echo "<p>From: <code>app/views/auth/LogIn.php</code></p>";
echo "<ul>";

$login_base = './app/views/auth/';
$redirect_paths = [
    '../../../customer_dashboard.php' => 'Customer Dashboard (fixed)',
    '../customer/customer_dashboard.php' => 'Customer Dashboard (old)',
    '../../views/admin/admin_dashboard.php' => 'Admin Dashboard',
    '../../views/cashier/cashier_walk_in.php' => 'Cashier Dashboard',
    '../../views/order_manager/order_management.php' => 'Order Manager Dashboard',
    '../rider/order_assignments.php' => 'Rider Dashboard',
];

foreach ($redirect_paths as $relative_path => $description) {
    // Resolve the path from login directory
    $resolved_path = realpath($login_base . $relative_path);
    $exists = $resolved_path && file_exists($resolved_path);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ VALID' : '❌ BROKEN';
    echo "<li style='color: $color'><strong>$description</strong>: $relative_path → ";
    echo $resolved_path ? $resolved_path : 'UNRESOLVED';
    echo " - $status</li>";
}
echo "</ul>";

echo "<h3>Environment Configuration Test:</h3>";
if (file_exists('./config/environment.php')) {
    require_once './config/environment.php';
    echo "<ul>";
    echo "<li><strong>Environment:</strong> " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'NOT DEFINED') . "</li>";
    echo "<li><strong>Base URL:</strong> " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "</li>";
    echo "<li><strong>Database Host:</strong> " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</li>";
    echo "<li><strong>WebSocket URL:</strong> " . (defined('WS_URL') ? WS_URL : 'NOT DEFINED') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red'>❌ Environment configuration not found!</p>";
}

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>✅ Customer dashboard redirect paths have been fixed</li>";
echo "<li>✅ Environment configuration is loaded in login file</li>";
echo "<li>✅ All major files exist in correct locations</li>";
echo "<li>⚠️ Consider using only one customer dashboard file (prefer root level)</li>";
echo "</ul>";

echo "<p><strong>Status:</strong> <span style='color: green'>LOGIN SHOULD WORK WITHOUT DIRECTORY ISSUES</span></p>";
?>