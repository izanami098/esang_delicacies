<?php
// Debug script to check paths
echo "<h3>Path Debug Information</h3>";
echo "<p><strong>Current working directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Script filename:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Script directory:</strong> " . dirname(__FILE__) . "</p>";

echo "<h4>File existence check:</h4>";
$files_to_check = [
    'public/api/get_pending_orders.php',
    './public/api/get_pending_orders.php',
    '../public/api/get_pending_orders.php',
    '../../public/api/get_pending_orders.php',
    '../../../public/api/get_pending_orders.php'
];

foreach ($files_to_check as $path) {
    $exists = file_exists($path) ? "✓ EXISTS" : "✗ Missing";
    echo "<p><strong>$path:</strong> $exists</p>";
    if (file_exists($path)) {
        echo "<p style='margin-left: 20px; color: green;'>Full path: " . realpath($path) . "</p>";
    }
}

echo "<h4>From Order Manager directory perspective:</h4>";
chdir('app/views/order_manager/');
echo "<p><strong>Changed to:</strong> " . getcwd() . "</p>";

$om_files_to_check = [
    '../../../public/api/get_pending_orders.php',
    '../../public/api/get_pending_orders.php',
    '../public/api/get_pending_orders.php'
];

foreach ($om_files_to_check as $path) {
    $exists = file_exists($path) ? "✓ EXISTS" : "✗ Missing";
    echo "<p><strong>$path:</strong> $exists</p>";
    if (file_exists($path)) {
        echo "<p style='margin-left: 20px; color: green;'>Full path: " . realpath($path) . "</p>";
    }
}
?>