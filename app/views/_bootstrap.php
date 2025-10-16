<?php
// Use absolute path for includes
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): mysqli {
    return Database::getConnection();
}

function current_user_id(): ?int {
    return isset($_SESSION['customerId']) ? (int)$_SESSION['customerId'] : null;
}

function require_login(): void {
    if (!current_user_id()) {
        // Redirect to homepage if not logged in
        header('Location: /esang_delicacies/public/Index.php');
        exit;
    }
}

function json_headers_local(): void {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
}

?>


