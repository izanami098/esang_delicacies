<?php
/**
 * WebSocket Server for Esang Delicacies
 * Handles real-time communication for notifications, orders, and rider updates
 * 
 * To install dependencies, run:
 * composer require reactphp/socket
 * composer require ratchet/pawl
 * composer require textalk/websocket-client
 * 
 * Or use the simple server below that doesn't require external libraries
 */

require_once 'includes/config.php';

class EsangWebSocketServer {
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    private $rooms = [];
    
    public function __construct($host = 'localhost', $port = 8080) {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function start() {
        // Create socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        if (!$this->socket) {
            die("Failed to create socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        
        // Bind socket
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("Failed to bind socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        // Listen for connections
        if (!socket_listen($this->socket, 10)) {
            die("Failed to listen on socket: " . socket_strerror(socket_last_error()) . "\n");
        }
        
        echo "WebSocket server started on {$this->host}:{$this->port}\n";
        
        // Main server loop
        while (true) {
            $read = [$this->socket];
            
            // Add all client sockets to read array
            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }
            
            $write = null;
            $except = null;
            
            // Select sockets that are ready
            if (socket_select($read, $write, $except, 0, 10) < 1) {
                continue;
            }
            
            // Handle new connections
            if (in_array($this->socket, $read)) {
                $this->handleNewConnection();
                $key = array_search($this->socket, $read);
                unset($read[$key]);
            }
            
            // Handle client messages
            foreach ($read as $clientSocket) {
                $this->handleClientMessage($clientSocket);
            }
        }
    }
    
    private function handleNewConnection() {
        $clientSocket = socket_accept($this->socket);
        
        if (!$clientSocket) {
            return;
        }
        
        // Perform WebSocket handshake
        $request = socket_read($clientSocket, 1024);
        $this->performHandshake($request, $clientSocket);
        
        // Store client
        $clientId = uniqid();
        $this->clients[$clientId] = [
            'socket' => $clientSocket,
            'id' => $clientId,
            'rooms' => []
        ];
        
        echo "New client connected: {$clientId}\n";
        
        // Send welcome message
        $this->sendToClient($clientId, [
            'type' => 'welcome',
            'message' => 'Connected to Esang Delicacies WebSocket server',
            'clientId' => $clientId
        ]);
    }
    
    private function performHandshake($request, $clientSocket) {
        $headers = [];
        $lines = explode("\n", $request);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        if (!isset($headers['Sec-WebSocket-Key'])) {
            socket_close($clientSocket);
            return;
        }
        
        $acceptKey = base64_encode(sha1($headers['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                   "Upgrade: websocket\r\n" .
                   "Connection: Upgrade\r\n" .
                   "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";
        
        socket_write($clientSocket, $response);
    }
    
    private function handleClientMessage($clientSocket) {
        $data = socket_read($clientSocket, 1024);
        
        if ($data === false) {
            // Client disconnected
            $this->removeClient($clientSocket);
            return;
        }
        
        // Decode WebSocket frame
        $message = $this->decodeFrame($data);
        
        if ($message === false) {
            return;
        }
        
        // Find client ID
        $clientId = null;
        foreach ($this->clients as $id => $client) {
            if ($client['socket'] === $clientSocket) {
                $clientId = $id;
                break;
            }
        }
        
        if (!$clientId) {
            return;
        }
        
        echo "Message from {$clientId}: {$message}\n";
        
        // Parse JSON message
        $messageData = json_decode($message, true);
        
        if ($messageData) {
            $this->processMessage($clientId, $messageData);
        }
    }
    
    private function processMessage($clientId, $data) {
        $type = $data['type'] ?? '';
        
        switch ($type) {
            case 'join_room':
                $room = $data['data']['room'] ?? '';
                if ($room) {
                    $this->joinRoom($clientId, $room);
                }
                break;
                
            case 'leave_room':
                $room = $data['data']['room'] ?? '';
                if ($room) {
                    $this->leaveRoom($clientId, $room);
                }
                break;
                
            case 'ping':
                $this->sendToClient($clientId, ['type' => 'pong']);
                break;
                
            case 'broadcast':
                $this->broadcast($data['data'], $clientId);
                break;
                
            default:
                echo "Unknown message type: {$type}\n";
        }
    }
    
    private function joinRoom($clientId, $room) {
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }
        
        $this->rooms[$room][] = $clientId;
        $this->clients[$clientId]['rooms'][] = $room;
        
        echo "Client {$clientId} joined room: {$room}\n";
        
        $this->sendToClient($clientId, [
            'type' => 'room_joined',
            'room' => $room
        ]);
    }
    
    private function leaveRoom($clientId, $room) {
        if (isset($this->rooms[$room])) {
            $key = array_search($clientId, $this->rooms[$room]);
            if ($key !== false) {
                unset($this->rooms[$room][$key]);
            }
        }
        
        if (isset($this->clients[$clientId])) {
            $key = array_search($room, $this->clients[$clientId]['rooms']);
            if ($key !== false) {
                unset($this->clients[$clientId]['rooms'][$key]);
            }
        }
        
        echo "Client {$clientId} left room: {$room}\n";
    }
    
    private function broadcast($message, $excludeClientId = null) {
        foreach ($this->clients as $clientId => $client) {
            if ($clientId !== $excludeClientId) {
                $this->sendToClient($clientId, $message);
            }
        }
    }
    
    private function sendToRoom($room, $message) {
        if (!isset($this->rooms[$room])) {
            return;
        }
        
        foreach ($this->rooms[$room] as $clientId) {
            $this->sendToClient($clientId, $message);
        }
    }
    
    private function sendToClient($clientId, $message) {
        if (!isset($this->clients[$clientId])) {
            return;
        }
        
        $messageStr = is_array($message) ? json_encode($message) : $message;
        $frame = $this->encodeFrame($messageStr);
        
        socket_write($this->clients[$clientId]['socket'], $frame);
    }
    
    private function removeClient($clientSocket) {
        foreach ($this->clients as $clientId => $client) {
            if ($client['socket'] === $clientSocket) {
                // Remove from all rooms
                foreach ($client['rooms'] as $room) {
                    $this->leaveRoom($clientId, $room);
                }
                
                socket_close($clientSocket);
                unset($this->clients[$clientId]);
                echo "Client {$clientId} disconnected\n";
                break;
            }
        }
    }
    
    private function decodeFrame($data) {
        if (strlen($data) < 2) {
            return false;
        }
        
        $firstByte = ord($data[0]);
        $secondByte = ord($data[1]);
        
        $opcode = $firstByte & 0x0F;
        $masked = $secondByte & 0x80;
        $payloadLength = $secondByte & 0x7F;
        
        $offset = 2;
        
        if ($payloadLength == 126) {
            $payloadLength = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength == 127) {
            $payloadLength = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }
        
        if ($masked) {
            $mask = substr($data, $offset, 4);
            $offset += 4;
        }
        
        $payload = substr($data, $offset);
        
        if ($masked) {
            for ($i = 0; $i < strlen($payload); $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ ord($mask[$i % 4]));
            }
        }
        
        return $payload;
    }
    
    private function encodeFrame($payload) {
        $frame = '';
        $payloadLength = strlen($payload);
        
        // First byte: FIN (1) + RSV (000) + Opcode (0001)
        $frame .= chr(0x81);
        
        // Payload length
        if ($payloadLength < 126) {
            $frame .= chr($payloadLength);
        } elseif ($payloadLength < 65536) {
            $frame .= chr(126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(127) . pack('J', $payloadLength);
        }
        
        $frame .= $payload;
        
        return $frame;
    }
    
    // Public methods for sending notifications
    public function sendNotification($userId, $title, $message, $type = 'info') {
        $this->sendToRoom("user_{$userId}", [
            'type' => 'notification',
            'title' => $title,
            'message' => $message,
            'notificationType' => $type,
            'timestamp' => time()
        ]);
    }
    
    public function sendOrderUpdate($orderId, $status, $details = []) {
        $this->broadcast([
            'type' => 'order_update',
            'orderId' => $orderId,
            'status' => $status,
            'details' => $details,
            'timestamp' => time()
        ]);
    }
    
    public function sendRiderUpdate($riderId, $location, $status) {
        $this->broadcast([
            'type' => 'rider_update',
            'riderId' => $riderId,
            'location' => $location,
            'status' => $status,
            'timestamp' => time()
        ]);
    }
}

// Start server if run directly
if (php_sapi_name() === 'cli') {
    $server = new EsangWebSocketServer('0.0.0.0', 8080);
    $server->start();
}
?>