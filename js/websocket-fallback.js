/**
 * WebSocket Fallback for Shared Hosting
 * Uses HTTP polling when WebSocket server is not available
 * Perfect for deployment on shared hosting like Truehost
 */

class WebSocketFallback {
    constructor(options = {}) {
        this.options = {
            pollInterval: 3000, // 3 seconds
            maxRetries: 5,
            apiEndpoint: '/api/get_notifications.php',
            wsEndpoint: null, // Will try WebSocket first if available
            ...options
        };
        
        this.isConnected = false;
        this.isPolling = false;
        this.retryCount = 0;
        this.listeners = {};
        this.lastNotificationId = 0;
        this.userId = null;
        this.connectionType = null; // 'websocket' or 'polling'
        this.ws = null;
        this.pollTimer = null;
    }

    /**
     * Initialize connection - tries WebSocket first, falls back to polling
     */
    async connect(userId) {
        this.userId = userId;
        
        // Try WebSocket first
        if (this.options.wsEndpoint && await this.tryWebSocket()) {
            this.connectionType = 'websocket';
            this.trigger('connected', { type: 'websocket' });
            return;
        }
        
        // Fallback to HTTP polling
        this.connectionType = 'polling';
        this.startPolling();
        this.trigger('connected', { type: 'polling' });
    }

    /**
     * Try to establish WebSocket connection
     */
    async tryWebSocket() {
        return new Promise((resolve) => {
            try {
                this.ws = new WebSocket(this.options.wsEndpoint);
                
                const timeout = setTimeout(() => {
                    this.ws.close();
                    resolve(false);
                }, 5000); // 5 second timeout
                
                this.ws.onopen = () => {
                    clearTimeout(timeout);
                    this.isConnected = true;
                    this.setupWebSocketHandlers();
                    resolve(true);
                };
                
                this.ws.onerror = () => {
                    clearTimeout(timeout);
                    resolve(false);
                };
                
            } catch (error) {
                resolve(false);
            }
        });
    }

    /**
     * Setup WebSocket event handlers
     */
    setupWebSocketHandlers() {
        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleMessage(data);
            } catch (error) {
                console.error('Error parsing WebSocket message:', error);
            }
        };

        this.ws.onclose = () => {
            this.isConnected = false;
            this.trigger('disconnected');
            
            // Switch to polling if WebSocket fails
            if (this.connectionType === 'websocket') {
                console.log('WebSocket disconnected, switching to polling...');
                this.connectionType = 'polling';
                this.startPolling();
            }
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
        
        // Join user room if WebSocket is connected
        if (this.userId) {
            this.send({
                type: 'join_room',
                data: { room: `user_${this.userId}` }
            });
        }
    }

    /**
     * Start HTTP polling for notifications
     */
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.poll();
    }

    /**
     * Stop HTTP polling
     */
    stopPolling() {
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    }

    /**
     * Perform single poll request
     */
    async poll() {
        if (!this.isPolling) return;

        try {
            const response = await fetch(this.options.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: this.userId,
                    last_notification_id: this.lastNotificationId,
                    type: 'poll'
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.notifications) {
                this.handleNotifications(data.notifications);
            }
            
            this.retryCount = 0; // Reset retry count on success
            
        } catch (error) {
            console.error('Polling error:', error);
            this.retryCount++;
            
            if (this.retryCount >= this.options.maxRetries) {
                this.trigger('error', { message: 'Max polling retries reached' });
                this.stopPolling();
                return;
            }
        }

        // Schedule next poll
        if (this.isPolling) {
            this.pollTimer = setTimeout(() => this.poll(), this.options.pollInterval);
        }
    }

    /**
     * Handle notifications from polling
     */
    handleNotifications(notifications) {
        notifications.forEach(notification => {
            if (notification.id > this.lastNotificationId) {
                this.lastNotificationId = notification.id;
            }
            
            this.handleMessage({
                type: notification.type || 'notification',
                title: notification.title,
                message: notification.message,
                notificationType: notification.notification_type || 'info',
                data: notification.data ? JSON.parse(notification.data) : {},
                timestamp: notification.created_at
            });
        });
    }

    /**
     * Handle incoming messages (from WebSocket or polling)
     */
    handleMessage(data) {
        this.trigger('message', data);
        
        // Trigger specific event types
        if (data.type) {
            this.trigger(data.type, data);
        }
    }

    /**
     * Send message (WebSocket only)
     */
    send(message) {
        if (this.connectionType === 'websocket' && this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(message));
        } else {
            console.warn('Cannot send message: not connected via WebSocket');
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
     * Get connection status
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            connectionType: this.connectionType,
            isPolling: this.isPolling,
            retryCount: this.retryCount
        };
    }

    /**
     * Disconnect
     */
    disconnect() {
        this.stopPolling();
        
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
        
        this.isConnected = false;
        this.trigger('disconnected');
    }
}

// Enhanced notification system for shared hosting
class EsangNotificationSystem {
    constructor() {
        // Try WebSocket first, fallback to polling
        const wsUrl = this.getWebSocketUrl();
        
        this.client = new WebSocketFallback({
            wsEndpoint: wsUrl,
            apiEndpoint: this.getApiEndpoint() + '/get_notifications.php',
            pollInterval: 5000 // Poll every 5 seconds
        });
        
        this.setupEventHandlers();
    }

    /**
     * Get WebSocket URL based on environment
     */
    getWebSocketUrl() {
        const isProduction = window.location.hostname.includes('esangdelicacies.com');
        
        if (isProduction) {
            // Try secure WebSocket first
            return `wss://${window.location.hostname}:8080`;
        } else {
            return `ws://${window.location.hostname}:8080`;
        }
    }

    /**
     * Get API endpoint
     */
    getApiEndpoint() {
        return window.location.origin + (window.location.pathname.includes('esang_delicacies') ? '/esang_delicacies/api' : '/api');
    }

    /**
     * Initialize the notification system
     */
    async init(userId) {
        await this.client.connect(userId);
        console.log(`Notification system connected via ${this.client.connectionType}`);
    }

    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        this.client.on('connected', (data) => {
            this.showConnectionStatus(`Connected via ${data.type}`, 'success');
        });

        this.client.on('disconnected', () => {
            this.showConnectionStatus('Disconnected', 'warning');
        });

        this.client.on('error', (error) => {
            this.showConnectionStatus('Connection Error', 'error');
            console.error('Notification system error:', error);
        });

        this.client.on('notification', (data) => {
            this.showNotification(data);
        });

        this.client.on('order_update', (data) => {
            this.handleOrderUpdate(data);
        });

        this.client.on('rider_update', (data) => {
            this.handleRiderUpdate(data);
        });
    }

    /**
     * Show notification to user
     */
    showNotification(data) {
        // Browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(data.title || 'Notification', {
                body: data.message,
                icon: '/assets/images/logo.png'
            });
        }

        // In-app notification
        this.displayInAppNotification(data);
        
        // Update notification badge
        this.updateNotificationBadge();
    }

    /**
     * Display in-app notification
     */
    displayInAppNotification(data) {
        const notificationContainer = this.getOrCreateNotificationContainer();
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${data.notificationType || 'info'}`;
        notification.innerHTML = `
            <div class="notification-content">
                <strong>${data.title || 'Notification'}</strong>
                <p>${data.message}</p>
                <small>${new Date(data.timestamp).toLocaleTimeString()}</small>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        notificationContainer.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Get or create notification container
     */
    getOrCreateNotificationContainer() {
        let container = document.getElementById('notification-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
        
        return container;
    }

    /**
     * Handle order updates
     */
    handleOrderUpdate(data) {
        console.log('Order update:', data);
        
        // Refresh order list if on orders page
        if (window.location.pathname.includes('orders')) {
            this.refreshOrderList();
        }
    }

    /**
     * Handle rider updates
     */
    handleRiderUpdate(data) {
        console.log('Rider update:', data);
        
        // Update rider tracking if on tracking page
        if (typeof updateRiderLocation === 'function') {
            updateRiderLocation(data);
        }
    }

    /**
     * Show connection status
     */
    showConnectionStatus(message, type) {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            statusElement.textContent = message;
            statusElement.className = `status-${type}`;
        }
        console.log(`Connection: ${message}`);
    }

    /**
     * Update notification badge
     */
    updateNotificationBadge() {
        // Implementation depends on your UI
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            // Update badge count
        }
    }

    /**
     * Refresh order list
     */
    refreshOrderList() {
        // Implementation depends on your order list structure
        if (typeof loadOrders === 'function') {
            loadOrders();
        }
    }

    /**
     * Get connection status
     */
    getStatus() {
        return this.client.getStatus();
    }
}

// Global instance
let esangNotifications = null;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Initialize notification system
    esangNotifications = new EsangNotificationSystem();
    
    // Get user ID from page (you may need to adjust this)
    const userId = document.body.getAttribute('data-user-id') || 
                  document.querySelector('[data-user-id]')?.getAttribute('data-user-id');
    
    if (userId) {
        esangNotifications.init(userId);
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (esangNotifications) {
        esangNotifications.client.disconnect();
    }
});