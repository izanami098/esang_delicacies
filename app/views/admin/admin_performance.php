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
    <title>Data Analytics & Sales & Transactions</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Common_CSS/sidebar.css">
    <link rel="stylesheet" href="../VCSS/Admin_CSS/admin_performance.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <!-- Header -->
        <header class="header">
            <h1>Esang Delicacies Performance</h1>
        </header>

        <!-- Navigation Buttons -->
        <nav class="nav-buttons">
            <button id="weekly-btn" class="nav-btn active">Weekly</button>
            <button id="monthly-btn" class="nav-btn">Monthly</button>
            <button id="yearly-btn" class="nav-btn">Yearly</button>
            <button id="add-sample-btn" class="nav-btn" style="background-color: #6c757d; color: white;">Add Sample Data</button>
        </nav>

        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="total-orders">0</h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="total-revenue">₱0.00</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="avg-order-value">₱0.00</h3>
                        <p>Average Order Value</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="unique-customers">0</h3>
                        <p>Unique Customers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-section">
            <div class="chart-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-section">
            <h2>Transaction Details</h2>
            <div class="table-responsive-container">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Payment Type</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-table-body">
                        <!-- Table rows will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="../VJavaScript/sidebar.js"></script>
    <script src="../VJavaScript/admin_performance.js"></script>
</body>
</html>