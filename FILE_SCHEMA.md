# Esang Delicacies - File Schema Documentation

## Project Structure Overview

```
esang_delicacies/
├── 📁 ROOT FILES (Configuration & Entry Points)
├── 📁 api/ (API Endpoints)
├── 📁 app/ (MVC Application Structure)
├── 📁 assets/ (Frontend Resources)
├── 📁 config/ (Configuration Files)
├── 📁 vendor/ (Composer Dependencies)
└── 📁 Documentation & Development Files
```

---

## 📁 ROOT LEVEL FILES

### Core Configuration
```
db_connection.php          # Main database connection (TrueHost ready)
.env                      # Environment variables
.htaccess                 # Apache URL rewriting rules
composer.json            # PHP dependency manager
composer.lock            # Locked dependency versions
```

### Authentication & User Management
```
customer_login.php       # Customer login page
customer_logout.php      # Customer logout handler
customer_dashboard.php   # Customer main dashboard
customer_profile.php     # Customer profile management
customer_billing.php     # Customer billing interface
```

### Admin & Management
```
admin_feedback_dashboard.php    # Admin feedback management
admin_order_management.php      # Admin order oversight
```

### Order Management System
```
add_delivery_orders.php         # Add new delivery orders
add_returned_orders.php         # Process returned orders
return_management.php           # Return order management
return_orders.php              # Return order interface
```

### Rider System
```
rider_dashboard.php            # Rider main interface
rider_login.php               # Rider authentication
rider_logout.php              # Rider logout handler
rider_main_dashboard.php       # Enhanced rider dashboard
```

### Payment & Billing
```
payment_history.php           # Payment transaction history
enhanced_invoices.php         # Advanced invoice system
```

---

## 📁 API DIRECTORY STRUCTURE

### Core API Configuration
```
api/
├── _api_config.php              # API configuration & CORS (TrueHost ready)
```

### Authentication APIs
```
├── user_login.php               # User authentication endpoint
├── customer_register.php        # Customer registration
├── customer_registration.php    # Alternative registration endpoint
├── rider_auth.php              # Rider authentication
```

### Order Management APIs
```
├── admin_orders.php            # Admin order management API
├── rider_orders.php            # Rider order operations
```

### Communication APIs
```
├── get_notifications.php       # Retrieve notifications
├── rider_notifications.php     # Rider-specific notifications
├── get_feedback.php            # Feedback retrieval
├── save_feedback.php           # Feedback submission
```

### Payment APIs
```
├── upload_payment_screenshot.php    # Payment proof upload
├── get_payment_screenshot.php       # Payment proof retrieval
├── view_payment_screenshot.php      # Payment proof viewer
├── verify_payment.php              # Payment verification
```

### User Management APIs
```
├── user_management.php             # User CRUD operations
├── rider_profile.php              # Rider profile management
├── notification_preferences.php    # User notification settings
```

---

## 📁 APP DIRECTORY (MVC STRUCTURE)

### Core Application Structure
```
app/
├── 📁 api/                     # Internal API handlers
├── 📁 auth/                    # Authentication system
├── 📁 classes/                 # PHP classes
├── 📁 config/                  # App configuration
├── 📁 controllers/             # MVC Controllers (empty - future expansion)
├── 📁 models/                  # MVC Models (empty - future expansion)
└── 📁 views/                   # MVC Views
```

### Authentication System
```
app/auth/
├── HashBasedAuth.php           # Hash-based authentication
```

### Class Libraries
```
app/classes/
├── ProfileHashManager.php      # Profile hash management (active)
└── ProfileHashManager_OLD.php  # Legacy version (backup)
```

### Database Configuration
```
app/config/
├── database.php               # Database connection configuration
```

### Customer API
```
app/api/customer/
├── BaseAPI.php                # Base API class
├── logout.php                 # Customer logout API
└── stats.php                  # Customer statistics API
```

---

## 📁 VIEW SYSTEM (MVC Views)

### Admin Views
```
app/views/admin/
├── AdminMetrics.php           # Admin metrics dashboard
├── AdminPopUp.php             # Admin popup components
├── admin_dashboard.php        # Main admin dashboard
├── admin_feedback_dashboard.php  # Feedback management
├── admin_order_management.php    # Order management interface
├── admin_order_status.php        # Order status management
├── admin_performance.php         # Performance analytics
├── admin_profile.php             # Admin profile management
├── create_discount.php           # Discount creation
├── enhanced_invoices.php         # Enhanced invoice system
├── manage_items.php              # Product management
├── product_maintenance.php       # Product maintenance
└── user_maintenance.php          # User management
```

### Customer Views
```
app/views/customer/
├── customer_billing.php          # Customer billing interface
├── customer_dashboard.php        # Customer main dashboard
├── customer_login.php            # Customer login form
├── customer_logout.php           # Customer logout handler
├── customer_order_status.php     # Order status tracking
├── customer_profile.php          # Profile management
├── enhanced_customer_billing.php # Enhanced billing system
├── feedback.php                  # Feedback submission
├── order_history.php             # Order history view
├── orders.php                    # Order placement
├── OrdersAction.php              # Order action handlers
├── payment_history.php           # Payment history
├── StatusCheck.php               # Status checking utility
└── 📁 profile/                   # Profile management subsystem
    ├── dashboard.php             # Profile dashboard
    ├── index.php                 # Profile index
    └── 📁 handlers/
        └── dashboard.php         # Dashboard handlers
```

### Rider Views
```
app/views/rider/
├── order_assignments.php        # Order assignment interface
├── order_status.php             # Order status management
├── rider_dashboard.php          # Main rider dashboard
├── rider_login.php              # Rider login interface
├── rider_logout.php             # Rider logout handler
├── rider_main_dashboard.php     # Enhanced rider dashboard
└── rider_profile.php            # Rider profile management
```

### Order Manager Views
```
app/views/order_manager/
├── add_delivery_orders.php      # Add delivery orders
├── add_returned_orders.php      # Process returns
├── inventory.php                # Inventory management
├── order_management.php         # Order management
├── order_manager_login_example.php  # Login example
├── order_manager_profile.php    # Profile management
├── order_manager_status.php     # Status management
├── return_management.php        # Return processing
├── return_order.php             # Return order interface
└── return_orders.php            # Return orders listing
```

### Cashier Views
```
app/views/cashier/
├── CashierPopUp.php             # Cashier popup components
├── cashier_invoices.php         # Invoice management
├── cashier_profile.php          # Cashier profile
└── cashier_walk_in.php          # Walk-in customer handling
```

### Authentication Views
```
app/views/auth/
├── 2FA.php                      # Two-factor authentication
├── database_config.php          # Database configuration
├── LogIn.php                    # Login interface
├── logout.php                   # Logout handler
├── OTP.php                      # One-time password
├── register.php                 # Registration form
├── session.php                  # Session management
├── SignUp.php                   # Sign up interface
└── 📁 PHPMailer/                # Email system
    ├── Exception.php            # PHPMailer exceptions
    ├── PHPMailer.php            # Main PHPMailer class
    └── SMTP.php                 # SMTP configuration
```

---

## 📁 FRONTEND ASSETS

### CSS Stylesheets
```
app/views/VCSS/
├── OTP.css                      # OTP styling
├── SignUp.css                   # Sign up styling
├── 📁 Admin_CSS/                # Admin interface styles
├── 📁 Cashier_CSS/              # Cashier interface styles
├── 📁 Common_CSS/               # Shared styles
├── 📁 Customer_CSS/             # Customer interface styles
├── 📁 Order_Manager_CSS/        # Order manager styles
└── 📁 Rider_CSS/                # Rider interface styles
```

### JavaScript Files
```
app/views/VJavaScript/
├── admin_dashboard.js           # Admin dashboard functionality
├── customer_billing.js         # Customer billing logic
├── customer_dashboard.js       # Customer dashboard logic
├── order_sync_service.js       # Real-time order synchronization
├── enhanced_customer_billing.js # Enhanced billing features
└── [multiple other JS files]   # Component-specific scripts
```

### Images & Media
```
app/views/VImages/
├── favicon.jpg                  # Website favicon
├── Food Poster.png              # Main food poster
└── 📁 Full Menu/                # Complete menu images
    ├── 📁 Bilaos/               # Bilao combinations
    ├── 📁 Delicacies/           # Traditional delicacies
    ├── 📁 Desserts/             # Dessert options
    └── 📁 Drinks/               # Beverage options
```

---

## 📁 CONFIGURATION SYSTEM

### Environment Configuration
```
config/
├── environment.php              # Environment detection (dev/prod)
└── [other config files]        # Additional configurations
```

### Web Server Configuration
```
.htaccess                       # Apache rewrite rules
```

---

## 📁 DEPENDENCIES

### Composer Dependencies
```
vendor/                         # Composer-managed dependencies
├── autoload.php               # Composer autoloader
└── [vendor packages]          # Third-party libraries
```

---

## 📁 DOCUMENTATION FILES

### Deployment Documentation
```
DEPLOYMENT_CHECKLIST.md         # Pre-deployment checklist
DEPLOYMENT_GUIDE.md             # Step-by-step deployment
DEPLOYMENT_READY.md             # Deployment readiness confirmation
TRUEHOST_DEPLOYMENT.md          # TrueHost-specific instructions
ENHANCED_BILLING_DEPLOYMENT_GUIDE.md  # Billing system deployment
```

### Feature Documentation
```
API_INTEGRATION_SUMMARY.md      # API integration overview
COMPLETE_CUSTOMER_ACCOUNT_SYSTEM.md  # Customer account system
Customer_Dashboard_Refresh_Enhancement.md  # Dashboard improvements
ORDER_STATUS_IMPLEMENTATION.md  # Order status system
ORDER_SYNCHRONIZATION_SETUP.md  # Real-time sync setup
PAYMENT_SCREENSHOT_SETUP.md     # Payment proof system
REAL_TIME_NOTIFICATIONS_SETUP_GUIDE.md  # Notification system
TWO_STEP_CHECKOUT_FLOW.md       # Checkout process
```

### Technical Documentation
```
ORDER_MANAGER_FIXES.md          # Order manager bug fixes
PATH_ALIGNMENT_REPORT.md        # Path structure alignment
PATH_CORRECTIONS_SUMMARY.md     # Path correction summary
PROFILE_HASH_IMPLEMENTATION_GUIDE.md  # Profile hashing system
```

---

## 📁 DEVELOPMENT & TESTING FILES

### Setup Scripts
```
setup_database.php              # Database initialization
setup_new_system.php            # New system setup
setup_profile_hash.php          # Profile hash setup
update_database.php             # Database updates
```

### Testing Scripts
```
test_*.php                      # Various test files
debug_*.php                     # Debug utilities
verify_deployment.php           # Deployment verification
```

### WebSocket System
```
websocket-server.php            # WebSocket server
simple-websocket-server.php     # Simplified WebSocket
websocket_server.php            # Alternative WebSocket implementation
```

---

## 🔧 SYSTEM INTEGRATIONS

### Email System
- **PHPMailer** integration for OTP and notifications
- **2FA** system with email verification

### Real-time Features
- **WebSocket** servers for live notifications
- **Order synchronization** service
- **Real-time status** updates

### Payment System
- **Payment screenshot** upload and verification
- **Enhanced billing** with detailed invoices
- **Payment history** tracking

### Authentication
- **Hash-based authentication** system
- **Profile hash** management
- **Session management** across user types

---

## 🚀 DEPLOYMENT ARCHITECTURE

### TrueHost Ready Configuration
- **localhost** database configuration
- **mysqli** driver usage  
- **Environment detection** (production/development)
- **CORS configuration** for API endpoints
- **Clean file structure** for shared hosting

### User Roles Supported
1. **Customer** - Order placement, billing, profile management
2. **Admin** - System oversight, analytics, user management
3. **Rider** - Order delivery, status updates
4. **Order Manager** - Inventory, returns, order processing
5. **Cashier** - Invoice management, walk-in customers

---

*This schema represents a complete food delivery and management system with multi-user support, real-time features, and production-ready deployment configuration.*