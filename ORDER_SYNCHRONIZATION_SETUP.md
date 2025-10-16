# Order Status Synchronization System Setup Guide

## Overview

This guide provides instructions for setting up the enhanced order status synchronization system that enables real-time communication between customer, admin, and order manager modules.

## Features Implemented

### 1. Real-Time Synchronization
- **Instant Updates**: Order status changes are synchronized across all modules in real-time
- **Polling System**: Efficient polling mechanism that checks for updates every 10-15 seconds
- **Auto-Retry**: Automatic retry mechanism with exponential backoff for failed connections
- **Connection Status**: Visual indicators for connection status in each module

### 2. Enhanced Notification System
- **Customer Notifications**: Automatic notifications when order status changes
- **Admin/Manager Alerts**: Real-time alerts for new orders and status changes
- **Persistent Storage**: All notifications are stored in the database
- **Notification History**: Complete audit trail of all status changes

### 3. Cross-Module Communication
- **Customer Module**: Real-time progress tracking with visual progress bar updates
- **Admin Module**: Instant order list updates when status changes occur
- **Order Manager Module**: Synchronized order management with immediate status propagation

## Installation Steps

### Step 1: Database Setup

Run the SQL script to create the required database tables:

```bash
# Navigate to your MySQL/phpMyAdmin
# Execute the following SQL file:
```

```sql
-- Run this in your MySQL database
SOURCE /path/to/esang_delicacies/storage/order_sync_tables.sql;
```

Or manually execute these commands:

```sql
-- Table to track order status changes for audit and synchronization
CREATE TABLE IF NOT EXISTS order_status_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status VARCHAR(50) NOT NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by INT DEFAULT NULL,
    changed_by_role VARCHAR(20) DEFAULT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Table to track synchronization timestamps and versions
CREATE TABLE IF NOT EXISTS order_sync_tracker (
    order_id INT PRIMARY KEY,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    sync_version INT DEFAULT 1,
    INDEX idx_last_updated (last_updated),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Table for customer notifications
CREATE TABLE IF NOT EXISTS customer_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(50) DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (customer_id) REFERENCES customer(customerId) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Initialize sync tracker for existing orders
INSERT IGNORE INTO order_sync_tracker (order_id, last_updated, sync_version)
SELECT order_id, updated_at, 1 FROM orders WHERE updated_at IS NOT NULL;
```

### Step 2: File Verification

Ensure all these files are in place:

#### API Files:
- `public/api/order_status_sync.php` - Main synchronization API
- `public/api/notifications.php` - Notification management API

#### JavaScript Files:
- `app/views/VJavaScript/order_sync_service.js` - Core synchronization service

#### Updated Modules:
- `app/views/customer/customer_order_status.php` - Enhanced with sync service
- `app/views/admin/admin_order_status.php` - Enhanced with sync service  
- `app/views/order_manager/order_manager_status.php` - Enhanced with sync service

#### Updated JavaScript Files:
- `app/views/VJavaScript/customer_order_status.js` - Real-time updates
- `app/views/VJavaScript/admin_order_status.js` - Real-time updates
- `app/views/VJavaScript/order_manager_status.js` - Real-time updates

### Step 3: Configuration

#### Polling Intervals
Current polling intervals (can be adjusted as needed):
- **Customer**: 10 seconds (for immediate status updates)
- **Admin/Order Manager**: 15 seconds (for order management)

To adjust intervals, modify the `pollInterval` parameter in each module:
```javascript
pollInterval: 10000, // milliseconds
```

#### Connection Retry Settings
The system includes automatic retry with exponential backoff:
- **Max Retries**: 3 attempts
- **Max Poll Interval**: 60 seconds
- **Retry Backoff**: 2x multiplier

### Step 4: Testing the System

#### 1. Start Your Server
```bash
# Make sure XAMPP/Apache is running
# Access: http://localhost/esang_delicacies/
```

#### 2. Test Multi-Module Synchronization

**Customer Module Test:**
1. Log in as a customer
2. Navigate to Order Status page
3. Open browser console to see sync service logs

**Admin Module Test:**
1. Log in as admin  
2. Navigate to Order Status page
3. Update an order status
4. Verify update appears immediately in customer module

**Order Manager Module Test:**
1. Log in as order manager
2. Navigate to Order Status page
3. Update an order status
4. Verify synchronization across all modules

#### 3. Test Scenarios

**Real-time Sync Test:**
1. Open customer order status page in one browser tab
2. Open admin order status page in another tab
3. Update order status in admin tab
4. Verify customer tab updates immediately (within 10-15 seconds)

**Cross-Browser Test:**
1. Open customer module in Chrome
2. Open admin module in Firefox  
3. Update status in admin
4. Verify customer sees update

**Notification Test:**
1. Update order status as admin/order manager
2. Check customer receives notification
3. Verify notification appears in customer interface

## How the System Works

### 1. Status Update Flow
```
Admin/Order Manager → API Call → Database Update → Notification Creation → Real-time Sync
                                                                             ↓
Customer ← Polling Service ← Status Check ← Notification Check ← Database
```

### 2. Polling Mechanism
- Each module polls the sync API at regular intervals
- Only requests changes since last sync timestamp
- Efficient database queries using indexed timestamps
- Automatic pause/resume based on page visibility

### 3. Error Handling
- Connection failures trigger automatic retries
- Exponential backoff prevents server overload
- Fallback mechanisms for older browsers
- Graceful degradation to periodic refresh

## Troubleshooting

### Common Issues

**1. Sync Service Not Loading**
- Check browser console for JavaScript errors
- Verify `order_sync_service.js` is loading correctly
- Ensure proper script order in HTML files

**2. Database Connection Issues**
- Verify database tables were created successfully
- Check database permissions for sync operations
- Ensure foreign key constraints are properly set

**3. Polling Not Working**
- Check server logs for API endpoint errors
- Verify session authentication is working
- Test API endpoints directly in browser/Postman

**4. Notifications Not Appearing**
- Verify customer_notifications table exists
- Check notification insertion in order_status_sync.php
- Ensure customer ID is properly set in session

### Debug Commands

**Test Sync API:**
```bash
# GET request to test polling
curl -X GET "http://localhost/esang_delicacies/public/api/order_status_sync.php?poll_type=customer&last_sync=0"

# POST request to test status update
curl -X POST "http://localhost/esang_delicacies/public/api/order_status_sync.php" \
  -H "Content-Type: application/json" \
  -d '{"orderId": 1, "action": "next"}'
```

**Check Database Tables:**
```sql
-- Verify tables exist
SHOW TABLES LIKE '%sync%';
SHOW TABLES LIKE '%notification%';

-- Check recent sync activity  
SELECT * FROM order_status_log ORDER BY changed_at DESC LIMIT 10;
SELECT * FROM order_sync_tracker ORDER BY last_updated DESC LIMIT 10;
SELECT * FROM customer_notifications ORDER BY created_at DESC LIMIT 10;
```

## Performance Considerations

### Optimization Tips

1. **Database Indexing**: Ensure proper indexes on frequently queried columns
2. **Polling Frequency**: Adjust based on server load and user needs
3. **Connection Pooling**: Use persistent database connections
4. **Caching**: Implement caching for frequently accessed data

### Monitoring

Monitor these metrics for optimal performance:
- Database query response times
- API endpoint response times  
- Number of active polling connections
- Error rates and retry patterns

## Security Considerations

1. **Session Validation**: All API calls validate user sessions
2. **Role-Based Access**: Different poll types for different user roles
3. **SQL Injection Protection**: All queries use prepared statements
4. **XSS Prevention**: All output is properly escaped
5. **CSRF Protection**: API calls use same-origin requests

## Support and Maintenance

### Regular Maintenance Tasks

1. **Clean Old Notifications**: Periodically remove old read notifications
2. **Monitor Log Tables**: Archive old status change logs
3. **Database Optimization**: Run database maintenance queries
4. **Performance Review**: Monitor polling efficiency

### Extending the System

The synchronization system is designed to be extensible:

1. **Add New Poll Types**: Extend the sync API for new user roles
2. **Custom Notifications**: Add new notification types and templates  
3. **Push Notifications**: Integrate with web push notification APIs
4. **Mobile Support**: Extend for mobile app synchronization

## Conclusion

The order status synchronization system provides real-time communication between all modules of the Esang Delicacies application. It ensures that customers, admins, and order managers always see the most up-to-date order information, improving the overall user experience and operational efficiency.

For additional support or customization needs, refer to the API documentation and module-specific implementation details in the respective JavaScript files.