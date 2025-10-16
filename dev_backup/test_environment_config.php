<?php
/**
 * Test Environment Configuration
 * This file tests if the environment detection and configuration works properly
 */

// Load the environment configuration
require_once 'config/environment.php';

// Set content type for JSON output
header('Content-Type: application/json');

// Prepare test results
$testResults = [
    'environment_detection' => [
        'server_name' => $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost',
        'detected_environment' => ENVIRONMENT,
        'is_production' => IS_PRODUCTION,
        'is_development' => IS_DEVELOPMENT
    ],
    'configuration_constants' => [
        'BASE_URL' => BASE_URL,
        'BASE_PATH' => BASE_PATH,
        'WS_URL' => WS_URL,
        'API_BASE_URL' => API_BASE_URL,
        'DB_HOST' => DB_HOST,
        'DB_NAME' => DB_NAME,
        'ASSETS_URL' => ASSETS_URL,
        'PUBLIC_URL' => PUBLIC_URL,
        'API_URL' => API_URL
    ],
    'helper_functions' => [
        'getUrl()' => getUrl(),
        'getUrl("test")' => getUrl('test'),
        'getApiUrl()' => getApiUrl(),
        'getApiUrl("test")' => getApiUrl('test'),
        'getWebSocketUrl()' => getWebSocketUrl(),
        'getUploadUrl()' => getUploadUrl(),
        'getUploadUrl("test.jpg")' => getUploadUrl('test.jpg')
    ],
    'database_test' => [
        'host' => DB_HOST,
        'user' => DB_USER,
        'database' => DB_NAME,
        'connection_test' => 'not_tested' // We won't actually test DB connection here
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'success'
];

// Add production-specific tests
if (IS_PRODUCTION) {
    $testResults['production_checks'] = [
        'https_urls' => strpos(BASE_URL, 'https://') === 0,
        'secure_websocket' => strpos(WS_URL, 'wss://') === 0,
        'correct_domain' => strpos(BASE_URL, 'esangdelicacies.com') !== false
    ];
} else {
    $testResults['development_checks'] = [
        'localhost_urls' => strpos(BASE_URL, 'localhost') !== false,
        'http_allowed' => strpos(BASE_URL, 'http://') === 0,
        'local_websocket' => strpos(WS_URL, 'ws://localhost') === 0
    ];
}

// Output the test results
echo json_encode($testResults, JSON_PRETTY_PRINT);
?>