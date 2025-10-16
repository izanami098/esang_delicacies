<?php
require_once __DIR__ . '/../_bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Common_CSS/sidebar.css">
    <link rel="stylesheet" href="../VCSS/Admin_CSS/admin_dashboard.css">
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
        <span class="openbtn" onclick="openNav()">&#9776;</span>
        <div class="dashboard-container">
            <section class="summary-cards">
                <div class="card total-menus">
                    <div class="card-header">Total Menus</div>
                    <div class="card-value" id="totalMenus">0</div>
                </div>
                <div class="card total-customers">
                    <div class="card-header">Total Customers</div>
                    <div class="card-value" id="totalCustomers">0</div>
                </div>
                <div class="card total-sales">
                    <div class="card-header">Total Sales</div>
                    <div class="card-value" id="totalSales">₱0.0</div>
                </div>
            </section>

            <div class="dashboard-sections-bottom"> <div class="card order-summary-card"> <h2>Order Summary</h2>
                    <div class="chart-container">
                        <canvas id="orderPieChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="legend-color processed"></span> Processed orders
                        </div>
                        <div class="legend-item">
                            <span class="legend-color completed"></span> Completed orders
                        </div>
                        <div class="legend-item">
                            <span class="legend-color pending"></span> Pending orders
                        </div>
                    </div>
                </div>

                <section class="card menu-list-section"> <h2>Menu Items</h2>
                    <div class="menu-grid" id="menuGrid">
                        <div class="menu-item">
                            <span class="popular-tag">POPULAR choice</span>
                            <h3>halaya with Leche flan</h3>
                            <p class="price">₱150.00</p>
                        </div>
                        <div class="menu-item">
                            <h3>Baked ube halaya with Leche flan</h3>
                            <p class="price">₱150.00</p>
                        </div>
                         <div class="menu-item">
                            <span class="popular-tag">POPULAR choice</span>
                            <h3>Leche flan</h3>
                            <p class="price">₱80.00</p>
                        </div>
                        <div class="menu-item">
                            <span class="popular-tag">POPULAR choice</span>
                            <h3>Black kuchinta</h3>
                            <p class="price">₱70.00</p>
                        </div>
                        </div>
                </section>
            </div>
        </div>
    </div>
    
    <script src="../VJavaScript/sidebar.js"></script>
    <script src="../VJavaScript/admin_dashboard.js"></script>
</body>
</html>