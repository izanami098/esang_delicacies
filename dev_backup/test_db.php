<?php
echo "Testing database connections...\n";

// Test different MySQL configurations
$configs = [
    ['host' => 'localhost', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'localhost', 'port' => 3307], // Alternative XAMPP port
    ['host' => '127.0.0.1', 'port' => 3307],
];

foreach ($configs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']}";
        $pdo = new PDO($dsn, 'root', '');
        echo "✅ SUCCESS: Connected to MySQL at {$config['host']}:{$config['port']}\n";
        
        // Try to create and use the database
        $pdo->exec('CREATE DATABASE IF NOT EXISTS esangdel_esang_db');
        $pdo->exec('USE esangdel_esang_db');
        
        // Create a simple table to test
        $pdo->exec('CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(50))');
        echo "✅ Database 'esangdel_esang_db' created and accessible\n";
        break;
        
    } catch (PDOException $e) {
        echo "❌ FAILED: {$config['host']}:{$config['port']} - " . $e->getMessage() . "\n";
    }
}
?>