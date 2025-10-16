<?php
/**
 * Simple WebSocket Server for Testing
 * No database dependency - just for testing WebSocket functionality
 */

require_once 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SimpleWebSocketApp implements MessageComponentInterface {
    protected $clients;
    protected $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        echo "Simple WebSocket server initialized (no database required)\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        
        // Generate unique client ID
        $conn->clientId = uniqid('client_');
        $conn->rooms = [];
        
        echo "New connection! ({$conn->clientId})\n";
        
        // Send welcome message
        $this->sendToConnection($conn, [
            'type' => 'welcome',
            'message' => 'Connected to Esang Delicacies WebSocket server',
            'clientId' => $conn->clientId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Message from {$from->clientId}: {$msg}\n";
        
        try {
            $data = json_decode($msg, true);
            
            if (!$data) {
                $this->sendError($from, 'Invalid JSON message');
                return;
            }
            
            $this->handleMessage($from, $data);
            
        } catch (Exception $e) {
            echo "Error processing message: " . $e->getMessage() . "\n";
            $this->sendError($from, 'Internal server error');
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove from all rooms
        foreach ($conn->rooms as $room) {
            $this->leaveRoom($conn, $room);
        }
        
        // Remove the connection
        $this->clients->detach($conn);
        echo "Connection {$conn->clientId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    protected function handleMessage(ConnectionInterface $from, $data) {
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'join_room':
                $room = $data['data']['room'] ?? '';
                if ($room) {
                    $this->joinRoom($from, $room);
                }
                break;
                
            case 'leave_room':
                $room = $data['data']['room'] ?? '';
                if ($room) {
                    $this->leaveRoom($from, $room);
                }
                break;
                
            case 'ping':
                $this->sendToConnection($from, ['type' => 'pong', 'timestamp' => time()]);
                break;
                
            case 'broadcast':
                $message = $data['data'] ?? $data['message'] ?? 'Test broadcast message';
                $this->broadcast([
                    'type' => 'broadcast_message',
                    'message' => $message,
                    'from' => $from->clientId,
                    'timestamp' => date('Y-m-d H:i:s')
                ], $from);
                break;
                
            case 'test_notification':
                $this->sendNotificationTest($from);
                break;
                
            default:
                echo "Unknown message type: {$type}\n";
                $this->sendError($from, "Unknown message type: {$type}");
        }
    }
    
    protected function joinRoom(ConnectionInterface $conn, $room) {
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = new \SplObjectStorage;
        }
        
        $this->rooms[$room]->attach($conn);
        $conn->rooms[] = $room;
        
        echo "Client {$conn->clientId} joined room: {$room}\n";
        
        $this->sendToConnection($conn, [
            'type' => 'room_joined',
            'room' => $room,
            'message' => "Successfully joined room: {$room}",
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // Notify other room members
        $this->sendToRoom($room, [
            'type' => 'user_joined_room',
            'room' => $room,
            'user' => $conn->clientId,
            'timestamp' => date('Y-m-d H:i:s')
        ], $conn);
    }
    
    protected function leaveRoom(ConnectionInterface $conn, $room) {
        if (isset($this->rooms[$room])) {
            $this->rooms[$room]->detach($conn);
            
            // Clean up empty rooms
            if (count($this->rooms[$room]) === 0) {
                unset($this->rooms[$room]);
            }
        }
        
        // Remove room from connection's room list
        $conn->rooms = array_filter($conn->rooms, function($r) use ($room) {
            return $r !== $room;
        });
        
        echo "Client {$conn->clientId} left room: {$room}\n";
        
        $this->sendToConnection($conn, [
            'type' => 'room_left',
            'room' => $room,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    protected function broadcast($message, ConnectionInterface $excludeConn = null) {
        $count = 0;
        foreach ($this->clients as $client) {
            if ($client !== $excludeConn) {
                $this->sendToConnection($client, $message);
                $count++;
            }
        }
        echo "Broadcasted message to {$count} clients\n";
    }
    
    protected function sendToRoom($room, $message, ConnectionInterface $excludeConn = null) {
        if (!isset($this->rooms[$room])) {
            echo "Room {$room} not found\n";
            return;
        }
        
        $count = 0;
        foreach ($this->rooms[$room] as $client) {
            if ($client !== $excludeConn) {
                $this->sendToConnection($client, $message);
                $count++;
            }
        }
        
        echo "Sent message to {$count} clients in room {$room}\n";
    }
    
    protected function sendToConnection(ConnectionInterface $conn, $message) {
        try {
            $messageStr = is_array($message) ? json_encode($message) : $message;
            $conn->send($messageStr);
        } catch (Exception $e) {
            echo "Error sending message to {$conn->clientId}: " . $e->getMessage() . "\n";
        }
    }
    
    protected function sendError(ConnectionInterface $conn, $error) {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'message' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    protected function sendNotificationTest(ConnectionInterface $conn) {
        $this->sendToConnection($conn, [
            'type' => 'notification',
            'title' => 'Test Notification',
            'message' => 'This is a test notification from the WebSocket server!',
            'notificationType' => 'success',
            'timestamp' => time()
        ]);
        
        echo "Sent test notification to {$conn->clientId}\n";
    }
    
    // Public methods for external use
    public function sendNotificationToRoom($room, $title, $message, $type = 'info') {
        $this->sendToRoom($room, [
            'type' => 'notification',
            'title' => $title,
            'message' => $message,
            'notificationType' => $type,
            'timestamp' => time()
        ]);
    }
    
    public function broadcastOrderUpdate($orderId, $status, $details = []) {
        $this->broadcast([
            'type' => 'order_update',
            'orderId' => $orderId,
            'status' => $status,
            'details' => $details,
            'timestamp' => time()
        ]);
    }
}

// Create and start the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SimpleWebSocketApp()
        )
    ),
    8080,
    '0.0.0.0'  // Listen on all interfaces
);

echo "Starting Simple WebSocket server on port 8080...\n";
echo "You can test the connection at: ws://localhost:8080\n";
echo "Press Ctrl+C to stop the server\n\n";

// Show available test commands
echo "Test commands you can send:\n";
echo "1. Join a room: {\"type\":\"join_room\",\"data\":{\"room\":\"user_123\"}}\n";
echo "2. Send ping: {\"type\":\"ping\"}\n";
echo "3. Broadcast: {\"type\":\"broadcast\",\"message\":\"Hello everyone!\"}\n";
echo "4. Test notification: {\"type\":\"test_notification\"}\n";
echo "\n";

$server->run();
?>