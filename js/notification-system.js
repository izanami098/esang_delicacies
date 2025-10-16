/**
 * Real-time Notification System for Customer Dashboard
 * Handles WebSocket connections, notification display, and user interactions
 */

class NotificationSystem {
    constructor(options = {}) {
        // Determine WebSocket URL based on current protocol
        const defaultWsUrl = window.location.protocol === 'https:' ? 
            'wss://esangdelicacies.com:8080' : 
            'ws://localhost:8080';
        this.wsUrl = options.wsUrl || defaultWsUrl;
        this.apiBase = options.apiBase || '/api';
        this.profileHash = options.profileHash;
        this.sessionId = options.sessionId;
        
        // WebSocket connection
        this.websocket = null;
        this.connectionAttempts = 0;
        this.maxConnectionAttempts = 5;
        this.reconnectDelay = 2000;
        
        // Notification state
        this.notifications = [];
        this.unreadCount = 0;
        this.isDropdownOpen = false;
        
        // UI elements
        this.bellIcon = null;
        this.badge = null;
        this.dropdown = null;
        this.notificationsList = null;
        this.soundEnabled = true;
        
        this.init();
    }

    async init() {
        try {
            await this.initializeUI();
            await this.loadExistingNotifications();
            this.connectWebSocket();
            this.setupEventListeners();
        } catch (error) {
            console.error('Failed to initialize notification system:', error);
        }
    }

    initializeUI() {
        return new Promise((resolve) => {
            // Create notification bell icon if it doesn't exist
            if (!document.getElementById('notification-bell')) {
                this.createNotificationBell();
            }
            
            this.bellIcon = document.getElementById('notification-bell');
            this.badge = document.getElementById('notification-badge');
            this.dropdown = document.getElementById('notification-dropdown');
            this.notificationsList = document.getElementById('notifications-list');
            
            resolve();
        });
    }

    createNotificationBell() {
        const bellContainer = document.createElement('div');
        bellContainer.className = 'notification-container position-relative';
        bellContainer.innerHTML = `
            <button id="notification-bell" class="btn btn-link position-relative p-2" 
                    title="Notifications" aria-label="Show notifications">
                <i class="fas fa-bell fs-5"></i>
                <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                    0
                </span>
            </button>
            
            <div id="notification-dropdown" class="notification-dropdown position-absolute d-none">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Notifications</h6>
                        <button id="mark-all-read" class="btn btn-sm btn-outline-primary">
                            Mark All Read
                        </button>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <div id="notifications-list">
                            <div class="text-center p-3 text-muted">
                                <i class="fas fa-bell-slash mb-2"></i>
                                <p class="mb-0">No notifications yet</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="#" id="view-all-notifications" class="text-decoration-none">
                            View All Notifications
                        </a>
                    </div>
                </div>
            </div>
        `;

        // Insert into navbar or header
        const navbar = document.querySelector('.navbar-nav') || document.querySelector('header') || document.body;
        navbar.appendChild(bellContainer);
    }

    async loadExistingNotifications() {
        try {
            const response = await fetch(`${this.apiBase}/get_notifications.php?limit=10&unread_only=false`);
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.data.notifications || [];
                this.unreadCount = data.data.unread_count || 0;
                this.updateUI();
            }
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    connectWebSocket() {
        if (this.connectionAttempts >= this.maxConnectionAttempts) {
            console.error('Max WebSocket connection attempts reached');
            return;
        }

        this.connectionAttempts++;
        
        try {
            this.websocket = new WebSocket(this.wsUrl);
            
            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                this.connectionAttempts = 0;
                this.authenticate();
                this.showConnectionStatus('Connected', 'success');
            };

            this.websocket.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    this.handleWebSocketMessage(message);
                } catch (error) {
                    console.error('Failed to parse WebSocket message:', error);
                }
            };

            this.websocket.onclose = () => {
                console.log('WebSocket disconnected');
                this.showConnectionStatus('Disconnected', 'warning');
                
                // Attempt to reconnect
                setTimeout(() => {
                    this.connectWebSocket();
                }, this.reconnectDelay);
            };

            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
                this.showConnectionStatus('Connection Error', 'danger');
            };

        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
        }
    }

    authenticate() {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify({
                type: 'authenticate',
                session_id: this.sessionId,
                profile_hash: this.profileHash
            }));
        }
    }

    handleWebSocketMessage(message) {
        switch (message.type) {
            case 'connection_established':
                console.log('WebSocket connection established');
                break;
                
            case 'authentication_success':
                console.log('WebSocket authenticated successfully');
                this.showConnectionStatus('Authenticated', 'success');
                break;
                
            case 'new_notification':
                this.handleNewNotification(message.notification);
                break;
                
            case 'notification_marked_read':
                this.handleNotificationMarkedRead(message.notification_id);
                break;
                
            case 'pong':
                // Handle ping/pong for connection health
                break;
                
            case 'error':
                console.error('WebSocket error:', message.message);
                this.showConnectionStatus(message.message, 'danger');
                break;
        }
    }

    handleNewNotification(notification) {
        // Add to notifications array
        this.notifications.unshift(notification);
        
        // Update unread count
        if (!notification.is_read) {
            this.unreadCount++;
        }
        
        // Update UI
        this.updateUI();
        
        // Show notification popup
        this.showNotificationPopup(notification);
        
        // Play sound if enabled
        if (this.soundEnabled) {
            this.playNotificationSound();
        }
        
        // Show browser notification if permission granted
        this.showBrowserNotification(notification);
    }

    handleNotificationMarkedRead(notificationId) {
        // Find and update the notification
        const notification = this.notifications.find(n => n.id == notificationId);
        if (notification && !notification.is_read) {
            notification.is_read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateUI();
        }
    }

    updateUI() {
        // Update badge
        if (this.badge) {
            if (this.unreadCount > 0) {
                this.badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                this.badge.classList.remove('d-none');
            } else {
                this.badge.classList.add('d-none');
            }
        }
        
        // Update bell icon animation
        if (this.bellIcon) {
            if (this.unreadCount > 0) {
                this.bellIcon.classList.add('has-notifications');
            } else {
                this.bellIcon.classList.remove('has-notifications');
            }
        }
        
        // Update notifications list
        this.updateNotificationsList();
    }

    updateNotificationsList() {
        if (!this.notificationsList) return;
        
        if (this.notifications.length === 0) {
            this.notificationsList.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-bell-slash mb-2"></i>
                    <p class="mb-0">No notifications yet</p>
                </div>
            `;
            return;
        }
        
        const notificationsHtml = this.notifications.slice(0, 10).map(notification => {
            const timeAgo = this.formatTimeAgo(new Date(notification.created_at));
            const priorityClass = this.getPriorityClass(notification.priority);
            const readClass = notification.is_read ? '' : 'fw-bold';
            
            return `
                <div class="notification-item p-3 border-bottom ${readClass}" 
                     data-notification-id="${notification.id}"
                     data-is-read="${notification.is_read}">
                    <div class="d-flex align-items-start">
                        <div class="notification-icon me-2">
                            ${this.getNotificationIcon(notification.notification_type)}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="mb-1 ${readClass}">${this.escapeHtml(notification.title)}</h6>
                                <span class="badge ${priorityClass}">${notification.priority}</span>
                            </div>
                            <p class="mb-1 text-muted small">${this.escapeHtml(notification.message)}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">${timeAgo}</small>
                                ${!notification.is_read ? `
                                    <button class="btn btn-sm btn-outline-primary mark-read-btn" 
                                            data-notification-id="${notification.id}">
                                        Mark Read
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        this.notificationsList.innerHTML = notificationsHtml;
    }

    setupEventListeners() {
        // Bell icon click
        if (this.bellIcon) {
            this.bellIcon.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDropdown();
            });
        }
        
        // Mark all read button
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }
        
        // Individual mark read buttons (delegated event)
        if (this.notificationsList) {
            this.notificationsList.addEventListener('click', (e) => {
                if (e.target.classList.contains('mark-read-btn')) {
                    const notificationId = e.target.dataset.notificationId;
                    this.markNotificationAsRead(notificationId);
                }
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification-container')) {
                this.closeDropdown();
            }
        });
        
        // Request browser notification permission
        this.requestNotificationPermission();
    }

    toggleDropdown() {
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        if (this.dropdown) {
            this.dropdown.classList.remove('d-none');
            this.isDropdownOpen = true;
        }
    }

    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.classList.add('d-none');
            this.isDropdownOpen = false;
        }
    }

    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch(`${this.apiBase}/get_notifications.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: parseInt(notificationId)
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.handleNotificationMarkedRead(notificationId);
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async markAllNotificationsAsRead() {
        try {
            const response = await fetch(`${this.apiBase}/get_notifications.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_all_read'
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.notifications.forEach(n => n.is_read = true);
                this.unreadCount = 0;
                this.updateUI();
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    showNotificationPopup(notification) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast notification-toast position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 1055;';
        
        toast.innerHTML = `
            <div class="toast-header">
                <div class="notification-icon me-2">
                    ${this.getNotificationIcon(notification.notification_type)}
                </div>
                <strong class="me-auto">${this.escapeHtml(notification.title)}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${this.escapeHtml(notification.message)}
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Initialize and show toast
        if (window.bootstrap) {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove from DOM after hiding
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        } else {
            // Fallback without Bootstrap
            toast.style.display = 'block';
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    }

    showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const browserNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/assets/images/logo-small.png',
                badge: '/assets/images/logo-small.png',
                tag: `notification-${notification.id}`
            });
            
            browserNotification.onclick = () => {
                window.focus();
                this.openDropdown();
                browserNotification.close();
            };
            
            setTimeout(() => {
                browserNotification.close();
            }, 5000);
        }
    }

    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    playNotificationSound() {
        try {
            const audio = new Audio('/assets/sounds/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => {
                // Ignore autoplay policy errors
                console.log('Could not play notification sound:', e.message);
            });
        } catch (error) {
            console.log('Notification sound not available');
        }
    }

    showConnectionStatus(status, type) {
        // Could show a small status indicator in the UI
        console.log(`Connection status: ${status} (${type})`);
    }

    // Utility methods
    getNotificationIcon(type) {
        const icons = {
            'order_accepted': '<i class="fas fa-check-circle text-success"></i>',
            'order_preparing': '<i class="fas fa-utensils text-warning"></i>',
            'order_cooking': '<i class="fas fa-fire text-orange"></i>',
            'order_ready': '<i class="fas fa-bell text-primary"></i>',
            'order_out_for_delivery': '<i class="fas fa-truck text-info"></i>',
            'order_delivered': '<i class="fas fa-check-double text-success"></i>',
            'order_cancelled': '<i class="fas fa-times-circle text-danger"></i>',
            'payment_received': '<i class="fas fa-credit-card text-success"></i>',
            'general': '<i class="fas fa-info-circle text-secondary"></i>'
        };
        
        return icons[type] || icons['general'];
    }

    getPriorityClass(priority) {
        const classes = {
            'low': 'bg-secondary',
            'medium': 'bg-primary',
            'high': 'bg-warning text-dark',
            'urgent': 'bg-danger'
        };
        
        return classes[priority] || classes['medium'];
    }

    formatTimeAgo(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public methods
    destroy() {
        if (this.websocket) {
            this.websocket.close();
        }
    }

    refreshNotifications() {
        this.loadExistingNotifications();
    }

    setSoundEnabled(enabled) {
        this.soundEnabled = enabled;
    }
}

// CSS for notifications (add to your main CSS file)
const notificationStyles = `
.notification-container {
    display: inline-block;
}

.notification-dropdown {
    top: 100%;
    right: 0;
    width: 350px;
    z-index: 1050;
}

.notification-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.fw-bold {
    background-color: #fff3cd;
}

.notification-toast {
    min-width: 300px;
}

.has-notifications .fa-bell {
    animation: bell-shake 0.5s ease-in-out;
    color: #dc3545;
}

@keyframes bell-shake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-10deg); }
    75% { transform: rotate(10deg); }
}

.text-orange {
    color: #fd7e14 !important;
}
`;

// Inject styles
if (!document.getElementById('notification-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'notification-styles';
    styleSheet.textContent = notificationStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other scripts
window.NotificationSystem = NotificationSystem;