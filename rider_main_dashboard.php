<?php
/**
 * Rider Main Dashboard
 * Alternative dashboard implementation with profile management
 */

session_start();
require_once 'includes/db.php';
require_once 'app/auth/HashBasedAuth.php';

$auth = new HashBasedAuth($pdo);

// Check if rider is authenticated
if (!$auth->isRiderAuthenticated()) {
    header('Location: rider_login.php');
    exit();
}

$rider = $auth->getAuthenticatedRider();
if (!$rider) {
    header('Location: rider_login.php');
    exit();
}

// Get rider statistics
try {
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_deliveries,
            COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as completed_deliveries,
            COUNT(CASE WHEN order_status = 'on_delivery' THEN 1 END) as active_deliveries,
            COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN delivery_fee END), 0) as total_earnings
        FROM orders 
        WHERE rider_id = ?
    ");
    $statsStmt->execute([$rider['rider_id']]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Get today's stats
    $todayStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as today_deliveries,
            COALESCE(SUM(delivery_fee), 0) as today_earnings
        FROM orders 
        WHERE rider_id = ? AND DATE(order_date) = CURDATE() AND order_status = 'delivered'
    ");
    $todayStmt->execute([$rider['rider_id']]);
    $todayStats = $todayStmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
    $stats = ['total_deliveries' => 0, 'completed_deliveries' => 0, 'active_deliveries' => 0, 'total_earnings' => 0];
    $todayStats = ['today_deliveries' => 0, 'today_earnings' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover { transform: translateY(-5px); }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .earnings-card { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #8b4513; }
        .deliveries-card { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #2c5530; }
        .profile-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-motorcycle"></i> Esang Delicacies - Rider</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($rider['name']); ?>!</span>
                <a class="nav-link" href="api/rider_auth.php?action=logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Profile & Status Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card profile-section">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4><i class="fas fa-user-circle"></i> Rider Profile</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($rider['name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($rider['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($rider['phone']); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="mb-3">
                                    <span class="badge fs-6 bg-<?php echo $rider['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($rider['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <button class="btn btn-light me-2" onclick="toggleStatus()">
                                        <i class="fas fa-power-off"></i> Toggle Status
                                    </button>
                                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#profileModal">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-2x mb-2"></i>
                        <h3><?php echo number_format($rider['rating'], 1); ?></h3>
                        <p class="mb-0">Rating</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card earnings-card">
                    <div class="card-body text-center">
                        <i class="fas fa-peso-sign fa-2x mb-2"></i>
                        <h3>₱<?php echo number_format($stats['total_earnings'], 2); ?></h3>
                        <p class="mb-0">Total Earnings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card deliveries-card">
                    <div class="card-body text-center">
                        <i class="fas fa-truck fa-2x mb-2"></i>
                        <h3><?php echo $stats['completed_deliveries']; ?></h3>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2 text-warning"></i>
                        <h3><?php echo $stats['active_deliveries']; ?></h3>
                        <p class="mb-0">Active Orders</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-calendar-day"></i> Today's Summary</h5></div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary"><?php echo $todayStats['today_deliveries']; ?></h4>
                                <small>Deliveries</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">₱<?php echo number_format($todayStats['today_earnings'], 2); ?></h4>
                                <small>Earnings</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5><i class="fas fa-tasks"></i> Quick Actions</h5></div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm me-2 mb-2" onclick="loadOrders()">
                            <i class="fas fa-box"></i> View My Orders
                        </button>
                        <button class="btn btn-success btn-sm me-2 mb-2" onclick="loadAvailableOrders()">
                            <i class="fas fa-search"></i> Find Orders
                        </button>
                        <button class="btn btn-info btn-sm me-2 mb-2" onclick="viewEarnings()">
                            <i class="fas fa-coins"></i> View Earnings
                        </button>
                        <button class="btn btn-warning btn-sm mb-2" onclick="viewNotifications()">
                            <i class="fas fa-bell"></i> Notifications
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Content Area -->
        <div id="dynamicContent"></div>
    </div>

    <!-- Profile Edit Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($rider['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($rider['phone']); ?>" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // API helper function
        async function makeApiCall(url, method = 'GET', data = null) {
            try {
                const options = { method, headers: { 'Content-Type': 'application/json' } };
                if (data) options.body = JSON.stringify(data);
                
                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                console.error('API call error:', error);
                return { success: false, message: 'Network error' };
            }
        }

        async function toggleStatus() {
            const currentStatus = '<?php echo $rider['status']; ?>';
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            const result = await makeApiCall('api/rider_profile.php?action=update_availability', 'POST', { status: newStatus });
            
            if (result.success) {
                alert('Status updated successfully');
                location.reload();
            } else {
                alert('Failed to update status: ' + result.message);
            }
        }

        async function updateProfile() {
            const formData = new FormData(document.getElementById('profileForm'));
            const data = Object.fromEntries(formData);
            
            const result = await makeApiCall('api/rider_profile.php?action=profile', 'PUT', data);
            
            if (result.success) {
                alert('Profile updated successfully');
                location.reload();
            } else {
                alert('Failed to update profile: ' + result.message);
            }
        }

        async function loadOrders() {
            const content = document.getElementById('dynamicContent');
            content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            
            const result = await makeApiCall('api/rider_orders.php?action=my_orders');
            
            if (result.success) {
                let html = '<div class="card"><div class="card-header"><h5>My Orders</h5></div><div class="card-body">';
                
                if (result.orders.length === 0) {
                    html += '<p class="text-muted">No orders found.</p>';
                } else {
                    result.orders.forEach(order => {
                        html += `
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>Order #${order.order_id}</h6>
                                            <p><strong>Customer:</strong> ${order.customer_name || order.customer_name_full}</p>
                                            <p><strong>Address:</strong> ${order.delivery_address}</p>
                                            <p><strong>Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)} | <strong>Fee:</strong> ₱${parseFloat(order.delivery_fee).toFixed(2)}</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge bg-${getStatusColor(order.order_status)} mb-2">${order.order_status.toUpperCase()}</span><br>
                                            ${order.order_status === 'on_delivery' ? `<button class="btn btn-success btn-sm" onclick="markAsDelivered(${order.order_id})">Mark Delivered</button>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += '</div></div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load orders: ' + result.message + '</div>';
            }
        }

        async function loadAvailableOrders() {
            const content = document.getElementById('dynamicContent');
            content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            
            const result = await makeApiCall('api/rider_orders.php?action=available_orders');
            
            if (result.success) {
                let html = '<div class="card"><div class="card-header"><h5>Available Orders</h5></div><div class="card-body">';
                
                if (result.orders.length === 0) {
                    html += '<p class="text-muted">No available orders at the moment.</p>';
                } else {
                    result.orders.forEach(order => {
                        html += `
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6>Order #${order.order_id}</h6>
                                            <p><strong>Customer:</strong> ${order.customer_name || order.customer_name_full}</p>
                                            <p><strong>Pickup:</strong> ${order.pickup_address}</p>
                                            <p><strong>Delivery:</strong> ${order.delivery_address}</p>
                                            <p><strong>Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)} | <strong>Fee:</strong> ₱${parseFloat(order.delivery_fee).toFixed(2)}</p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-primary" onclick="acceptOrder(${order.order_id})">Accept Order</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += '</div></div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load available orders: ' + result.message + '</div>';
            }
        }

        async function acceptOrder(orderId) {
            if (!confirm('Are you sure you want to accept this order?')) return;
            
            const result = await makeApiCall('api/rider_orders.php?action=accept_order', 'POST', { order_id: orderId });
            
            if (result.success) {
                alert('Order accepted successfully!');
                loadAvailableOrders();
            } else {
                alert('Failed to accept order: ' + result.message);
            }
        }

        async function markAsDelivered(orderId) {
            if (!confirm('Are you sure you want to mark this order as delivered?')) return;
            
            const result = await makeApiCall('api/rider_orders.php?action=update_status', 'PUT', { 
                order_id: orderId, 
                status: 'delivered' 
            });
            
            if (result.success) {
                alert('Order marked as delivered!');
                location.reload();
            } else {
                alert('Failed to update order status: ' + result.message);
            }
        }

        async function viewEarnings() {
            const content = document.getElementById('dynamicContent');
            content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            
            const result = await makeApiCall('api/rider_profile.php?action=earnings');
            
            if (result.success) {
                const earnings = result.earnings;
                const html = `
                    <div class="card">
                        <div class="card-header"><h5>Earnings Report</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <h4 class="text-success">₱${parseFloat(earnings.total.total_earnings).toFixed(2)}</h4>
                                    <p>Total Earnings</p>
                                    <small>${earnings.total.completed_deliveries} deliveries</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4 class="text-primary">₱${parseFloat(earnings.month.month_earnings).toFixed(2)}</h4>
                                    <p>This Month</p>
                                    <small>${earnings.month.month_deliveries} deliveries</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4 class="text-info">₱${parseFloat(earnings.week.week_earnings).toFixed(2)}</h4>
                                    <p>This Week</p>
                                    <small>${earnings.week.week_deliveries} deliveries</small>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h4 class="text-warning">₱${earnings.total.completed_deliveries > 0 ? (earnings.total.total_earnings / earnings.total.completed_deliveries).toFixed(2) : '0.00'}</h4>
                                    <p>Average per Delivery</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load earnings: ' + result.message + '</div>';
            }
        }

        async function viewNotifications() {
            const content = document.getElementById('dynamicContent');
            content.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            
            const result = await makeApiCall('api/rider_notifications.php?action=all&limit=10');
            
            if (result.success) {
                let html = '<div class="card"><div class="card-header"><h5>Recent Notifications</h5></div><div class="card-body">';
                
                if (result.notifications.length === 0) {
                    html += '<p class="text-muted">No notifications found.</p>';
                } else {
                    result.notifications.forEach(notification => {
                        html += `
                            <div class="card mb-2 ${!notification.is_read ? 'border-primary' : ''}">
                                <div class="card-body">
                                    <h6>${notification.title}</h6>
                                    <p>${notification.message}</p>
                                    <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                        `;
                    });
                }
                
                html += '</div></div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load notifications: ' + result.message + '</div>';
            }
        }

        function getStatusColor(status) {
            const colors = {
                'delivered': 'success',
                'on_delivery': 'primary',
                'ready_for_delivery': 'warning',
                'cancelled': 'danger'
            };
            return colors[status] || 'secondary';
        }
    </script>
</body>
</html>