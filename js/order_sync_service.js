/**
 * Order Status Synchronization Service
 * Handles real-time synchronization of order statuses across customer, admin, and order manager modules
 */
class OrderSyncService {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 15000; // 15 seconds
        this.lastSync = 0;
        this.isPolling = false;
        this.pollType = options.pollType || 'customer'; // customer, admin, order_manager
        this.callbacks = {
            onStatusUpdate: options.onStatusUpdate || function(){},
            onNotification: options.onNotification || function(){},
            onError: options.onError || function(){},
            onConnect: options.onConnect || function(){}
        };
        this.retryAttempts = 0;
        this.maxRetries = 3;
        this.isConnected = false;
        
        // Initialize service
        this.init();
    }
    
    init() {
        console.log(`Initializing Order Sync Service for ${this.pollType}`);
        this.startPolling();
        
        // Handle page visibility changes to optimize polling
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausePolling();
            } else {
                this.resumePolling();
            }
        });
        
        // Handle window focus/blur
        window.addEventListener('focus', () => {
            if (!this.isPolling) {
                this.resumePolling();
            }
        });
        
        window.addEventListener('beforeunload', () => {
            this.stopPolling();
        });
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        console.log('Starting order status polling...');
        
        // Initial poll
        this.poll();
        
        // Set up periodic polling
        this.pollingInterval = setInterval(() => {
            this.poll();
        }, this.pollInterval);
    }
    
    pausePolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        this.isPolling = false;
        console.log('Order status polling paused');
    }
    
    resumePolling() {
        if (!this.isPolling) {
            console.log('Resuming order status polling...');
            this.startPolling();
        }
    }
    
    stopPolling() {
        this.pausePolling();
        console.log('Order status polling stopped');
    }
    
    async poll() {
        if (!this.isPolling) return;
        
        try {
            const url = `/esang_delicacies/public/api/order_status_sync.php?poll_type=${this.pollType}&last_sync=${this.lastSync}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.handleSuccessfulPoll(result);
            } else {
                throw new Error(result.message || 'Polling failed');
            }
            
        } catch (error) {
            this.handlePollingError(error);
        }
    }
    
    handleSuccessfulPoll(result) {
        // Reset retry attempts on successful poll
        this.retryAttempts = 0;
        
        if (!this.isConnected) {
            this.isConnected = true;
            this.callbacks.onConnect();
            console.log('Order sync service connected');
        }
        
        // Update last sync timestamp
        if (result.server_time) {
            this.lastSync = result.server_time;
        }
        
        // Handle order updates
        if (result.has_updates && result.order_updates && result.order_updates.length > 0) {
            console.log(`Received ${result.order_updates.length} order updates`);
            result.order_updates.forEach(update => {
                this.callbacks.onStatusUpdate(update);
            });
        }
        
        // Handle notifications (customer only)
        if (result.notifications && result.notifications.length > 0) {
            console.log(`Received ${result.notifications.length} notifications`);
            result.notifications.forEach(notification => {
                this.callbacks.onNotification(notification);
            });
        }
    }
    
    handlePollingError(error) {
        console.error('Order sync polling error:', error);
        
        this.isConnected = false;
        this.retryAttempts++;
        
        if (this.retryAttempts >= this.maxRetries) {
            console.warn('Max retry attempts reached, reducing polling frequency');
            this.pollInterval = Math.min(this.pollInterval * 2, 60000); // Max 1 minute
            this.retryAttempts = 0;
        }
        
        this.callbacks.onError(error);
    }
    
    // Method to manually trigger an update (useful after making changes)
    async triggerSync() {
        console.log('Manually triggering sync...');
        await this.poll();
    }
    
    // Method to update poll type (useful for role switching)
    updatePollType(newPollType) {
        if (this.pollType !== newPollType) {
            console.log(`Updating poll type from ${this.pollType} to ${newPollType}`);
            this.pollType = newPollType;
            this.lastSync = 0; // Reset to get all updates
            this.triggerSync();
        }
    }
    
    // Method to send status update and immediately sync
    async updateOrderStatus(orderId, action) {
        try {
            const response = await fetch('/esang_delicacies/public/api/order_status_sync_dev.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    orderId: orderId,
                    action: action
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                console.log('Order status updated successfully:', result);
                
                // Trigger immediate sync to update all connected clients
                setTimeout(() => {
                    this.triggerSync();
                }, 500); // Small delay to ensure database has updated
                
                return result;
            } else {
                throw new Error(result.message || 'Failed to update order status');
            }
            
        } catch (error) {
            console.error('Error updating order status:', error);
            this.callbacks.onError(error);
            throw error;
        }
    }
    
    // Get current connection status
    getConnectionStatus() {
        return {
            isConnected: this.isConnected,
            isPolling: this.isPolling,
            pollType: this.pollType,
            lastSync: this.lastSync,
            retryAttempts: this.retryAttempts
        };
    }
    
    // Method to display connection status (for debugging)
    displayConnectionInfo() {
        const status = this.getConnectionStatus();
        console.table(status);
        return status;
    }
}

// Utility function to show notification to user
function showStatusNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `status-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button class="close-notification">&times;</button>
    `;
    
    // Add styles if not already present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .status-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #fff;
                border-left: 4px solid #007bff;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                border-radius: 4px;
                padding: 15px;
                max-width: 350px;
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            }
            .status-notification.success { border-left-color: #28a745; }
            .status-notification.error { border-left-color: #dc3545; }
            .status-notification.warning { border-left-color: #ffc107; }
            .status-notification i { margin-right: 8px; }
            .status-notification .close-notification {
                background: none;
                border: none;
                font-size: 18px;
                float: right;
                cursor: pointer;
                margin-top: -5px;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Handle close button
    const closeBtn = notification.querySelector('.close-notification');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OrderSyncService, showStatusNotification };
} else {
    window.OrderSyncService = OrderSyncService;
    window.showStatusNotification = showStatusNotification;
}