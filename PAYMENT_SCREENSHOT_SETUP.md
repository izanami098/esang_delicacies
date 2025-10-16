# Payment Screenshot Upload System - Setup Instructions

## 🎯 Overview
This system allows customers to upload payment screenshots when selecting GCash or Bank Transfer (Metrobank) payment methods during checkout. Order managers can then view and verify these screenshots before confirming orders.

## 📋 Setup Steps

### 1. Database Setup
Run the SQL script to add the necessary database columns and tables:
```sql
-- Execute this file in your MySQL database
mysql> source C:\xampp\htdocs\esang_delicacies\sql\add_payment_screenshots.sql
```

### 2. Directory Permissions
Ensure the uploads directory has proper write permissions:
- `C:\xampp\htdocs\esang_delicacies\uploads\payment_screenshots\`

### 3. Test the System

#### For Customers (orders.php):
1. Go to the orders page
2. Add items to cart and click "Checkout"
3. Select "GCash" or "Bank Transfer (Metrobank)" as payment method
4. The payment screenshot upload section will appear
5. Upload a payment screenshot (JPG, JPEG, PNG - max 5MB)
6. Complete the order

#### For Order Managers (order_management.php):
1. Go to the order management dashboard
2. View orders with payment screenshots in the "Proof of Payment" column
3. Click "📷 View" to see the uploaded screenshot
4. Click "✓ Verify Payment" to mark payment as verified
5. Once verified, you can "Confirm Order"

## 🔧 Technical Details

### Customer Features:
- **Conditional Upload Field**: Only shows for GCash and Bank Transfer
- **Drag & Drop Support**: Users can drag files or click to browse
- **File Validation**: Client and server-side validation for file type and size
- **Preview**: Shows thumbnail of uploaded image before submission
- **Payment Instructions**: Displays payment details for selected method

### Order Manager Features:
- **Payment Status Indicators**: 
  - ✅ COD (for Cash on Delivery)
  - ✅ Verified (for verified payments)
  - ⚠ Pending (waiting for verification)
- **Screenshot Viewer**: Modal popup to view uploaded screenshots
- **Payment Verification**: One-click verification system
- **Smart Controls**: Buttons are enabled/disabled based on payment status

### Security Features:
- **File Type Validation**: Only JPG, JPEG, PNG allowed
- **File Size Limits**: Maximum 5MB per upload
- **Secure File Storage**: Files stored with random names
- **Access Control**: Only authenticated users can view screenshots
- **Database Integrity**: Foreign key constraints and transaction safety

## 📁 Files Created/Modified

### New Files:
- `sql/add_payment_screenshots.sql` - Database schema updates
- `api/upload_payment_screenshot.php` - File upload endpoint
- `api/get_payment_screenshot.php` - Retrieve screenshot data
- `api/view_payment_screenshot.php` - Secure image viewing
- `api/verify_payment.php` - Payment verification endpoint

### Modified Files:
- `app/views/customer/orders.php` - Added upload interface
- `app/views/order_manager/order_management.php` - Added screenshot viewing

### Directory Structure:
```
esang_delicacies/
├── uploads/
│   └── payment_screenshots/     # Uploaded screenshots stored here
├── api/
│   ├── upload_payment_screenshot.php
│   ├── get_payment_screenshot.php
│   ├── view_payment_screenshot.php
│   └── verify_payment.php
└── sql/
    └── add_payment_screenshots.sql
```

## 🚀 Usage Examples

### Customer Upload Process:
1. Customer selects GCash payment method
2. Upload field appears with payment instructions
3. Customer uploads screenshot of GCash transfer
4. Order is submitted with screenshot attached

### Order Manager Verification:
1. Order appears in pending with "⚠ Pending" payment status
2. Manager clicks "📷 View" to see screenshot
3. Manager verifies payment details in screenshot
4. Manager clicks "✓ Verify Payment" to approve
5. Status changes to "✅ Verified"
6. Manager can now "Confirm Order"

## 🔒 Security Considerations

1. **File Upload Security**: 
   - Only image files allowed
   - File size restrictions
   - Secure filename generation

2. **Access Control**: 
   - Screenshots only viewable by order managers
   - Database-driven access verification

3. **Data Integrity**:
   - Foreign key constraints
   - Transaction-based uploads
   - Audit trail support

## 💡 Customization Options

### Payment Account Details:
Edit the payment instructions in `orders.php` to include your actual account details:
- GCash number (line ~1715)
- Metrobank account details (line ~1725)

### File Size Limits:
Modify the `MAX_FILE_SIZE` constant in `upload_payment_screenshot.php` to change upload limits.

### Supported File Types:
Update `ALLOWED_MIME_TYPES` and `ALLOWED_EXTENSIONS` arrays to support additional image formats.

## 🆘 Troubleshooting

### Common Issues:

1. **Upload fails**: Check directory permissions and PHP file upload settings
2. **Screenshots not visible**: Verify database connection and file paths
3. **Modal not opening**: Check browser console for JavaScript errors

### Debug Steps:
1. Check PHP error logs for upload issues
2. Verify database tables were created properly
3. Test file permissions in uploads directory
4. Check browser network tab for API call failures

---

## 🎉 Congratulations!
Your payment screenshot upload system is now ready! Customers can upload proof of payment, and order managers can verify them before processing orders.