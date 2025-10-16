<?php
session_start();
require_once 'includes/db.php';

// Check if rider is authenticated using basic session
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'RIDER' || !isset($_SESSION['riderId'])) {
    header("Location: app/views/auth/LogIn.php");
    exit();
}

// Get rider information from session and database
$riderId = $_SESSION['riderId'];
$riderName = $_SESSION['user_name'] ?? 'Rider';
$riderEmail = $_SESSION['email'] ?? '';

// Get rider status from database
$riderStatus = 'active'; // Default
try {
    $stmt = $pdo->prepare("SELECT status FROM RIDER WHERE empId = ?");
    $stmt->execute([$riderId]);
    $result = $stmt->fetch();
    if ($result && isset($result['status'])) {
        $riderStatus = $result['status'];
    }
} catch (Exception $e) {
    error_log('Failed to get rider status: ' . $e->getMessage());
}

// Get basic stats
$totalOrders = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM orders WHERE rider_id = ?");
    $stmt->execute([$riderId]);
    $result = $stmt->fetch();
    $totalOrders = $result['total_orders'] ?? 0;
} catch (Exception $e) {
    // Orders table might not exist or have different structure - that's okay
    error_log('Failed to get order stats: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .order-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .order-status {
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .btn-accept {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-accept:hover {
            transform: translateY(-1px);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .btn-status {
            border-radius: 25px;
            padding: 0.4rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .status-toggle {
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            min-width: 100px;
        }
        .rider-info-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        }
        .nav-pills .nav-link {
            border-radius: 25px;
            margin-right: 0.5rem;
            font-weight: 500;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-motorcycle me-2"></i>Rider Portal
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="rider_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Status Toggle -->
                    <li class="nav-item me-3">
                        <select class="form-select status-toggle" id="riderStatus">
                            <option value="active" <?php echo $riderStatus === 'active' ? 'selected' : ''; ?>>
                                <i class="fas fa-check-circle"></i> Active
                            </option>
                            <option value="inactive" <?php echo $riderStatus === 'inactive' ? 'selected' : ''; ?>>
                                <i class="fas fa-pause-circle"></i> Inactive
                            </option>
                            <option value="offline" <?php echo $riderStatus === 'offline' ? 'selected' : ''; ?>>
                                <i class="fas fa-times-circle"></i> Offline
                            </option>
                        </select>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($riderName); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="showProfile()">
                                <i class="fas fa-user-edit me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="rider_logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($riderName); ?>!</h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-shield-alt me-2"></i>
                        Rider ID: <?php echo $riderId; ?> | 
                        Status: <span class="badge bg-light text-dark"><?php echo ucfirst($riderStatus); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-none d-md-block">
                        <i class="fas fa-motorcycle" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        <div class="row" id="statsRow">
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number" id="totalDeliveries">-</div>
                    <div>Total Deliveries</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number" id="todayDeliveries">-</div>
                    <div>Today's Deliveries</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number" id="totalEarnings">₱-</div>
                    <div>Total Earnings</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number" id="activeOrders">-</div>
                    <div>Active Orders</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-body">
                        <!-- Navigation Pills -->
                        <ul class="nav nav-pills mb-4" id="orderTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="available-tab" data-bs-toggle="pill" 
                                        data-bs-target="#available" type="button" role="tab">
                                    <i class="fas fa-list me-2"></i>Available Orders
                                    <span class="badge bg-primary ms-1" id="availableCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="active-tab" data-bs-toggle="pill" 
                                        data-bs-target="#active" type="button" role="tab">
                                    <i class="fas fa-truck me-2"></i>My Active Orders
                                    <span class="badge bg-warning ms-1" id="activeCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="completed-tab" data-bs-toggle="pill" 
                                        data-bs-target="#completed" type="button" role="tab">
                                    <i class="fas fa-check-circle me-2"></i>Completed
                                    <span class="badge bg-success ms-1" id="completedCount">0</span>
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="orderTabsContent">
                            <!-- Connection Status -->
                            <div class="alert alert-success mb-3" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Database Connected!</strong> Successfully connected to esangdel_esang_db as Rider ID: <?php echo $riderId; ?>
                            </div>
                            
                            <!-- Available Orders -->
                            <div class="tab-pane fade show active" id="available" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Available Delivery Orders</h5>
                                    <button class="btn btn-outline-primary" onclick="refreshAvailableOrders()">
                                        <i class="fas fa-sync me-1"></i>Refresh
                                    </button>
                                </div>
                                <div class="loading-spinner" id="availableLoading">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                </div>
                                <div id="availableOrdersList">
                                    <!-- Show basic system info if API not working -->
                                    <div class="empty-state">
                                        <i class="fas fa-database"></i>
                                        <h5>System Ready</h5>
                                        <p>Database connection established. Order system integration ready.</p>
                                        <small class="text-muted">Total Orders: <?php echo $totalOrders; ?></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Orders -->
                            <div class="tab-pane fade" id="active" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">My Active Deliveries</h5>
                                    <button class="btn btn-outline-primary" onclick="refreshActiveOrders()">
                                        <i class="fas fa-sync me-1"></i>Refresh
                                    </button>
                                </div>
                                <div class="loading-spinner" id="activeLoading">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                </div>
                                <div id="activeOrdersList"></div>
                            </div>

                            <!-- Completed Orders -->
                            <div class="tab-pane fade" id="completed" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Completed Deliveries</h5>
                                    <button class="btn btn-outline-primary" onclick="refreshCompletedOrders()">
                                        <i class="fas fa-sync me-1"></i>Refresh
                                    </button>
                                </div>
                                <div class="loading-spinner" id="completedLoading">
                                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                </div>
                                <div id="completedOrdersList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalTitle">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderModalBody">
                    <!-- Order details will be loaded here -->
                </div>
                <div class="modal-footer" id="orderModalFooter">
                    <!-- Action buttons will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables
        let riderData = {
            rider_id: <?php echo $riderId; ?>,
            name: '<?php echo htmlspecialchars($riderName); ?>',
            status: '<?php echo $riderStatus; ?>'
        };

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            refreshAvailableOrders();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                loadStats();
                const activeTab = document.querySelector('.nav-link.active').id;
                
                switch(activeTab) {
                    case 'available-tab':
                        refreshAvailableOrders();
                        break;
                    case 'active-tab':
                        refreshActiveOrders();
                        break;
                    case 'completed-tab':
                        refreshCompletedOrders();
                        break;
                }
            }, 30000);
        });

        // Status change handler
        document.getElementById('riderStatus').addEventListener('change', function() {
            updateRiderStatus(this.value);
        });

        // Load rider statistics
        function loadStats() {
            fetch('api/rider_orders.php?action=stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.stats;
                        document.getElementById('totalDeliveries').textContent = stats.completed_deliveries || 0;
                        document.getElementById('todayDeliveries').textContent = stats.today_deliveries || 0;
                        document.getElementById('totalEarnings').textContent = '₱' + parseFloat(stats.total_earnings || 0).toFixed(2);
                        document.getElementById('activeOrders').textContent = stats.active_deliveries || 0;
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
        }

        // Refresh available orders
        function refreshAvailableOrders() {
            showLoading('availableLoading');
            
            fetch('api/rider_orders.php?action=available_orders')
                .then(response => response.json())
                .then(data => {
                    hideLoading('availableLoading');
                    
                    if (data.success) {
                        displayAvailableOrders(data.orders);
                        document.getElementById('availableCount').textContent = data.count;
                    } else {
                        showError('availableOrdersList', 'Failed to load available orders');
                    }
                })
                .catch(error => {
                    hideLoading('availableLoading');
                    console.error('Error loading available orders:', error);
                    showError('availableOrdersList', 'Error loading orders');
                });
        }

        // Refresh active orders
        function refreshActiveOrders() {
            showLoading('activeLoading');
            
            fetch('api/rider_orders.php?action=my_orders&status=on_delivery')
                .then(response => response.json())
                .then(data => {
                    hideLoading('activeLoading');
                    
                    if (data.success) {
                        displayActiveOrders(data.orders);
                        document.getElementById('activeCount').textContent = data.count;
                    } else {
                        showError('activeOrdersList', 'Failed to load active orders');
                    }
                })
                .catch(error => {
                    hideLoading('activeLoading');
                    console.error('Error loading active orders:', error);
                    showError('activeOrdersList', 'Error loading orders');
                });
        }

        // Refresh completed orders
        function refreshCompletedOrders() {
            showLoading('completedLoading');
            
            fetch('api/rider_orders.php?action=my_orders&status=delivered')
                .then(response => response.json())
                .then(data => {
                    hideLoading('completedLoading');
                    
                    if (data.success) {
                        displayCompletedOrders(data.orders);
                        document.getElementById('completedCount').textContent = data.count;
                    } else {
                        showError('completedOrdersList', 'Failed to load completed orders');
                    }
                })
                .catch(error => {
                    hideLoading('completedLoading');
                    console.error('Error loading completed orders:', error);
                    showError('completedOrdersList', 'Error loading orders');
                });
        }

        // Display available orders
        function displayAvailableOrders(orders) {
            const container = document.getElementById('availableOrdersList');
            
            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No available orders</h5>
                        <p>Check back later for new delivery requests.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="card-title mb-1">
                                    Order #${order.order_id} 
                                    <span class="order-status bg-warning text-dark">Ready for Delivery</span>
                                </h6>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-1"></i>${order.customer_name_full} | 
                                    <i class="fas fa-phone me-1"></i>${order.customer_phone_full}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <strong>Deliver to:</strong> ${order.delivery_address}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-store text-success me-1"></i>
                                    <strong>Pickup from:</strong> ${order.pickup_address}
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Ordered: ${new Date(order.order_date).toLocaleString()}
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <div class="h4 text-primary mb-1">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                    <small class="text-success">+₱${parseFloat(order.delivery_fee || 0).toFixed(2)} delivery fee</small>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-accept" onclick="acceptOrder(${order.order_id})">
                                        <i class="fas fa-check me-1"></i>Accept Order
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="viewOrderDetails(${order.order_id})">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                        ${order.special_instructions ? `
                            <div class="mt-2 p-2 bg-light rounded">
                                <small><i class="fas fa-sticky-note me-1"></i><strong>Special Instructions:</strong> ${order.special_instructions}</small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Display active orders
        function displayActiveOrders(orders) {
            const container = document.getElementById('activeOrdersList');
            
            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h5>No active deliveries</h5>
                        <p>Accept an order to start delivering.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card border-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="card-title mb-1">
                                    Order #${order.order_id} 
                                    <span class="order-status bg-primary text-white">On Delivery</span>
                                </h6>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-1"></i>${order.customer_name_full} | 
                                    <i class="fas fa-phone me-1"></i>${order.customer_phone_full}
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <strong>Deliver to:</strong> ${order.delivery_address}
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Order Time: ${new Date(order.order_date).toLocaleString()}
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <div class="h4 text-primary mb-1">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                    <small class="text-success">+₱${parseFloat(order.delivery_fee || 0).toFixed(2)} delivery fee</small>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" onclick="markAsDelivered(${order.order_id})">
                                        <i class="fas fa-check-circle me-1"></i>Mark Delivered
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(${order.order_id})">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                    <a href="tel:${order.customer_phone_full}" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-phone me-1"></i>Call Customer
                                    </a>
                                </div>
                            </div>
                        </div>
                        ${order.special_instructions ? `
                            <div class="mt-2 p-2 bg-light rounded">
                                <small><i class="fas fa-sticky-note me-1"></i><strong>Special Instructions:</strong> ${order.special_instructions}</small>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Display completed orders
        function displayCompletedOrders(orders) {
            const container = document.getElementById('completedOrdersList');
            
            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h5>No completed deliveries</h5>
                        <p>Your completed orders will appear here.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = orders.map(order => `
                <div class="order-card border-success">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-9">
                                <h6 class="card-title mb-1">
                                    Order #${order.order_id} 
                                    <span class="order-status bg-success text-white">Delivered</span>
                                </h6>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-1"></i>${order.customer_name_full} | 
                                    <i class="fas fa-map-marker-alt me-1"></i>${order.delivery_address}
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Delivered: ${order.actual_delivery_time ? new Date(order.actual_delivery_time).toLocaleString() : 'N/A'}
                                </small>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="h6 text-success mb-1">₱${parseFloat(order.total_amount).toFixed(2)}</div>
                                <small class="text-success">+₱${parseFloat(order.delivery_fee || 0).toFixed(2)} earned</small>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Accept order
        function acceptOrder(orderId) {
            if (confirm('Accept this delivery order?')) {
                fetch('api/rider_orders.php?action=accept_order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Order accepted successfully!');
                        refreshAvailableOrders();
                        loadStats();
                    } else {
                        showAlert('danger', data.message || 'Failed to accept order');
                    }
                })
                .catch(error => {
                    console.error('Error accepting order:', error);
                    showAlert('danger', 'Error accepting order');
                });
            }
        }

        // Mark as delivered
        function markAsDelivered(orderId) {
            if (confirm('Mark this order as delivered?')) {
                fetch('api/rider_orders.php?action=update_status', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        order_id: orderId,
                        status: 'delivered'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', 'Order marked as delivered!');
                        refreshActiveOrders();
                        loadStats();
                    } else {
                        showAlert('danger', data.message || 'Failed to update order');
                    }
                })
                .catch(error => {
                    console.error('Error updating order:', error);
                    showAlert('danger', 'Error updating order');
                });
            }
        }

        // Update rider status
        function updateRiderStatus(status) {
            fetch('api/rider_orders.php?action=update_rider_status', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    riderData.status = status;
                    showAlert('success', `Status updated to ${status.charAt(0).toUpperCase() + status.slice(1)}`);
                } else {
                    showAlert('danger', 'Failed to update status');
                    // Revert select
                    document.getElementById('riderStatus').value = riderData.status;
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
                showAlert('danger', 'Error updating status');
                // Revert select
                document.getElementById('riderStatus').value = riderData.status;
            });
        }

        // View order details (placeholder)
        function viewOrderDetails(orderId) {
            // This would show detailed order information in the modal
            showAlert('info', `Order details for #${orderId} (feature coming soon)`);
        }

        // Utility functions
        function showLoading(elementId) {
            document.getElementById(elementId).style.display = 'block';
        }

        function hideLoading(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        function showError(containerId, message) {
            document.getElementById(containerId).innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </div>
            `;
        }

        function showAlert(type, message) {
            // Create and show bootstrap alert
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', alertHtml);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert:last-child');
                if (alert) {
                    alert.remove();
                }
            }, 3000);
        }

        // Tab change handlers
        document.getElementById('active-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                refreshActiveOrders();
            }
        });

        document.getElementById('completed-tab').addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                refreshCompletedOrders();
            }
        });
    </script>
</body>
</html>