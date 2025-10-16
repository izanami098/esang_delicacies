<?php
session_start();
require_once 'config/environment.php';
require_once 'includes/db.php';
require_once 'app/classes/ProfileHashManager.php';
require_once 'app/auth/HashBasedAuth.php';

// STRICT: Check if customer is authenticated via profile hash
$auth = new HashBasedAuth($pdo);
if (!$auth->isCustomerAuthenticated()) {
    header("Location: customer_login.php");
    exit();
}

// Get authenticated customer data - ONLY for this specific profile
$customerData = $auth->getAuthenticatedCustomer();
$profileHashManager = new ProfileHashManager($pdo);

// Extract customer information
$customerId = $customerData['customer_id'];
$customerName = $customerData['name'] ?? 'Customer';
$customerEmail = $customerData['email'] ?? '';
$profileHash = $customerData['profile_hash'];

// Log dashboard access for security audit
$profileHashManager->logProfileAccess($profileHash, 'dashboard_accessed', [
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Get customer's recent orders - STRICTLY filtered by customer ID
try {
    $recentOrdersStmt = $pdo->prepare("
        SELECT order_id, order_date, total_amount, order_status, payment_status 
        FROM orders 
        WHERE customerId = ? 
        ORDER BY order_date DESC 
        LIMIT 5
    ");
    $recentOrdersStmt->execute([$customerId]);
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Error fetching customer orders: " . $e->getMessage());
}

// Get customer's order statistics - STRICTLY filtered by customer ID  
try {
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_spent,
            COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders
        FROM orders 
        WHERE customerId = ?
    ");
    $statsStmt->execute([$customerId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $stats = ['total_orders' => 0, 'total_spent' => 0, 'pending_orders' => 0];
    error_log("Error fetching customer stats: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Esang Delicacies</title>
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .quick-action-btn {
            width: 100%;
            margin-bottom: 1rem;
            border-radius: 10px;
            padding: 1rem;
            font-weight: 500;
        }
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
        }
        .profile-security-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            font-size: 0.8rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-utensils me-2"></i>Esang Delicacies
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="customer_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_orders.php">
                            <i class="fas fa-shopping-bag me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_billing.php">
                            <i class="fas fa-file-invoice-dollar me-1"></i>Billing
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                    <!-- Notification Bell will be inserted here by JavaScript -->
                    <li class="nav-item">
                        <a class="nav-link" href="customer_logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
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
                    <h1 class="mb-2">Welcome back, <?php echo htmlspecialchars($customerName); ?>!</h1>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-shield-alt me-2"></i>
                        Your account is secure with profile ID: <?php echo substr($profileHash, 0, 8); ?>...
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-none d-md-block">
                        <i class="fas fa-user-circle" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo $stats['total_orders']; ?></div>
                    <div>Total Orders</div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-number">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
                    <div>Total Spent</div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo $stats['pending_orders']; ?></div>
                    <div>Pending Orders</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-bolt me-2 text-primary"></i>Quick Actions
                        </h5>
                        
                        <a href="menu.php" class="btn btn-primary quick-action-btn">
                            <i class="fas fa-plus me-2"></i>Place New Order
                        </a>
                        
                        <a href="customer_orders.php" class="btn btn-outline-primary quick-action-btn">
                            <i class="fas fa-list me-2"></i>View All Orders
                        </a>
                        
                        <a href="customer_billing.php" class="btn btn-outline-success quick-action-btn">
                            <i class="fas fa-credit-card me-2"></i>Billing & Payments
                        </a>
                        
                        <a href="customer_profile.php" class="btn btn-outline-secondary quick-action-btn">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-lg-8 mb-4">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2 text-primary"></i>Recent Orders
                        </h5>
                        <a href="customer_orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    
                    <?php if (!empty($recentOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge bg-<?php echo getStatusColor($order['order_status']); ?> text-white">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge bg-<?php echo getPaymentStatusColor($order['payment_status'] ?? 'pending'); ?> text-white">
                                                    <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="customer_order_details.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-bag text-muted mb-3" style="font-size: 3rem;"></i>
                            <h6 class="text-muted">No orders yet</h6>
                            <p class="text-muted">Start by placing your first order!</p>
                            <a href="menu.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Place Order
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Account Security Info -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">
                                    <i class="fas fa-shield-alt text-success me-2"></i>Account Security
                                </h6>
                                <p class="text-muted mb-0">
                                    Your account is protected with advanced profile-based security. 
                                    Only you can access your data and orders.
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <small class="text-muted">
                                    Profile Hash: <?php echo substr($profileHash, 0, 12); ?>...
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Indicator -->
    <div class="profile-security-indicator">
        <i class="fas fa-lock me-1"></i>Secure Profile Active
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/notification-system.js"></script>
    <script>
        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            // Create notification system instance
            const notificationSystem = new NotificationSystem({
                wsUrl: '<?php echo defined("WS_URL") ? WS_URL : getWebSocketUrl(); ?>',
                apiBase: 'api',
                profileHash: '<?php echo $profileHash; ?>',
                sessionId: '<?php echo session_id(); ?>'
            });
            
            // Store instance globally for debugging
            window.customerNotifications = notificationSystem;
            
            console.log('Notification system initialized for customer:', '<?php echo htmlspecialchars($customerName); ?>');
        });
        
        // Auto-refresh security indicator every 30 seconds
        setInterval(function() {
            const indicator = document.querySelector('.profile-security-indicator');
            if (indicator) {
                indicator.style.opacity = '0.5';
                setTimeout(() => {
                    indicator.style.opacity = '1';
                }, 200);
            }
        }, 30000);
        
        // Periodically refresh dashboard data
        setInterval(function() {
            // Refresh notifications silently
            if (window.customerNotifications) {
                window.customerNotifications.refreshNotifications();
            }
        }, 60000); // Every minute
    </script>
</body>
</html>

<?php
// Helper functions for status colors
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'processing': return 'primary';
        case 'ready': return 'success';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPaymentStatusColor($status) {
    switch(strtolower($status)) {
        case 'pending': return 'warning';
        case 'completed': return 'success';
        case 'failed': return 'danger';
        case 'cancelled': return 'secondary';
        default: return 'warning';
    }
}
?>