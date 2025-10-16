<?php
// Load environment configuration
require_once dirname(dirname(dirname(__DIR__))) . '/config/environment.php';

// Database constants are now defined in environment.php
// Available: DB_HOST, DB_USER, DB_PASS, DB_NAME
?>
$con = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($con->connect_error) {
    die("Database connection failed: " . $con->connect_error);
}

?>