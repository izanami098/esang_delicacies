# Enhanced Customer Billing System - Deployment Guide

## Overview
This guide walks you through deploying the enhanced customer billing system with the profile hash authentication system for Esang Delicacies.

## Files Created/Updated

### 1. Database Setup
- **`payments_table_setup.sql`** - Creates the payments table and updates orders table

### 2. PHP Files
- **`customer_billing.php`** - Enhanced billing page with tabbed interface
- **`enhanced_invoices.php`** - API endpoint for fetching customer invoices
- **`payment_history.php`** - API endpoint for payment history

### 3. Prerequisites
Make sure you have already implemented the profile hash system with these files:
- `includes/ProfileHashManager.php`
- `includes/HashBasedAuth.php`
- Profile hash columns in customer table
- Customer sessions working with profile hashes

## Deployment Steps

### Step 1: Database Setup
1. **Run the SQL setup script:**
   ```bash
   # In phpMyAdmin or MySQL command line
   ```
   Execute the contents of `payments_table_setup.sql`

2. **Verify the database structure:**
   ```sql
   DESCRIBE payments;
   DESCRIBE orders;
   -- Check that payment_status column exists in orders table
   ```

### Step 2: File Deployment
1. **Copy the files to your web directory:**
   ```
   customer_billing.php         -> root directory
   enhanced_invoices.php        -> root directory  
   payment_history.php          -> root directory
   ```

2. **Create upload directory:**
   ```bash
   mkdir -p public/uploads/payment_proofs/
   chmod 755 public/uploads/payment_proofs/
   ```

### Step 3: Configuration Check
1. **Verify database connection** in `includes/db.php`
2. **Test profile hash system** is working properly
3. **Ensure customer authentication** is functional

### Step 4: Testing the System

#### Test 1: Access the Billing Page
1. **Login as a customer** (using profile hash authentication)
2. **Navigate to** `customer_billing.php`
3. **Verify tabs load:**
   - My Invoices tab
   - Make Payment tab  
   - Payment History tab

#### Test 2: Invoice Loading
1. **Check invoices tab** loads customer orders
2. **Verify "Pay Now" buttons** work
3. **Test invoice selection** for payment

#### Test 3: Payment Submission
1. **Select an invoice** to pay
2. **Choose payment method:**
   - GCash (requires reference number)
   - Bank Transfer (requires reference number)
   - Cash Payment (no additional fields)
3. **Upload payment proof** (optional)
4. **Submit payment** and verify success message

#### Test 4: Payment History
1. **Check payment history tab** loads previous payments
2. **Verify payment details** are displayed correctly

## System Features

### Enhanced User Experience
- **Tabbed interface** prevents UI freezing
- **Real-time validation** with user feedback
- **Loading states** during API calls
- **Error handling** with retry options
- **Responsive design** works on mobile

### Security Features
- **Profile hash authentication** ensures data isolation
- **File upload validation** for payment proofs
- **Secure file naming** using profile hash + timestamp
- **Input sanitization** prevents XSS and SQL injection

### Payment Methods Supported
1. **GCash** - Requires reference number and optional proof upload
2. **Bank Transfer** - Requires reference number and optional proof upload  
3. **Cash Payment** - Simple submission, no additional fields

### API Endpoints

#### `/enhanced_invoices.php`
- **Purpose:** Fetch customer invoices/orders
- **Method:** GET
- **Authentication:** Profile hash session
- **Response:** JSON with invoice list

#### `/payment_history.php`  
- **Purpose:** Fetch customer payment history
- **Method:** GET
- **Authentication:** Profile hash session
- **Response:** JSON with payment records

#### `/customer_billing.php` (POST)
- **Purpose:** Submit payment information
- **Method:** POST
- **Action:** `submit_payment`
- **Files:** Supports payment proof upload

## Database Schema

### Payments Table
```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    proof_file VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Orders Table Update
```sql
ALTER TABLE orders 
ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending';
```

## File Upload Configuration

### Upload Directory Structure
```
public/
└── uploads/
    └── payment_proofs/
        ├── {profile_hash}_{order_id}_{timestamp}.jpg
        ├── {profile_hash}_{order_id}_{timestamp}.png
        └── {profile_hash}_{order_id}_{timestamp}.pdf
```

### File Naming Convention
- **Format:** `{profile_hash}_{order_id}_{timestamp}.{extension}`
- **Purpose:** Unique, secure, and traceable file names
- **Example:** `a1b2c3d4e5f6_101_1697123456.jpg`

## Error Handling

### Graceful Degradation
1. **Missing payments table** - Falls back to orders table data
2. **Missing order_items table** - Shows generic item descriptions
3. **Database connection issues** - Shows mock data for testing
4. **File upload failures** - Payment still processes without proof

### User Feedback
- **Loading spinners** during API calls
- **Success/error modals** for payment submission
- **Retry buttons** when errors occur
- **Form validation** with helpful messages

## Performance Considerations

### Database Optimization
- **Indexed columns** for faster queries
- **Limited result sets** (20 invoices, 50 payment history)
- **Prepared statements** prevent SQL injection

### Frontend Optimization
- **Bootstrap CDN** for fast CSS/JS loading
- **Minimal JavaScript** for better performance
- **Lazy loading** of payment history when tab clicked

## Security Considerations

### Data Protection
- **Profile hash isolation** - Each customer sees only their data
- **Session validation** on every API call
- **File upload restrictions** - Images and PDFs only
- **Secure file storage** outside web root (recommended)

### Input Validation
- **Server-side validation** of all form inputs
- **File type validation** for uploads
- **Amount validation** to prevent negative values
- **Reference number sanitization**

## Troubleshooting

### Common Issues

#### 1. "Authentication required" error
- **Check:** Profile hash system is properly implemented
- **Verify:** Customer is logged in with valid session
- **Solution:** Ensure HashBasedAuth class is working

#### 2. Empty invoices/payment history
- **Check:** Database tables exist and have data
- **Verify:** Customer ID matches in orders table
- **Solution:** Check customerId field naming consistency

#### 3. File upload not working
- **Check:** Upload directory exists and is writable
- **Verify:** PHP file upload settings (upload_max_filesize, post_max_size)
- **Solution:** Create directory with proper permissions

#### 4. Payment submission fails
- **Check:** Payments table exists
- **Verify:** Form validation is passing
- **Solution:** Check browser console for JavaScript errors

### Debug Mode
To enable debugging, add this to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Maintenance

### Regular Tasks
1. **Monitor upload directory** size and clean old files
2. **Review payment submissions** for manual verification
3. **Update payment statuses** as payments are confirmed
4. **Backup payment data** regularly

### Updates
- **Keep Bootstrap and FontAwesome** updated for security
- **Review payment methods** and add new ones as needed
- **Monitor user feedback** and adjust UI/UX accordingly

## Integration with Existing System

### Customer Dashboard Links
Add this link to your customer dashboard:
```html
<a href="customer_billing.php" class="btn btn-primary">
    <i class="fas fa-file-invoice-dollar"></i> Billing & Payments
</a>
```

### Admin Panel Integration
Consider creating an admin interface to:
- **View all payment submissions**
- **Update payment statuses**
- **Download payment proofs**
- **Generate payment reports**

## Support and Documentation

### For Developers
- **Code comments** explain complex functionality
- **Error logging** can be added for production monitoring
- **API responses** follow consistent JSON format

### For Users
- **Help tooltips** explain payment methods
- **Status indicators** show payment progress
- **Error messages** guide users to correct issues

---

## Summary

This enhanced billing system provides:
✅ **Secure customer billing** with profile hash authentication  
✅ **Improved user experience** with tabbed interface  
✅ **Multiple payment methods** with proper validation  
✅ **File upload capability** for payment proofs  
✅ **Comprehensive error handling** and fallbacks  
✅ **Mobile-responsive design** for all devices  
✅ **Database flexibility** adapts to existing schema  

The system is now ready for deployment and will resolve the issues with:
- Frozen invoice interactions
- Unclear payment flow
- Limited payment method support
- Poor error handling
- Lack of payment history tracking

Deploy following this guide and test thoroughly before going live with customers.