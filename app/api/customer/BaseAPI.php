<?php
/**
 * Base API class for profile hash authenticated endpoints
 */

require_once __DIR__ . '/../../classes/ProfileHashManager.php';
require_once __DIR__ . '/../../views/_bootstrap.php';

abstract class BaseAPI {
    protected $profileHashManager;
    protected $database;
    protected $profileHash;
    protected $sessionId;
    protected $customer;
    
    public function __construct() {
        // Set headers for JSON API
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('Method not allowed', 405);
            exit;
        }
        
        // Initialize dependencies
        $this->profileHashManager = ProfileHashManager::getInstance();
        $this->database = db();
        
        // Parse and validate request
        $this->validateRequest();
    }
    
    protected function validateRequest() {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $this->sendError('Invalid JSON input');
        }
        
        // Validate required fields
        if (!isset($input['profile_hash']) || !isset($input['session_id'])) {
            $this->sendError('Missing profile_hash or session_id');
        }
        
        $this->profileHash = $input['profile_hash'];
        $this->sessionId = $input['session_id'];
        
        // Validate profile hash format
        if (!preg_match('/^[a-f0-9]{32}$/', $this->profileHash)) {
            $this->sendError('Invalid profile hash format');
        }
        
        // Validate session and get customer data
        $sessionValid = $this->profileHashManager->validateSession($this->sessionId, $this->profileHash);
        if (!$sessionValid) {
            $this->sendError('Invalid session or profile hash', 401);
        }
        
        // Get customer data
        $this->customer = $this->profileHashManager->getCustomerByHash($this->profileHash);
        if (!$this->customer) {
            $this->sendError('Customer not found', 404);
        }
    }
    
    protected function sendSuccess($data = [], $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    protected function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Abstract method that child classes must implement
    abstract public function handleRequest();
}
?>