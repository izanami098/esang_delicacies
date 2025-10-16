# User Management API Documentation

This document describes the available API endpoints for user creation and management in the Esang Delicacies system.

## Base URL

All endpoints are available under:
- Production: `https://esangdelicacies.com/esang_delicacies/api/`
- Development: `http://localhost/esang_delicacies/api/`

## Available Endpoints

### 1. Public Customer Registration

**Endpoint:** `customer_registration.php`
**Method:** `POST`
**Access:** Public (no authentication required)

**Description:** Allows anyone to register as a new customer.

**Request Body:**
```json
{
    "name": "Juan Dela Cruz",
    "email": "juan@example.com",
    "phone": "09123456789",
    "password": "securepass123",
    "first_name": "Juan",        // Optional
    "last_name": "Dela Cruz",    // Optional
    "address": "123 Main St"     // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful! You can now login with your email and password.",
    "customer_id": 123
}
```

**Validation Rules:**
- Name, email, phone, password are required
- Email must be valid format
- Phone must be Philippine format (09XXXXXXXXX or +639XXXXXXXXX)
- Password must be at least 8 characters with at least one letter and one number
- Email and phone must be unique

### 2. Check Email/Phone Availability

**Endpoint:** `customer_registration.php`
**Method:** `GET`
**Access:** Public

**Description:** Check if email or phone number is already registered.

**Query Parameters:**
- `email` (optional): Email to check
- `phone` (optional): Phone number to check

**Example:** `customer_registration.php?email=test@example.com&phone=09123456789`

**Response:**
```json
{
    "success": true,
    "availability": {
        "email_available": true,
        "phone_available": false
    }
}
```

### 3. User Management (Admin Functions)

**Endpoint:** `user_management.php`
**Access:** Requires authentication (admin privileges for staff creation)

#### 3.1 Universal Login

**Method:** `POST`
**Action:** `?action=login`

**Request Body:**
```json
{
    "email": "admin@example.com",
    "password": "password123",
    "user_type": "ADMIN"  // CUSTOMER, ADMIN, CASHIER, ORDER_MANAGER, RIDER
}
```

#### 3.2 Create Admin User

**Method:** `POST`
**Action:** `?action=create_admin`
**Auth Required:** Admin

**Request Body:**
```json
{
    "name": "Admin Name",
    "password": "adminpass123",
    "email": "admin@example.com",  // Optional
    "phone": "09123456789"         // Optional
}
```

#### 3.3 Create Cashier

**Method:** `POST`
**Action:** `?action=create_cashier`
**Auth Required:** Admin

**Request Body:**
```json
{
    "name": "Cashier Name",
    "password": "cashierpass123",
    "email": "cashier@example.com",  // Optional
    "phone": "09123456789"           // Optional
}
```

#### 3.4 Create Order Manager

**Method:** `POST`
**Action:** `?action=create_order_manager`
**Auth Required:** Admin

**Request Body:**
```json
{
    "name": "Manager Name",
    "password": "managerpass123",
    "email": "manager@example.com",  // Optional
    "phone": "09123456789"           // Optional
}
```

#### 3.5 Create Rider

**Method:** `POST`
**Action:** `?action=create_rider`
**Auth Required:** Admin

**Request Body:**
```json
{
    "name": "Rider Name",
    "password": "riderpass123",
    "email": "rider@example.com",    // Optional
    "phone": "09123456789",          // Optional
    "plateNum": "ABC-1234"           // Optional, defaults to "N/A"
}
```

#### 3.6 List Users

**Method:** `GET`
**Action:** `?action=list_users&type=all`
**Auth Required:** Admin

**Query Parameters:**
- `type`: `all`, `customer`, `admin`, `cashier`, `order_manager`, `rider`

**Response:**
```json
{
    "success": true,
    "users": [
        {
            "id": 1,
            "name": "User Name",
            "email": "user@example.com",
            "phone": "09123456789",
            "created_at": "2025-01-01 12:00:00",
            "status": "active",
            "type": "customer"
        }
    ],
    "total": 1
}
```

#### 3.7 Check Session

**Method:** `GET`
**Action:** `?action=check_session`

**Response:**
```json
{
    "success": true,
    "authenticated": true,
    "user": {
        "role": "ADMIN",
        "name": "Admin User",
        "email": "admin@example.com",
        "profile": { /* profile data */ }
    }
}
```

#### 3.8 Change Password

**Method:** `PUT`
**Action:** `?action=change_password`
**Auth Required:** Customer

**Request Body:**
```json
{
    "old_password": "oldpass123",
    "new_password": "newpass123"
}
```

#### 3.9 Deactivate User

**Method:** `DELETE`
**Action:** `?action=deactivate_user`
**Auth Required:** Admin

**Request Body:**
```json
{
    "user_id": 123,
    "user_type": "customer"
}
```

#### 3.10 Logout

**Method:** `POST`
**Action:** `?action=logout`

## Usage Examples

### JavaScript/AJAX Examples

#### Register a New Customer
```javascript
fetch('/esang_delicacies/api/customer_registration.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'Juan Dela Cruz',
        email: 'juan@example.com',
        phone: '09123456789',
        password: 'securepass123',
        address: '123 Main Street, Manila'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Registration successful!');
        // Redirect to login page
        window.location.href = '/login';
    } else {
        alert('Error: ' + data.message);
    }
});
```

#### Admin Login
```javascript
fetch('/esang_delicacies/api/user_management.php?action=login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'admin@esangdelicacies.com',
        password: 'adminpassword',
        user_type: 'ADMIN'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Redirect to admin dashboard
        window.location.href = data.redirect_url;
    } else {
        alert('Login failed: ' + data.message);
    }
});
```

#### Create New Staff Member (Admin only)
```javascript
fetch('/esang_delicacies/api/user_management.php?action=create_cashier', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: 'New Cashier',
        password: 'cashierpass123',
        email: 'cashier@esangdelicacies.com',
        phone: '09987654321'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Cashier created successfully!');
        // Refresh user list or redirect
    } else {
        alert('Error: ' + data.message);
    }
});
```

### cURL Examples

#### Register Customer
```bash
curl -X POST "http://localhost/esang_delicacies/api/customer_registration.php" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Maria Santos",
    "email": "maria@example.com",
    "phone": "09123456789",
    "password": "mariapass123"
  }'
```

#### Admin Create Rider
```bash
curl -X POST "http://localhost/esang_delicacies/api/user_management.php?action=create_rider" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Rider Name",
    "password": "riderpass123",
    "email": "rider@example.com",
    "phone": "09123456789",
    "plateNum": "XYZ-9876"
  }'
```

## Error Codes

- `200`: Success
- `201`: Created successfully
- `400`: Bad request (validation error)
- `401`: Unauthorized (invalid credentials)
- `403`: Forbidden (insufficient privileges)
- `405`: Method not allowed
- `409`: Conflict (email/phone already exists)
- `500`: Internal server error

## Security Features

1. **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
2. **Input Validation**: Comprehensive validation for all input fields
3. **Session Management**: Secure hash-based sessions for customers
4. **Access Control**: Role-based access for admin functions
5. **Duplicate Prevention**: Email and phone uniqueness checks
6. **Activity Logging**: User registration and activities are logged

## Integration Notes

1. All endpoints return JSON responses
2. CORS headers are set for cross-origin requests
3. Session data is maintained server-side
4. Profile hashes are used for customer session management
5. Error messages are user-friendly and secure