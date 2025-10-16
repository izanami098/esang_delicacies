<?php
require_once __DIR__ . '/../_bootstrap.php';

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header('Location: ../auth/LogIn.php');
    exit;
}

// Get user role and name for display
$userRole = $_SESSION['role'];
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : $userRole;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Common_CSS/profile.css">
    <link rel="stylesheet" href="../VCSS/Common_CSS/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidenav" id="mySidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <div class="profile">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-name"><span id="userNameDisplay"><?php echo htmlspecialchars($userName); ?></span></div>
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
        <a href="admin_profile.php" class="sidenav-item"><i class="fas fa-user-circle"></i> Profile</a>
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
        <span class="openbtn" onclick="openNav()">&#9776;</span>
        <div class="container">
            <h1 class="title">Profile Settings</h1>
            
            <!-- User Role Display -->
            <div class="user-role-display">
                <h3>User Type: <span id="userRoleDisplay"><?php echo htmlspecialchars($userRole); ?></span></h3>
            </div>
            
            <div class="form-group profile-photo-section">
                <label for="profilePhoto" class="required">Profile photo</label>
                <div class="profile-photo-circle" id="profilePhotoCircle">
                    <input type="file" id="profilePhoto" accept="image/*" style="display: none;">
                    <img id="profilePhotoPreview" src="#" alt="Profile Photo Review" class="hidden">
                </div>
                <p class="upload-text">Click to upload photo</p>
            </div>

            <div class="form-group">
                <label for="firstName" class="required">First Name: </label>
                <input type="text" id="firstName" placeholder="Enter your first name">
            </div>
            <div class="form-group">
                <label for="lastName" class="required">Last Name: </label>
                <input type="text" id="lastName" placeholder="Enter your last name">
            </div>
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" placeholder="Enter your email address" readonly>
            </div>
            <div class="form-group">
                <label for="phoneNumber" class="required">Phone Number(+63)</label>
                <input type="tel" id="phoneNumber" placeholder="Enter your phone number">
            </div>
            <div class="form-group">
                <label for="address">Address (Optional)</label>
                <input type="text" id="address" placeholder="Enter your address">
            </div>
            <div class="button-group">
                <button class="save-button" onclick="saveProfile()">Save Changes</button>
                <button class="cancel-button" onclick="resetProfile()">Reset</button>
            </div>
        </div>
    </div>
    <script src="../VJavaScript/sidebar.js"></script>
    <script src="../VJavaScript/admin_profile.js"></script>
</body>
</html>