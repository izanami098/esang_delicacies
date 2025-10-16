<?php
/**
 * Environment Configuration for Esang Delicacies
 * Automatically detects environment and sets appropriate configurations
 */

// Detect environment (treat any non-localhost web host as production)
$serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1']) || preg_match('/\\.local$|\\.test$|\\.localhost$/i', $serverName);
$isCli = (PHP_SAPI === 'cli');
$isProduction = !$isCli && !$isLocal;

// Set environment constant
define('ENVIRONMENT', $isProduction ? 'production' : 'development');
define('IS_PRODUCTION', $isProduction);
define('IS_DEVELOPMENT', !$isProduction);

// Base configuration
if (IS_PRODUCTION) {
    // Production settings
    define('BASE_URL', 'https://esangdelicacies.com/esang_delicacies');
    define('BASE_PATH', '/esang_delicacies');
    define('WS_URL', 'wss://esangdelicacies.com:8080');
    define('API_BASE_URL', 'https://esangdelicacies.com/esang_delicacies');
    
    // Production database settings (will be overridden by environment variables if available)
    define('DEFAULT_DB_HOST', 'localhost'); // TrueHost uses localhost for MySQL
    define('DEFAULT_DB_USER', 'esangdel_app'); // Production DB username
    define('DEFAULT_DB_PASS', '9H;.?zz7NeX(}qn6'); // Production DB password
    define('DEFAULT_DB_NAME', 'esangdel_esang_db');
    
    // Production upload paths
    define('UPLOAD_BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/esang_delicacies/uploads/');
    define('UPLOAD_BASE_URL', '/esang_delicacies/uploads/');
    
} else {
    // Development settings
    define('BASE_URL', 'http://localhost/esang_delicacies');
    define('BASE_PATH', '/esang_delicacies');
    define('WS_URL', 'ws://localhost:8080');
    define('API_BASE_URL', 'http://localhost/esang_delicacies');
    
    // Development database settings
    define('DEFAULT_DB_HOST', '127.0.0.1');
    define('DEFAULT_DB_USER', 'root');
    define('DEFAULT_DB_PASS', '');
    define('DEFAULT_DB_NAME', 'esangdel_esang_db');
    
    // Development upload paths
    define('UPLOAD_BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/esang_delicacies/uploads/');
    define('UPLOAD_BASE_URL', '/esang_delicacies/uploads/');
}

// Database configuration with environment variable override
define('DB_HOST', getenv('ESANG_DB_HOST') ?: (getenv('DB_HOST') ?: DEFAULT_DB_HOST));
define('DB_USER', getenv('ESANG_DB_USER') ?: (getenv('DB_USER') ?: DEFAULT_DB_USER));
define('DB_PASS', getenv('ESANG_DB_PASS') ?: (getenv('DB_PASS') ?: DEFAULT_DB_PASS));
define('DB_NAME', getenv('ESANG_DB_NAME') ?: (getenv('DB_NAME') ?: DEFAULT_DB_NAME));

// Common paths
define('ASSETS_URL', BASE_URL . '/assets');
define('PUBLIC_URL', BASE_URL . '/public');
define('API_URL', BASE_URL . '/api');

// File upload settings
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 1800); // 30 minutes

// Logging
define('LOG_ERRORS', IS_PRODUCTION);
define('DISPLAY_ERRORS', IS_DEVELOPMENT);

// Set error reporting based on environment
if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

/**
 * Get the appropriate URL for the current environment
 */
function getUrl($path = '') {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get the appropriate API URL for the current environment
 */
function getApiUrl($endpoint = '') {
    return API_URL . ($endpoint ? '/' . ltrim($endpoint, '/') : '');
}

/**
 * Get WebSocket URL for current environment
 */
function getWebSocketUrl() {
    return WS_URL;
}

/**
 * Get upload URL for files
 */
function getUploadUrl($filename = '') {
    return UPLOAD_BASE_URL . ($filename ? ltrim($filename, '/') : '');
}

// Debug information (only in development)
if (IS_DEVELOPMENT && isset($_GET['debug_env'])) {
    echo "<pre>";
    echo "Environment: " . ENVIRONMENT . "\n";
    echo "Server Name: " . $serverName . "\n";
    echo "Base URL: " . BASE_URL . "\n";
    echo "WebSocket URL: " . WS_URL . "\n";
    echo "Database Host: " . DB_HOST . "\n";
    echo "Database Name: " . DB_NAME . "\n";
    echo "</pre>";
}
?>