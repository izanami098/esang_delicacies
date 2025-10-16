# Unique Profile Hash System Implementation Guide

## Overview

This system implements unique hash-based profile identification similar to Facebook's approach, ensuring complete data isolation and user privacy. Each customer gets a unique, non-sequential hash that cannot be replicated or guessed.

## 🔐 **Key Features Implemented**

### 1. Unique Profile Hash Generation
- **32-character SHA256-based hashes** for each customer
- **Multiple entropy sources** (timestamp, customer ID, random bytes, IP, etc.)
- **Collision-resistant** with automatic retry mechanism
- **Non-sequential** and unpredictable identifiers

### 2. Secure Session Management
- **Hash-based authentication** instead of exposing customer IDs
- **Session tracking** with expiration and security logging
- **Profile isolation** ensuring users only access their own data
- **Automatic session cleanup** for expired/inactive sessions

### 3. Complete Data Isolation
- **No customer ID exposure** in APIs or frontend
- **Profile-hash-only access** to orders and profile data
- **Secure data access** through ProfileHashManager
- **Audit logging** for all profile access activities

### 4. Privacy & Security
- **Profile access logging** for security monitoring
- **IP and user agent tracking** for suspicious activity detection
- **Automatic password hashing** for enhanced security
- **Session validation** with hash verification

## 📁 **Files Created/Modified**

### Database Schema
- `storage/profile_hash_system.sql` - Database tables and hash generation
- Added `profile_hash` column to customer table
- Created `user_sessions` table for secure session tracking
- Created `profile_access_log` table for security auditing

### Core Classes
- `app/classes/ProfileHashManager.php` - Main profile hash management
- `app/auth/HashBasedAuth.php` - Secure authentication system

### API Updates
- `public/api/secure_customer_order_status.php` - Hash-based order API
- Updated `public/api/order_status_sync.php` - Synchronization with hash support

## 🚀 **Installation Steps**

### Step 1: Database Setup

Run the SQL script to add profile hash system:

```bash
# Access your MySQL database (phpMyAdmin, command line, etc.)
# Execute the SQL file:
```

```sql
SOURCE C:/xampp/htdocs/esang_delicacies/storage/profile_hash_system.sql;
```

Or manually execute the SQL commands from the file.

### Step 2: Verify Hash Generation

Check that profile hashes were generated successfully:

```sql
-- Check hash generation
SELECT 
    COUNT(*) as total_customers,
    COUNT(CASE WHEN profile_hash IS NOT NULL AND profile_hash != '' THEN 1 END) as customers_with_hash,
    COUNT(DISTINCT profile_hash) as unique_hashes
FROM customer;

-- View sample hashes
SELECT customerId, name, profile_hash 
FROM customer 
WHERE profile_hash IS NOT NULL AND profile_hash != '' 
LIMIT 5;
```

Expected results:
- `total_customers` = `customers_with_hash` (all customers have hashes)
- `unique_hashes` = `customers_with_hash` (all hashes are unique)

### Step 3: Update Login System

Replace your existing login logic with the new hash-based authentication:

```php
// Example login implementation
require_once __DIR__ . '/app/auth/HashBasedAuth.php';

$auth = new HashBasedAuth();
$result = $auth->login($username, $password, 'CUSTOMER');

if ($result['success']) {
    // Login successful - user now has profile hash in session
    header('Location: ' . $result['redirect_url']);
} else {
    // Login failed
    echo $result['message'];
}
```

### Step 4: Update Customer APIs

Replace direct customer ID access with hash-based access:

**Old API call:**
```javascript
fetch('/esang_delicacies/public/api/get_customer_order_status.php')
```

**New API call:**
```javascript
fetch('/esang_delicacies/public/api/secure_customer_order_status.php')
```

### Step 5: Update Frontend JavaScript

Update customer order status JavaScript to use the secure API:

```javascript
// In customer_order_status.js, change the API endpoint
const response = await fetch('/esang_delicacies/public/api/secure_customer_order_status.php');
```

## 🔧 **How The System Works**

### 1. User Registration Flow
```
New Customer Registration
        ↓
Insert Customer Record
        ↓
Generate Unique Profile Hash
        ↓
Assign Hash to Customer
        ↓
Create Secure Session
        ↓
Customer Accesses System with Hash
```

### 2. Login Flow
```
Customer Login (email/username + password)
        ↓
Validate Credentials
        ↓
Retrieve/Generate Profile Hash
        ↓
Create Secure Session (with hash)
        ↓
Set Session Variables (profile_hash, session_id)
        ↓
Customer Uses Hash-Based APIs
```

### 3. Data Access Flow
```
API Request with Session
        ↓
Validate Profile Hash
        ↓
Get Customer Data via Hash
        ↓
Verify Access Permissions
        ↓
Return Data (NO customer ID exposed)
        ↓
Log Access Activity
```

## 🛡️ **Security Features**

### Profile Hash Properties
- **Length**: 32 characters (shortened from 64 for usability)
- **Character Set**: Hexadecimal (0-9, a-f)
- **Entropy Sources**: Customer ID, timestamp, random bytes, IP, salt
- **Collision Resistance**: Automatic retry if collision detected
- **Example**: `a1b2c3d4e5f6789012345678901abcdef`

### Session Security
- **Session ID**: 64-character SHA256 hash
- **Expiration**: 24 hours (configurable)
- **Activity Tracking**: Last activity timestamp
- **IP Validation**: IP address logging
- **User Agent**: Browser fingerprinting

### Access Logging
All profile access is logged with:
- Profile hash (for customer identification)
- Access type (LOGIN, VIEW_ORDERS, UPDATE_PROFILE, etc.)
- IP address and user agent
- Timestamp
- Additional metadata (JSON)

## 📊 **Database Schema Details**

### Customer Table Updates
```sql
ALTER TABLE customer 
ADD COLUMN profile_hash VARCHAR(64) UNIQUE NOT NULL DEFAULT '';
```

### New Tables

**user_sessions**
```sql
CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    profile_hash VARCHAR(64) NOT NULL,
    customer_id INT NOT NULL,
    user_role ENUM('CUSTOMER', 'ADMIN', 'ORDER_MANAGER', 'RIDER'),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    ip_address VARCHAR(45),
    user_agent TEXT
);
```

**profile_access_log**
```sql
CREATE TABLE profile_access_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profile_hash VARCHAR(64) NOT NULL,
    customer_id INT NOT NULL,
    access_type ENUM('LOGIN', 'LOGOUT', 'VIEW_ORDERS', 'UPDATE_PROFILE', 'PLACE_ORDER'),
    ip_address VARCHAR(45),
    user_agent TEXT,
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    additional_data JSON
);
```

## 💻 **Usage Examples**

### Get Customer Profile (Secure)
```php
$profileManager = ProfileHashManager::getInstance();
$customer = $profileManager->getCustomerByHash($_SESSION['profile_hash']);
```

### Get Customer Orders (Secure)
```php
$orders = $profileManager->getCustomerOrders($_SESSION['profile_hash'], 10, 0);
```

### Create Secure Session
```php
$auth = new HashBasedAuth();
$sessionData = $profileManager->createUserSession($profileHash, 'CUSTOMER');
```

### Validate Session
```php
$isValid = $auth->validateSession('CUSTOMER');
```

## 🌟 **Benefits of This System**

### 1. **Privacy Protection**
- Customer IDs are never exposed in URLs, APIs, or frontend
- Profile hashes are meaningless to outsiders
- Complete isolation between user profiles

### 2. **Security Enhancement**
- No sequential ID enumeration attacks possible
- Session hijacking protection through hash validation
- Comprehensive access logging for security monitoring

### 3. **Scalability**
- Hash-based access is efficient and scalable
- Database queries optimized with proper indexing
- Clean separation of concerns

### 4. **User Experience**
- Transparent to end users (URLs still work)
- Fast profile access and data retrieval
- Consistent across all customer interactions

## 🔄 **Migration from Old System**

### Existing Customers
- Automatic hash generation for all existing customers
- No data loss or corruption
- Seamless transition with backward compatibility

### API Compatibility
- Old APIs can still work during transition period
- New secure APIs provide enhanced protection
- Gradual migration approach supported

## 🧪 **Testing the System**

### 1. Test Hash Generation
```sql
-- Should show all customers have unique hashes
SELECT COUNT(*), COUNT(DISTINCT profile_hash) FROM customer;
```

### 2. Test Secure Login
```php
// Test with existing customer credentials
$auth = new HashBasedAuth();
$result = $auth->login('customer@email.com', 'password', 'CUSTOMER');
print_r($result);
```

### 3. Test Profile Access
```javascript
// In browser console after login
fetch('/esang_delicacies/public/api/secure_customer_order_status.php')
    .then(response => response.json())
    .then(data => console.log(data));
```

### 4. Test Cross-Customer Isolation
- Login as Customer A
- Try to access Customer B's data (should fail)
- Verify access logs show proper isolation

## 🚨 **Important Notes**

### Security Considerations
1. **Never expose customer IDs** in APIs or frontend after migration
2. **Always validate profile hashes** before data access
3. **Monitor access logs** for suspicious activity
4. **Clean up expired sessions** regularly

### Performance Considerations
1. **Profile hash lookups are indexed** for fast access
2. **Session validation is cached** in PHP session
3. **Database connections are optimized** for hash queries
4. **Access logging is asynchronous** to avoid delays

### Maintenance
1. **Regular session cleanup** (automated in system)
2. **Monitor hash uniqueness** (automated checks)
3. **Review access logs** for security auditing
4. **Update entropy sources** periodically for security

This implementation provides Facebook-level user isolation and privacy protection while maintaining excellent performance and security standards.