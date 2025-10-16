# Path Corrections Summary for https://esangdelicacies.com/esang_delicacies/

## Overview
This document outlines all the directory path issues found in your project and the corrections needed for proper deployment on your live domain.

## Database Configuration Issues

### 1. Host Configuration
**Current Issue:** Using localhost/127.0.0.1 in multiple places
**Files Affected:**
- `includes/config.php` (line 8): `define('DB_HOST', '127.0.0.1');`
- `app/config/database.php` (line 16): `$host = getenv('ESANG_DB_HOST') ?: '127.0.0.1';`
- Multiple files with direct `mysqli("localhost", ...)`

**Solution:** Update to use your production database host
```php
// In includes/config.php
define('DB_HOST', 'your_production_host');

// Or better, use environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
```

## URL Path Issues

### 2. Hardcoded `/esang_delicacies/` Paths
**Critical Issue:** Over 100+ instances of hardcoded `/esang_delicacies/` paths

**Major Files Affected:**
- `api/get_payment_screenshot.php` (line 77)
- `app/auth/HashBasedAuth.php` (lines 272, 297, 299, 301, 303, 305)
- `app/views/customer/customer_billing.php` (lines 23, 30)
- `app/views/customer/profile/handlers/dashboard.php` (line 216)
- `app/views/customer/orders.php` (multiple lines)
- `public/api/*.php` (multiple files)

**Solution:** Create a configuration constant
```php
// Add to includes/config.php
define('BASE_PATH', '/esang_delicacies');
define('BASE_URL', 'https://esangdelicacies.com/esang_delicacies');

// Then replace all hardcoded paths like:
// OLD: '/esang_delicacies/api/...'
// NEW: BASE_PATH . '/api/...'
```

### 3. WebSocket Configuration
**Issue:** Hardcoded localhost URLs for WebSocket connections
**Files Affected:**
- `customer_dashboard.php` (line 354): `wsUrl: 'ws://localhost:8080'`
- `assets/js/notification-system.js` (line 8): `'ws://localhost:8080'`
- `app/views/customer/profile/handlers/dashboard.php` (line 291)

**Solution:**
```javascript
// Use dynamic WebSocket URL based on environment
const wsUrl = window.location.protocol === 'https:' ? 
    'wss://esangdelicacies.com:8080' : 
    'ws://esangdelicacies.com:8080';
```

### 4. Upload Directory Paths
**Issue:** Document root paths may not work on live server
**Files Affected:**
- `app/views/customer/customer_billing.php` (line 23)
- `api/upload_payment_screenshot.php` (line 13)

**Current:**
```php
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/esang_delicacies/public/uploads/receipts/';
```

**Solution:**
```php
$uploadDir = dirname(__DIR__) . '/public/uploads/receipts/';
// Or use BASE_PATH constant
```

### 5. Image and Asset Paths
**Issue:** Hardcoded paths for images and assets
**Files Affected:**
- `public/api/get_customer_orders.php` (line 106)
- `public/api/get_customer_profile.php` (line 64)
- `public/api/upload_image.php` (line 61)

**Solution:** Use relative paths or BASE_PATH constant

## API Configuration Issues

### 6. API Base URLs
**Issue:** Using localhost URLs in API calls
**Files Affected:**
- `app/views/customer/orders.php` (lines 2413, 2981, 3007, etc.)
- `test_order_status_api.php` (lines 54, 94)

**Current:**
```javascript
const response = await fetch('http://localhost:8080/esang_delicacies/public/api/...');
```

**Solution:**
```javascript
const API_BASE = 'https://esangdelicacies.com/esang_delicacies';
const response = await fetch(`${API_BASE}/public/api/...`);
```

## File Include Issues

### 7. Bootstrap and Include Paths
**Issue:** Some files use absolute paths that may not work on live server
**Files Affected:**
- `public/api/get_order_status.php` (line 3)
- `public/api/profile.php` (line 3)
- `public/api/verify_otp.php` (line 3)

**Current:**
```php
require_once $_SERVER['DOCUMENT_ROOT'] . '/esang_delicacies/app/views/_bootstrap.php';
```

**Solution:**
```php
require_once dirname(dirname(__DIR__)) . '/app/views/_bootstrap.php';
```

## Environment-Specific Fixes

### 8. Create Environment Configuration
Create a new file: `config/environment.php`
```php
<?php
// Environment configuration
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');
define('IS_PRODUCTION', ENVIRONMENT === 'production');

if (IS_PRODUCTION) {
    define('BASE_URL', 'https://esangdelicacies.com/esang_delicacies');
    define('WS_URL', 'wss://esangdelicacies.com:8080');
    define('DB_HOST', 'your_production_db_host');
} else {
    define('BASE_URL', 'http://localhost/esang_delicacies');
    define('WS_URL', 'ws://localhost:8080');
    define('DB_HOST', '127.0.0.1');
}

define('BASE_PATH', '/esang_delicacies');
?>
```

## Priority Fixes (Immediate Action Required)

### High Priority:
1. **Database connection strings** - Update all localhost references
2. **API endpoint URLs** - Replace localhost with live domain
3. **WebSocket URLs** - Update for production environment
4. **Upload directory paths** - Ensure they work on live server

### Medium Priority:
5. **Image and asset paths** - Make them relative or configurable
6. **Include file paths** - Use relative paths instead of absolute
7. **Redirect URLs** - Update all hardcoded redirects

### Low Priority:
8. **CSS/JS asset references** - Ensure they load correctly
9. **Email configuration** - Update any SMTP settings if needed

## Quick Fix Script
Here's a search-and-replace list for immediate fixes:

```bash
# Replace common hardcoded paths (use with caution)
localhost:8080/esang_delicacies → esangdelicacies.com/esang_delicacies
ws://localhost:8080 → wss://esangdelicacies.com:8080
http://localhost:8080 → https://esangdelicacies.com
127.0.0.1 → your_production_db_host
```

## Testing Checklist
After making corrections, test:
- [ ] Database connections work
- [ ] API endpoints respond correctly
- [ ] File uploads work
- [ ] WebSocket connections establish
- [ ] Image loading works
- [ ] User authentication flows
- [ ] Order processing
- [ ] Payment systems

## Notes
- Your live server structure matches your local structure based on the external context provided
- Most issues are related to hardcoded localhost references
- Consider using environment variables for better deployment flexibility
- The `/esang_delicacies/` subdirectory structure appears correct for your live setup

Created: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")