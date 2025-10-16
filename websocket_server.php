<?php
/**
 * WebSocket Server for Real-time Customer Notifications
 * Handles real-time communication between customers and the notification system
 */

require_once 'vendor/autoload.php';
require_once 'includes/config.php';
require_once 'app/classes/ProfileHashManager.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\Socket\Server as ReactServer;

class NotificationWebSocketServer implements MessageComponentInterface {
    protected $clients;
    protected $customerConnections;
    protected $database;
    protected $profileHashManager;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        $this->customerConnections = [];
        $this->database = new mysqli(DB_HOST_OVERRIDE, DB_USER_OVERRIDE, DB_PASS_OVERRIDE, DB_NAME_OVERRIDE);
        $this->profileHashManager = ProfileHashManager::getInstance();
        
        if ($this->database->connect_error) {
            error_log("Database connection failed: " . $this->database->connect_error);
            die("Database connection failed");
        }
        
        echo "Notification WebSocket Server started...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send welcome message
        $conn->send(json_encode([
            'type' => 'connection_established',
            'message' => 'Connected to notification server',
            'connection_id' => $conn->resourceId,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }

            switch ($data['type']) {
                case 'authenticate':
                    $this->handleAuthentication($from, $data);
                    break;
                    
                case 'get_notifications':
                    $this->handleGetNotifications($from, $data);
                    break;
                    
                case 'mark_read':
                    $this->handleMarkRead($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from, $data);
                    break;
                    
                default:
                    $this->sendError($from, 'Unknown message type');
            }
        } catch (Exception $e) {
            error_log("WebSocket message error: " . $e->getMessage());
            $this->sendError($from, 'Internal server error');
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove the connection
        $this->clients->detach($conn);
        
        // Remove from customer connections
        foreach ($this->customerConnections as $customerId => $connections) {
            foreach ($connections as $key => $connection) {
                if ($connection['conn'] === $conn) {
                    unset($this->customerConnections[$customerId][$key]);
                    
                    // Update database
                    $stmt = $this->database->prepare("UPDATE customer_websocket_connections SET is_active = FALSE WHERE connection_id = ?");
                    $stmt->bind_param("s", $connection['connection_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    break;
                }
            }
            
            if (empty($this->customerConnections[$customerId])) {
                unset($this->customerConnections[$customerId]);
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        error_log("WebSocket error: " . $e->getMessage());
        $conn->close();
    }

    private function handleAuthentication($conn, $data) {
        if (!isset($data['session_id']) || !isset($data['profile_hash'])) {
            $this->sendError($conn, 'Missing session_id or profile_hash');
            return;
        }

        // Validate session and profile hash
        $sessionValid = $this->profileHashManager->validateSession($data['session_id'], $data['profile_hash']);
        
        if (!$sessionValid) {
            $this->sendError($conn, 'Invalid session or profile hash');
            return;
        }

        // Get customer details
        $stmt = $this->database->prepare("SELECT id, first_name, last_name FROM customer WHERE profile_hash = ?");
        $stmt->bind_param("s", $data['profile_hash']);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            $this->sendError($conn, 'Customer not found');
            return;
        }

        $customerId = $customer['id'];
        $connectionId = uniqid('conn_');

        // Store connection
        if (!isset($this->customerConnections[$customerId])) {
            $this->customerConnections[$customerId] = [];
        }

        $this->customerConnections[$customerId][] = [
            'conn' => $conn,
            'connection_id' => $connectionId,
            'profile_hash' => $data['profile_hash'],
            'authenticated_at' => time()
        ];

        // Store in database
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIpAddress();
        
        $stmt = $this->database->prepare("
            INSERT INTO customer_websocket_connections 
            (customer_id, profile_hash, connection_id, session_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $customerId, $data['profile_hash'], $connectionId, $data['session_id'], $ipAddress, $userAgent);
        $stmt->execute();
        $stmt->close();

        // Send authentication success
        $conn->send(json_encode([
            'type' => 'authentication_success',
            'message' => 'Successfully authenticated',
            'customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'connection_id' => $connectionId,
            'timestamp' => date('Y-m-d H:i:s')
        ]));

        // Send any unread notifications
        $this->sendUnreadNotifications($conn, $customerId);

        echo "Customer {$customerId} authenticated on connection {$conn->resourceId}\n";
    }

    private function handleGetNotifications($conn, $data) {
        $customerId = $this->getCustomerIdFromConnection($conn);
        if (!$customerId) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }

        $limit = isset($data['limit']) ? intval($data['limit']) : 20;
        $offset = isset($data['offset']) ? intval($data['offset']) : 0;

        $stmt = $this->database->prepare("
            SELECT id, order_id, notification_type, title, message, priority, is_read, created_at
            FROM customer_notifications 
            WHERE customer_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iii", $customerId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        $conn->send(json_encode([
            'type' => 'notifications_list',
            'notifications' => $notifications,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    private function handleMarkRead($conn, $data) {
        $customerId = $this->getCustomerIdFromConnection($conn);
        if (!$customerId) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }

        if (!isset($data['notification_id'])) {
            $this->sendError($conn, 'Missing notification_id');
            return;
        }

        $stmt = $this->database->prepare("
            UPDATE customer_notifications 
            SET is_read = TRUE, read_at = NOW() 
            WHERE id = ? AND customer_id = ?
        ");
        $stmt->bind_param("ii", $data['notification_id'], $customerId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $conn->send(json_encode([
                'type' => 'notification_marked_read',
                'notification_id' => $data['notification_id'],
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        } else {
            $this->sendError($conn, 'Notification not found or already read');
        }
    }

    private function handlePing($conn, $data) {
        $customerId = $this->getCustomerIdFromConnection($conn);
        if ($customerId) {
            // Update last ping time
            $connectionId = $this->getConnectionIdFromConnection($conn);
            if ($connectionId) {
                $stmt = $this->database->prepare("UPDATE customer_websocket_connections SET last_ping = NOW() WHERE connection_id = ?");
                $stmt->bind_param("s", $connectionId);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->send(json_encode([
            'type' => 'pong',
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    private function sendUnreadNotifications($conn, $customerId) {
        $stmt = $this->database->prepare("
            SELECT id, order_id, notification_type, title, message, priority, created_at
            FROM customer_notifications 
            WHERE customer_id = ? AND is_read = FALSE 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($notification = $result->fetch_assoc()) {
            $conn->send(json_encode([
                'type' => 'new_notification',
                'notification' => $notification,
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        }
        $stmt->close();
    }

    private function getCustomerIdFromConnection($conn) {
        foreach ($this->customerConnections as $customerId => $connections) {
            foreach ($connections as $connection) {
                if ($connection['conn'] === $conn) {
                    return $customerId;
                }
            }
        }
        return null;
    }

    private function getConnectionIdFromConnection($conn) {
        foreach ($this->customerConnections as $customerId => $connections) {
            foreach ($connections as $connection) {
                if ($connection['conn'] === $conn) {
                    return $connection['connection_id'];
                }
            }
        }
        return null;
    }

    private function sendError($conn, $message) {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    private function getClientIpAddress() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // Method to broadcast notifications to specific customers
    public function broadcastNotificationToCustomer($customerId, $notification) {
        if (isset($this->customerConnections[$customerId])) {
            foreach ($this->customerConnections[$customerId] as $connection) {
                $connection['conn']->send(json_encode([
                    'type' => 'new_notification',
                    'notification' => $notification,
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
            }
            
            // Mark as real-time sent
            if (isset($notification['id'])) {
                $stmt = $this->database->prepare("UPDATE customer_notifications SET is_real_time_sent = TRUE WHERE id = ?");
                $stmt->bind_param("i", $notification['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Start the WebSocket server
$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationWebSocketServer()
        )
    ),
    $port
);

echo "WebSocket server running on port {$port}\n";
$server->run();
?>