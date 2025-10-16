<?php
require_once __DIR__ . '/../_bootstrap.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../auth/LogIn.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Maintenance</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Admin_CSS/user_maintenance.css">
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

    <div class="main-container">
        <div class="header">
            <div class="controls">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search...">
                    <i class="fas fa-search"></i>
                </div>
                <button class="btn btn-primary" onclick="openModal('create')">Create new user</button>
                <button class="btn btn-primary" onclick="openModal('add')">Add new user</button>
                <button class="btn btn-secondary" onclick="addSampleStaff()">Add Sample Staff</button>
                <button class="btn btn-info" onclick="debugSession()">Debug Session</button>
            </div>
        </div>

        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Modules Assigned</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <tr>
                    <td colspan="7" class="text-center">Loading staff accounts...</td>
                </tr>
            </tbody>
        </table>

    </div>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="cashier">Cashier</option>
                        <option value="rider">Rider</option>
                        <option value="order manager">Order Manager</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modules">Modules Assigned</label>
                    <div id="modulesContainer">
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #4CAF50;">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <p id="confirmMessage"></p>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn btn-primary" style="background-color: #28a745;" onclick="confirmStatusChange(true)">Yes</button>
                <button class="btn" onclick="confirmStatusChange(false)">No</button>
            </div>
        </div>
    </div>

    </div>
    <script src="../VJavaScript/user_maintenance.js"></script>
    <script src="../VJavaScript/sidebar.js"></script>
</body>
</html>