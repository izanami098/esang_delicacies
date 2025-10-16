<?php
session_start();
require_once 'app/config/database.php';

// Role-based authentication - Order Manager role only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'order_manager') {
    // Check if user is logged in with order manager role
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?error=authentication_required');
        exit();
    }
    
    // If logged in but not order manager role
    if ($_SESSION['user_role'] !== 'order_manager') {
        header('Location: dashboard.php?error=insufficient_permissions');
        exit();
    }
}

// Get order manager information
$orderManagerName = $_SESSION['user_name'] ?? 'Order Manager';
$orderManagerRole = $_SESSION['user_role'] ?? 'order_manager';

try {
    $db = Database::getConnection();
    
    // Get order statistics
    $stats_query = "
        SELECT 
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status IN ('confirmed', 'processing', 'on_delivery') THEN 1 END) as ongoing_count,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_count,
            COUNT(CASE WHEN status IN ('cancelled', 'returned') THEN 1 END) as return_count,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
        FROM orders
    ";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching order statistics: " . $e->getMessage());
    $stats = [
        'pending_count' => 0,
        'ongoing_count' => 0, 
        'completed_count' => 0,
        'return_count' => 0,
        'rejected_count' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Esang Delicacies Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
        }
        
        .main-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 2rem;
            overflow: hidden;
        }
        
        .return-orders-content, .return-management-content {
            background-color: white !important;
        }
        
        .nav-pills .nav-link {
            border-radius: 25px;
            margin-right: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .nav-pills .nav-link:not(.active) {
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .nav-pills .nav-link:not(.active):hover {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .badge-count {
            background-color: #dc3545;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.75rem;
            margin-left: 8px;
        }
        
        .table-container {
            background-color: white;
            padding: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-weight: 500;
        }
        
        .btn-view {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 20px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .stats-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
        }
        
        .header-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .header-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-clipboard-list me-2"></i>Esang Delicacies - Order Manager
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-tie me-1"></i>Welcome, <?php echo htmlspecialchars($orderManagerName); ?>
                    <small class="badge bg-info ms-1"><?php echo strtoupper($orderManagerRole); ?></small>
                </span>
                <a class="nav-link" href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="main-content">
            <!-- Header Section -->
            <div class="stats-header">
                <h1 class="header-title">
                    <i class="fas fa-clipboard-list me-3"></i>Order Management
                </h1>
                <p class="header-subtitle">Manage all orders, returns, and customer requests</p>
            </div>

            <!-- Navigation Pills -->
            <div class="px-4">
                <ul class="nav nav-pills mb-4" id="orderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="pill" 
                                data-bs-target="#pending" type="button" role="tab">
                            <i class="fas fa-clock me-2"></i>Pending Orders
                            <span class="badge-count"><?php echo $stats['pending_count']; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ongoing-tab" data-bs-toggle="pill" 
                                data-bs-target="#ongoing" type="button" role="tab">
                            <i class="fas fa-truck me-2"></i>Ongoing Orders
                            <span class="badge-count"><?php echo $stats['ongoing_count']; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="pill" 
                                data-bs-target="#completed" type="button" role="tab">
                            <i class="fas fa-check-circle me-2"></i>Completed Orders
                            <span class="badge-count"><?php echo $stats['completed_count']; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="returns-tab" data-bs-toggle="pill" 
                                data-bs-target="#returns" type="button" role="tab">
                            <i class="fas fa-undo me-2"></i>Return Orders
                            <span class="badge-count"><?php echo $stats['return_count']; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="return-management-tab" data-bs-toggle="pill" 
                                data-bs-target="#return-management" type="button" role="tab">
                            <i class="fas fa-ban me-2"></i>Return Management
                            <span class="badge-count"><?php echo $stats['rejected_count']; ?></span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="orderTabsContent">
                <!-- Pending Orders -->
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-clock text-warning me-2"></i>Pending Orders</h4>
                            <button class="btn btn-outline-primary" onclick="refreshPendingOrders()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="loading-spinner" id="pendingLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading pending orders...</p>
                        </div>
                        <div id="pendingOrdersList"></div>
                    </div>
                </div>

                <!-- Ongoing Orders -->
                <div class="tab-pane fade" id="ongoing" role="tabpanel">
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-truck text-primary me-2"></i>Ongoing Orders</h4>
                            <button class="btn btn-outline-primary" onclick="refreshOngoingOrders()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="loading-spinner" id="ongoingLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading ongoing orders...</p>
                        </div>
                        <div id="ongoingOrdersList"></div>
                    </div>
                </div>

                <!-- Completed Orders -->
                <div class="tab-pane fade" id="completed" role="tabpanel">
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-check-circle text-success me-2"></i>Completed Orders</h4>
                            <button class="btn btn-outline-primary" onclick="refreshCompletedOrders()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="loading-spinner" id="completedLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading completed orders...</p>
                        </div>
                        <div id="completedOrdersList"></div>
                    </div>
                </div>

                <!-- Return Orders -->
                <div class="tab-pane fade return-orders-content" id="returns" role="tabpanel">
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-undo text-danger me-2"></i>Return Orders</h4>
                            <button class="btn btn-outline-primary" onclick="refreshReturnOrders()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="loading-spinner" id="returnsLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading return orders...</p>
                        </div>
                        <div id="returnOrdersList"></div>
                    </div>
                </div>

                <!-- Return Management -->
                <div class="tab-pane fade return-management-content" id="return-management" role="tabpanel">
                    <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><i class="fas fa-ban text-secondary me-2"></i>Return Management (Rejected)</h4>
                            <button class="btn btn-outline-primary" onclick="refreshReturnManagement()">
                                <i class="fas fa-sync me-1"></i>Refresh
                            </button>
                        </div>
                        <div class="loading-spinner" id="returnManagementLoading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading rejected returns...</p>
                        </div>
                        <div id="returnManagementList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Screenshot Modal -->
    <div class="modal fade" id="screenshotModal" tabindex="-1" aria-labelledby="screenshotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="screenshotModalLabel">Payment Proof Screenshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="screenshotImage" src="" alt="Payment Screenshot" class="img-fluid" style="max-height: 500px;">
                    <div id="screenshotError" class="alert alert-danger mt-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Error loading screenshot
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            refreshPendingOrders();
        });

        // Tab change handlers
        document.getElementById('ongoing-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) refreshOngoingOrders();
        });

        document.getElementById('completed-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) refreshCompletedOrders();
        });

        document.getElementById('returns-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) refreshReturnOrders();
        });

        document.getElementById('return-management-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) refreshReturnManagement();
        });

        // Refresh functions
        function refreshPendingOrders() {
            showLoading('pendingLoading');
            fetchOrders('pending', 'pendingOrdersList', 'pendingLoading');
        }

        function refreshOngoingOrders() {
            showLoading('ongoingLoading');
            fetchOrders(['confirmed', 'processing', 'on_delivery'], 'ongoingOrdersList', 'ongoingLoading');
        }

        function refreshCompletedOrders() {
            showLoading('completedLoading');
            fetchOrders('delivered', 'completedOrdersList', 'completedLoading');
        }

        function refreshReturnOrders() {
            showLoading('returnsLoading');
            fetchOrders(['cancelled', 'returned'], 'returnOrdersList', 'returnsLoading');
        }

        function refreshReturnManagement() {
            showLoading('returnManagementLoading');
            fetchOrders('rejected', 'returnManagementList', 'returnManagementLoading');
        }

        // Generic function to fetch orders
        function fetchOrders(status, containerId, loadingId) {
            const statusParam = Array.isArray(status) ? status.join(',') : status;
            
            fetch(`api/admin_orders.php?action=get_orders&status=${statusParam}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading(loadingId);
                    if (data.success) {
                        displayOrders(data.orders, containerId, status);
                    } else {
                        showError(containerId, data.message || 'Failed to load orders');
                    }
                })
                .catch(error => {
                    hideLoading(loadingId);
                    console.error('Error loading orders:', error);
                    showError(containerId, 'Error loading orders. Please try again.');
                });
        }

        // Display orders
        function displayOrders(orders, containerId, status) {
            const container = document.getElementById(containerId);
            
            if (orders.length === 0) {
                container.innerHTML = getEmptyState(status);
                return;
            }

            const orderCards = orders.map(order => createOrderCard(order, status)).join('');
            container.innerHTML = orderCards;
        }

        // Create order card HTML
        function createOrderCard(order, status) {
            const statusColor = getStatusColor(order.status || status);
            const hasScreenshot = order.payment_screenshot || order.proof_screenshot;
            
            return `
                <div class="order-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">
                                        <strong>Order #${order.order_id}</strong>
                                        <span class="status-badge bg-${statusColor} text-white ms-2">
                                            ${(order.status || status).toUpperCase()}
                                        </span>
                                    </h6>
                                    <small class="text-muted">
                                        ${new Date(order.order_date || order.created_at).toLocaleDateString()}
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    <strong>Customer:</strong> ${order.customer_name || 'N/A'}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-phone me-1 text-success"></i>
                                    <strong>Phone:</strong> ${order.customer_phone || 'N/A'}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                    <strong>Address:</strong> ${order.delivery_address || 'N/A'}
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-credit-card me-1 text-warning"></i>
                                    <strong>Payment:</strong> ${order.payment_method || 'N/A'}
                                    ${order.payment_verified == 1 ? '<span class="badge bg-success ms-1">Verified</span>' : '<span class="badge bg-warning ms-1">Pending</span>'}
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="h5 text-primary mb-2">₱${parseFloat(order.total_amount || 0).toFixed(2)}</div>
                                <div class="btn-group-vertical w-100" role="group">
                                    ${hasScreenshot ? `
                                        <button class="btn btn-outline-info btn-view mb-1" onclick="viewScreenshot(${order.order_id})">
                                            <i class="fas fa-image me-1"></i>View Proof
                                        </button>
                                    ` : ''}
                                    <button class="btn btn-outline-primary btn-view" onclick="viewOrderDetails(${order.order_id})">
                                        <i class="fas fa-eye me-1"></i>Details
                                    </button>
                                </div>
                            </div>
                        </div>
                        ${order.special_instructions ? `
                            <div class="mt-2 p-2 bg-light rounded">
                                <small><i class="fas fa-sticky-note me-1"></i><strong>Special Instructions:</strong> ${order.special_instructions}</small>
                            </div>
                        ` : ''}
                        ${order.return_reason ? `
                            <div class="mt-2 p-2 bg-warning-light rounded">
                                <small><i class="fas fa-exclamation-circle me-1"></i><strong>Return Reason:</strong> ${order.return_reason}</small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        // View screenshot function
        function viewScreenshot(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('screenshotModal'));
            const img = document.getElementById('screenshotImage');
            const error = document.getElementById('screenshotError');
            
            // Reset modal state
            img.style.display = 'block';
            error.style.display = 'none';
            img.src = '';
            
            // Set screenshot URL
            const screenshotUrl = `api/get_payment_screenshot.php?order_id=${orderId}`;
            
            img.onload = function() {
                img.style.display = 'block';
                error.style.display = 'none';
            };
            
            img.onerror = function() {
                img.style.display = 'none';
                error.style.display = 'block';
                error.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Screenshot not found or could not be loaded';
            };
            
            img.src = screenshotUrl;
            modal.show();
        }

        // View order details (placeholder)
        function viewOrderDetails(orderId) {
            alert(`View details for Order #${orderId} - Feature coming soon!`);
        }

        // Helper functions
        function getStatusColor(status) {
            const colors = {
                'pending': 'warning',
                'confirmed': 'info',
                'processing': 'primary',
                'on_delivery': 'primary',
                'delivered': 'success',
                'cancelled': 'danger',
                'returned': 'danger',
                'rejected': 'secondary'
            };
            return colors[status.toLowerCase()] || 'secondary';
        }

        function getEmptyState(status) {
            const messages = {
                'pending': { icon: 'clock', title: 'No Pending Orders', message: 'All orders are being processed.' },
                'ongoing': { icon: 'truck', title: 'No Ongoing Orders', message: 'No orders are currently being processed or delivered.' },
                'delivered': { icon: 'check-circle', title: 'No Completed Orders', message: 'No orders have been completed yet.' },
                'returned': { icon: 'undo', title: 'No Return Orders', message: 'No orders have been cancelled or returned.' },
                'rejected': { icon: 'ban', title: 'No Rejected Returns', message: 'No return requests have been rejected.' }
            };
            
            const statusKey = Array.isArray(status) ? 
                (status.includes('cancelled') || status.includes('returned') ? 'returned' : 'ongoing') : 
                status;
            
            const config = messages[statusKey] || messages['pending'];
            
            return `
                <div class="empty-state">
                    <i class="fas fa-${config.icon}"></i>
                    <h5>${config.title}</h5>
                    <p>${config.message}</p>
                </div>
            `;
        }

        function showLoading(elementId) {
            document.getElementById(elementId).style.display = 'block';
        }

        function hideLoading(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        function showError(containerId, message) {
            document.getElementById(containerId).innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </div>
            `;
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            const activeTab = document.querySelector('.nav-link.active').id;
            switch(activeTab) {
                case 'pending-tab': refreshPendingOrders(); break;
                case 'ongoing-tab': refreshOngoingOrders(); break;
                case 'completed-tab': refreshCompletedOrders(); break;
                case 'returns-tab': refreshReturnOrders(); break;
                case 'return-management-tab': refreshReturnManagement(); break;
            }
        }, 30000);
    </script>
</body>
</html>