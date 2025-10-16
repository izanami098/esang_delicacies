<?php
/**
 * Standardized API Configuration for TrueHost
 * Include this file in all API endpoints
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db_connection.php';

// Set standard JSON headers for all APIs
function setApiHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: https://esangdelicacies.com');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
}

// Handle preflight requests
function handleOptions() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        setApiHeaders();
        http_response_code(200);
        exit(0);
    }
}

// Standard error response
function apiError($code, $message, $details = null) {
    http_response_code($code);
    $response = ['success' => false, 'message' => $message];
    if ($details) {
        $response['details'] = $details;
    }
    echo json_encode($response);
    exit();
}

// Standard success response
function apiSuccess($data = null, $message = 'Success', $code = 200) {
    http_response_code($code);
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

// Validate required fields
function validateRequired($input, $required) {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}

// Get JSON input safely
function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        apiError(400, 'Invalid JSON input');
    }
    return $input ?: [];
}

// Initialize API
setApiHeaders();
handleOptions();
?>