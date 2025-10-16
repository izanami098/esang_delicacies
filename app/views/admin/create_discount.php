<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discounts</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Common_CSS/sidebar.css">
    <link rel="stylesheet" href="../VCSS/Admin_CSS/create_discount.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidenav" id="mySidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <div class="profile">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-name"><span>Admin</span></div>
        </div>
        <a href="admin_dashboard.php" class="sidenav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="admin_order_status.php" class="sidenav-item"><i class="fas fa-clipboard-list"></i> Order status</a>
        <div class="dropdown">
            <a href="#" class="sidenav-item dropdown-btn"><i class="fas fa-box"></i> Product Maintenance <i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
                <a href="product_maintenance.php">&bull; add item</a>
                <a href="manage_items.php">&bull; manage item</a>
                <a href="create_discount.php">&bull; create discount</a>
            </div>
        </div>
        <a href="user_maintenance.php" class="sidenav-item"><i class="fas fa-users"></i> User Maintenance</a>
        <a href="admin_performance.php" class="sidenav-item"><i class="fas fa-file-alt"></i> Analytics & Sales and Transaction History</a>
        <a href="#" class="sidenav-item" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Log Out</h2>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button id="cancelLogout" class="button cancel">Cancel</button>
                <button id="confirmLogout" class="button logout">Log Out</button>
            </div>
        </div>
    </div>
    
    <div class="main-content">
      <div class="discount-container">
        <h2>Create your first discount!</h2>
        <p class="subtitle">Create discounts to incentive your customers to engage more in your brand.</p>

        <form id="discountForm">
          <div class="form-group">
            <label for="discountName">Name:</label>
            <input type="text" id="discountName" required>
          </div>

          <div class="form-group">
            <label for="discountDesc">Description:</label>
            <textarea id="discountDesc" rows="2"></textarea>
          </div>

          <div class="form-group">
            <label>Availability:</label>
            <input type="date" id="startDate"> to
            <input type="date" id="endDate">
          </div>

          <div class="form-group">
            <label>Redemption Limit:</label>
            <button type="button" class="toggle-btn active" data-value="unlimited">Unlimited</button>
            <button type="button" class="toggle-btn" data-value="limited">Limited</button>
            <input type="hidden" id="redemptionLimit" value="unlimited">
          </div>

          <div class="form-group">
            <label>Type:</label>
            <button type="button" class="toggle-btn" data-value="set price">Set Price</button>
            <button type="button" class="toggle-btn active" data-value="percentage off">Percentage Off</button>
            <button type="button" class="toggle-btn" data-value="amount">Amount</button>
            <input type="hidden" id="discountType" value="percentage off">
          </div>

          <div class="form-group">
            <label for="discountValue">Value:</label>
            <input type="number" id="discountValue" placeholder="Enter value" required>
          </div>

          <div class="form-group">
            <label>Applicable Products:</label>
            <div id="productList" class="product-list"></div>
          </div>

          <div class="actions">
            <button type="button" id="cancelBtn" class="btn cancel">Cancel</button>
            <button type="submit" class="btn create">Create Discount</button>
          </div>
        </form>
      </div>
    </div>
    <script src="../VJavaScript/sidebar.js"></script>
    <script src="../VJavaScript/create_discount.js"></script>
</body>
</html>