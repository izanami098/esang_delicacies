# Real-Time Customer Notification System - Complete Setup Guide

## Overview
This system provides real-time order status notifications to customers using WebSocket technology, ensuring they receive instant updates when their order status changes (accepted, preparing, cooking, ready, out for delivery, delivered, etc.).

## Features
✅ **Real-time notifications** via WebSocket connections  
✅ **Profile hash-based security** ensuring complete customer isolation  
✅ **Database triggers** automatically create notifications on order status changes  
✅ **Multiple notification types** (order status, payment received, general)  
✅ **Notification preferences** allowing customers to control what notifications they receive  
✅ **Browser notifications** with permission-based display  
✅ **Sound alerts** for new notifications  
✅ **Toast popups** for immediate visual feedback  
✅ **Notification history** with read/unread tracking  
✅ **Mobile responsive** notification interface  
✅ **Automatic cleanup** of old notifications

## System Requirements

### Server Requirements
- **PHP 7.4+** with mysqli extension
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Apache or Nginx** web server
- **Composer** for dependency management
- **Node.js** (optional, for advanced WebSocket management)

### PHP Extensions Required
- mysqli
- json
- mbstring
- curl (for API calls)

### Browser Requirements
- Modern browsers supporting WebSocket (Chrome 16+, Firefox 11+, Safari 7+, Edge 12+)
- JavaScript enabled

## Installation Steps

### Step 1: Install Dependencies

```bash
# Navigate to your project directory
cd /path/to/esang_delicacies

# Install Composer dependencies
composer install
```

If you don't have the composer.json dependencies installed yet, run:
```bash
composer require ratchet/pawl ratchet/rfc6455 react/socket textalk/websocket-client
```

### Step 2: Database Setup

1. **Run the notification schema SQL:**
```bash
mysql -u your_username -p your_database_name < sql/notifications_schema.sql
```

Or execute via phpMyAdmin or your preferred MySQL client:
- Open `sql/notifications_schema.sql`
- Execute all the SQL commands

This will create:
- `customer_notifications` table
- `customer_notification_preferences` table  
- `customer_websocket_connections` table
- Database triggers for automatic notification creation
- Cleanup procedures and events

2. **Verify database changes:**
```sql
-- Check if tables were created
SHOW TABLES LIKE '%notification%';

-- Check if triggers were created
SHOW TRIGGERS WHERE `Table` = 'orders';

-- Check if events are enabled
SHOW EVENTS;
```

### Step 3: File System Setup

Ensure all these files are in place:

**Core System Files:**
- `src/notifications/NotificationService.php` - Main notification management
- `src/notifications/OrderStatusNotifier.php` - Order status integration
- `websocket_server.php` - WebSocket server
- `assets/js/notification-system.js` - Frontend JavaScript

**API Endpoints:**
- `api/get_notifications.php` - Notification management API
- `api/notification_preferences.php` - Preference management API

**Updated Files:**
- `customer_dashboard.php` - Integrated with notification system
- `composer.json` - Updated dependencies

### Step 4: Start the WebSocket Server

Open a terminal/command prompt and navigate to your project directory:

```bash
# Start the WebSocket server
php websocket_server.php
```

You should see:
```
Notification WebSocket Server started...
WebSocket server running on port 8080
```

**Important:** Keep this terminal window open. The WebSocket server must run continuously.

### Step 5: Configure Web Server

**For Production (Recommended):**

Create a system service to auto-start the WebSocket server:

**On Ubuntu/Linux:**
```bash
# Create service file
sudo nano /etc/systemd/system/esang-notifications.service
```

Add content:
```ini
[Unit]
Description=Esang Delicacies Notification WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/esang_delicacies
ExecStart=/usr/bin/php websocket_server.php
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable esang-notifications
sudo systemctl start esang-notifications
sudo systemctl status esang-notifications
```

**For Development:**
Run in background:
```bash
# Linux/Mac
nohup php websocket_server.php > websocket.log 2>&1 &

# Windows (use separate Command Prompt)
start /B php websocket_server.php
```

### Step 6: Test the System

#### Test 1: Database Triggers
```sql
-- Insert a test order to trigger notification
INSERT INTO orders (customer_id, order_status, total_amount, order_date) 
VALUES (1, 'accepted', 150.00, NOW());

-- Check if notification was created
SELECT * FROM customer_notifications WHERE order_id = LAST_INSERT_ID();
```

#### Test 2: API Endpoints

Test notification API:
```bash
curl -X GET "http://your-domain.com/api/get_notifications.php?count=true" \
     -H "Cookie: PHPSESSID=your_session_id"
```

#### Test 3: Frontend Integration

1. Login as a customer
2. Open browser Developer Tools (F12)
3. Check Console for WebSocket connection messages:
   - "WebSocket connected"
   - "WebSocket authenticated successfully"
4. Check Network tab for WebSocket connection

#### Test 4: Real-time Notifications

1. Have the customer dashboard open in one browser
2. In another window/tool, update an order status in the database:
```sql
UPDATE orders SET order_status = 'preparing' WHERE id = [order_id];
```
3. The customer should immediately receive a notification

## Configuration Options

### WebSocket Server Configuration

Edit `websocket_server.php` to change:

```php
// Change the port (default: 8080)
$port = 8080;

// Update connection settings
$this->maxConnectionAttempts = 5;
$this->reconnectDelay = 2000; // milliseconds
```

### Notification Settings

Edit `src/notifications/NotificationService.php`:

```php
// Change cleanup period (default: 30 days)
public function cleanupOldNotifications($daysOld = 30)

// Modify notification priorities
$notification['priority'] = 'high'; // low, medium, high, urgent
```

### Frontend Configuration

In `customer_dashboard.php`, update JavaScript initialization:

```javascript
const notificationSystem = new NotificationSystem({
    wsUrl: 'ws://your-domain.com:8080', // Update for production
    apiBase: 'api',
    profileHash: '<?php echo $profileHash; ?>',
    sessionId: '<?php echo session_id(); ?>'
});
```

## Security Considerations

### 1. Profile Hash Validation
- All notifications are tied to customer profile hashes
- WebSocket connections require session validation
- API endpoints enforce profile hash authentication

### 2. Input Sanitization
- All user inputs are sanitized and validated
- SQL injection protection via prepared statements
- XSS prevention in notification display

### 3. Connection Security
- Session-based WebSocket authentication
- IP address and user agent logging
- Connection cleanup for inactive sessions

### 4. Production Security
```php
// In production, use secure WebSocket (WSS)
$wsUrl = 'wss://your-secure-domain.com:8080'

// Configure proper CORS headers
header('Access-Control-Allow-Origin: https://your-domain.com');
```

## Monitoring and Logging

### WebSocket Server Logs
- Connection attempts and authentications
- Error messages and debugging info
- Customer connection/disconnection events

### Database Logging
- All notification activities logged via ProfileHashManager
- Access patterns and security events tracked
- Performance metrics available

### Log Files Location
```
/path/to/esang_delicacies/logs/
├── websocket.log          # WebSocket server logs
├── notification.log       # Notification service logs
├── error.log              # PHP error logs
└── access.log             # Profile access logs
```

## Troubleshooting

### Issue 1: WebSocket Connection Failed
**Symptoms:** Console shows "WebSocket connection failed"
**Solutions:**
1. Check if WebSocket server is running: `ps aux | grep websocket`
2. Verify port 8080 is open: `netstat -ln | grep 8080`
3. Check firewall settings
4. Verify WebSocket URL in JavaScript

### Issue 2: No Notifications Appearing
**Symptoms:** No notifications show despite order status changes
**Solutions:**
1. Check database triggers: `SHOW TRIGGERS WHERE \`Table\` = 'orders';`
2. Verify customer has profile hash: `SELECT profile_hash FROM customer WHERE id = X;`
3. Check notification preferences: `SELECT * FROM customer_notification_preferences WHERE customer_id = X;`
4. Review error logs

### Issue 3: WebSocket Authentication Failed
**Symptoms:** "Invalid session or profile hash" error
**Solutions:**
1. Verify session is active: Check PHP session settings
2. Confirm profile hash matches: Compare database vs. session
3. Check session timeout settings
4. Clear browser cache and cookies

### Issue 4: Notifications Not Real-time
**Symptoms:** Notifications appear with delay or not at all
**Solutions:**
1. Check WebSocket connection status in browser DevTools
2. Verify triggers are executing: Check `customer_notifications` table after order updates
3. Monitor WebSocket server console for errors
4. Test with database updates directly

## Performance Optimization

### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_notification_customer_read ON customer_notifications (customer_id, is_read, created_at);
CREATE INDEX idx_websocket_active_connections ON customer_websocket_connections (is_active, last_ping);

-- Enable query caching
SET GLOBAL query_cache_size = 1048576;
SET GLOBAL query_cache_type = ON;
```

### WebSocket Server Optimization
```php
// In websocket_server.php, add connection limits
private $maxConnectionsPerCustomer = 3;
private $maxTotalConnections = 1000;

// Implement connection pooling for better resource management
```

### Frontend Optimization
```javascript
// Reduce API calls by caching notifications
localStorage.setItem('notifications_cache', JSON.stringify(notifications));

// Implement lazy loading for notification history
if (offset > 0) {
    // Only load more when needed
}
```

## Scaling for Production

### Load Balancing
For high-traffic scenarios:
1. Use Redis for shared session storage
2. Implement horizontal scaling with multiple WebSocket servers
3. Use a load balancer (nginx) for WebSocket connections

### Message Queue Integration
For enterprise-level reliability:
```php
// Replace file-based queue with Redis/RabbitMQ
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class NotificationQueue {
    private $connection;
    private $channel;
    
    public function __construct() {
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('notifications', false, false, false, false);
    }
    
    public function publish($notification) {
        $msg = new AMQPMessage(json_encode($notification));
        $this->channel->basic_publish($msg, '', 'notifications');
    }
}
```

## API Documentation

### GET /api/get_notifications.php
**Parameters:**
- `limit` (int, default: 20): Number of notifications to retrieve
- `offset` (int, default: 0): Pagination offset
- `unread_only` (bool, default: false): Return only unread notifications
- `count` (bool, default: false): Return only unread count

**Response:**
```json
{
    "success": true,
    "data": {
        "notifications": [...],
        "unread_count": 5,
        "limit": 20,
        "offset": 0
    },
    "timestamp": "2024-01-15 10:30:00"
}
```

### POST /api/get_notifications.php
**Actions:**
- `mark_read`: Mark single notification as read
- `mark_all_read`: Mark all notifications as read

**Request:**
```json
{
    "action": "mark_read",
    "notification_id": 123
}
```

### GET /api/notification_preferences.php
**Response:**
```json
{
    "success": true,
    "data": {
        "order_status_notifications": true,
        "payment_notifications": true,
        "promotional_notifications": false,
        "email_notifications": true,
        "push_notifications": true,
        "sound_enabled": true
    }
}
```

### PUT /api/notification_preferences.php
**Request:**
```json
{
    "order_status_notifications": true,
    "payment_notifications": true,
    "promotional_notifications": false,
    "email_notifications": true,
    "push_notifications": true,
    "sound_enabled": true
}
```

## Testing Checklist

- [ ] Database schema created successfully
- [ ] WebSocket server starts without errors
- [ ] Customer can login and access dashboard
- [ ] Notification bell appears in navbar
- [ ] WebSocket connection establishes automatically
- [ ] Order status changes trigger notifications
- [ ] Real-time notifications appear immediately
- [ ] Notification dropdown shows recent notifications
- [ ] Mark as read functionality works
- [ ] Browser notifications appear (if permission granted)
- [ ] Sound notifications play (if enabled)
- [ ] Toast notifications display correctly
- [ ] Notification preferences can be updated
- [ ] System handles WebSocket disconnections gracefully
- [ ] Old notifications are cleaned up automatically

## Production Deployment Notes

1. **SSL/HTTPS:** Use secure WebSocket (WSS) in production
2. **Process Management:** Use supervisord or systemd for WebSocket server
3. **Monitoring:** Implement health checks and alerting
4. **Backup:** Include notification tables in backup procedures
5. **Updates:** Plan for zero-downtime deployments

## Support and Maintenance

### Regular Maintenance Tasks
- Monitor WebSocket server performance
- Review notification delivery rates
- Clean up old notification data
- Update security certificates
- Monitor database performance

### Backup Procedures
```sql
-- Backup notification data
mysqldump -u username -p database_name customer_notifications customer_notification_preferences > notifications_backup.sql

-- Restore if needed
mysql -u username -p database_name < notifications_backup.sql
```

This real-time notification system provides a comprehensive solution for keeping customers informed about their order status changes instantly. The system is secure, scalable, and provides an excellent user experience with immediate feedback and customizable notification preferences.

## Next Steps
Once this system is deployed and tested:
1. Consider adding email notifications as a backup
2. Implement push notifications for mobile apps
3. Add notification analytics and reporting
4. Integrate with SMS services for critical updates
5. Develop admin interface for managing bulk notifications