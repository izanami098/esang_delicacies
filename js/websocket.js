/**
 * WebSocket Client for Esang Delicacies
 * Handles real-time communication for notifications, orders, etc.
 */

class WebSocketClient {
    constructor(url, options = {}) {
        this.url = url;
        this.options = {
            reconnectInterval: 5000,
            maxReconnectAttempts: 10,
            ...options
        };
        this.reconnectAttempts = 0;
        this.ws = null;
        this.isConnected = false;
        this.messageQueue = [];
        this.listeners = {};
    }

    /**
     * Connect to WebSocket server
     */
    connect() {
        try {
            this.ws = new WebSocket(this.url);
            
            this.ws.onopen = (event) => {
                console.log('WebSocket connected');
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.processMessageQueue();
                this.trigger('open', event);
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('WebSocket message received:', data);
                    this.trigger('message', data);
                    
                    // Handle specific message types
                    if (data.type) {
                        this.trigger(data.type, data);
                    }
                } catch (error) {
                    console.error('Error parsing WebSocket message:', error);
                }
            };

            this.ws.onclose = (event) => {
                console.log('WebSocket disconnected:', event.code, event.reason);
                this.isConnected = false;
                this.trigger('close', event);
                
                // Attempt to reconnect if not a clean close
                if (event.code !== 1000 && this.reconnectAttempts < this.options.maxReconnectAttempts) {
                    this.scheduleReconnect();
                }
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.trigger('error', error);
            };

        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.scheduleReconnect();
        }
    }

    /**
     * Schedule reconnection attempt
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            return;
        }

        this.reconnectAttempts++;
        console.log(`Attempting to reconnect in ${this.options.reconnectInterval}ms (attempt ${this.reconnectAttempts})`);
        
        setTimeout(() => {
            this.connect();
        }, this.options.reconnectInterval);
    }

    /**
     * Send message to server
     */
    send(message) {
        const messageStr = typeof message === 'string' ? message : JSON.stringify(message);
        
        if (this.isConnected && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(messageStr);
        } else {
            // Queue message for when connection is restored
            this.messageQueue.push(messageStr);
        }
    }

    /**
     * Process queued messages
     */
    processMessageQueue() {
        while (this.messageQueue.length > 0 && this.isConnected) {
            const message = this.messageQueue.shift();
            this.ws.send(message);
        }
    }

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    }

    /**
     * Trigger event
     */
    trigger(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} callback:`, error);
                }
            });
        }
    }

    /**
     * Close connection
     */
    disconnect() {
        if (this.ws) {
            this.ws.close(1000, 'Client disconnecting');
        }
    }

    /**
     * Get connection status
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            readyState: this.ws ? this.ws.readyState : null,
            reconnectAttempts: this.reconnectAttempts
        };
    }
}

// Application-specific WebSocket manager
class EsangWebSocket {
    constructor() {
        // Adjust the WebSocket URL based on your server setup
        const wsUrl = `ws://${window.location.hostname}:8080`;
        this.client = new WebSocketClient(wsUrl);
        this.setupEventHandlers();
    }

    /**
     * Initialize WebSocket connection
     */
    init() {
        this.client.connect();
    }

    /**
     * Setup application-specific event handlers
     */
    setupEventHandlers() {
        // Handle connection events
        this.client.on('open', () => {
            this.showConnectionStatus('Connected', 'success');
        });

        this.client.on('close', () => {
            this.showConnectionStatus('Disconnected', 'warning');
        });

        this.client.on('error', () => {
            this.showConnectionStatus('Connection Error', 'error');
        });

        // Handle application-specific messages
        this.client.on('notification', (data) => {
            this.handleNotification(data);
        });

        this.client.on('order_update', (data) => {
            this.handleOrderUpdate(data);
        });

        this.client.on('rider_update', (data) => {
            this.handleRiderUpdate(data);
        });
    }

    /**
     * Handle incoming notifications
     */
    handleNotification(data) {
        // Update notification UI
        if (typeof updateNotificationBadge === 'function') {
            updateNotificationBadge();
        }
        
        // Show notification
        this.showNotification(data.title, data.message, data.type);
    }

    /**
     * Handle order updates
     */
    handleOrderUpdate(data) {
        console.log('Order update received:', data);
        
        // Refresh order list if on orders page
        if (window.location.pathname.includes('orders')) {
            if (typeof refreshOrderList === 'function') {
                refreshOrderList();
            }
        }
    }

    /**
     * Handle rider updates
     */
    handleRiderUpdate(data) {
        console.log('Rider update received:', data);
        
        // Update rider status if on rider tracking page
        if (typeof updateRiderLocation === 'function') {
            updateRiderLocation(data);
        }
    }

    /**
     * Show connection status
     */
    showConnectionStatus(message, type) {
        const statusElement = document.getElementById('websocket-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `status-${type}`;
        }
        console.log(`WebSocket: ${message}`);
    }

    /**
     * Show notification to user
     */
    showNotification(title, message, type = 'info') {
        // You can integrate this with your existing notification system
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/assets/images/logo.png'
            });
        }
        
        // Also show in-app notification
        console.log(`${type.toUpperCase()}: ${title} - ${message}`);
    }

    /**
     * Send message to server
     */
    send(type, data) {
        this.client.send({
            type: type,
            data: data,
            timestamp: Date.now()
        });
    }

    /**
     * Join a room (for targeted notifications)
     */
    joinRoom(roomName) {
        this.send('join_room', { room: roomName });
    }

    /**
     * Leave a room
     */
    leaveRoom(roomName) {
        this.send('leave_room', { room: roomName });
    }
}

// Global WebSocket instance
let esangWS = null;

// Initialize WebSocket when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Initialize WebSocket
    esangWS = new EsangWebSocket();
    esangWS.init();
    
    // Join user-specific room if user is logged in
    const userId = document.body.getAttribute('data-user-id');
    if (userId) {
        esangWS.joinRoom(`user_${userId}`);
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (esangWS) {
        esangWS.client.disconnect();
    }
});