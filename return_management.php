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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Management - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background-color: white;
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .order-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid #dc3545;
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
        
        .rejection-reason {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.5rem;
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
        
        .alert-info-header {
            background-color: #d1ecf1;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-ban me-3"></i>Return Management
                        </h1>
                        <p class="mb-0 opacity-75">
                            View only rejected return requests from the database
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-light" onclick="refreshRejectedReturns()">
                            <i class="fas fa-sync me-2"></i>Refresh Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert-info-header">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 text-info"></i>
                    <div>
                        <strong>Return Management Information:</strong> This page displays only rejected return requests. 
                        These are requests that have been reviewed and denied by the admin team.
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="row">
                <div class="col-12">
                    <div class="loading-spinner" id="loadingSpinner">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading rejected return requests from database...</p>
                    </div>
                    <div id="rejectedReturnsList"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            refreshRejectedReturns();
        });

        function refreshRejectedReturns() {
            showLoading();
            
            fetch('api/admin_orders.php?action=get_orders&status=rejected')
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        displayRejectedReturns(data.orders);
                    } else {
                        showError(data.message || 'Failed to load rejected returns');
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error loading rejected returns:', error);
                    showError('Error loading rejected return requests from database. Please try again.');
                });
        }

        function displayRejectedReturns(orders) {
            const container = document.getElementById('rejectedReturnsList');
            
            if (orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-ban"></i>
                        <h4>No Rejected Returns</h4>
                        <p>No rejected return requests found in the database.</p>
                        <small class="text-muted">This is good news! It means no return requests have been rejected.</small>
                    </div>
                `;
                return;
            }

            const orderCards = orders.map(order => createRejectedReturnCard(order)).join('');
            container.innerHTML = orderCards;
        }

        function createRejectedReturnCard(order) {
            return `
                <div class="order-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="mb-0">
                                        <i class="fas fa-times-circle me-2 text-danger"></i>
                                        Order #${order.order_id}
                                        <span class="status-badge bg-secondary text-white ms-2">
                                            REJECTED
                                        </span>
                                    </h5>
                                    <small class="text-muted">
                                        ${order.formatted_date || new Date(order.order_date || order.created_at).toLocaleDateString()}
                                    </small>
                                </div>
                                
                                <div class="mb-2">
                                    <p class="mb-1">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <strong>Customer:</strong> ${order.customer_name}
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-phone me-2 text-success"></i>
                                        <strong>Phone:</strong> ${order.customer_phone}
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        <strong>Delivery Address:</strong> ${order.delivery_address}
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-credit-card me-2 text-warning"></i>
                                        <strong>Payment Method:</strong> ${order.payment_method}
                                        ${order.payment_verified == 1 ? 
                                            '<span class="badge bg-success ms-1">Verified</span>' : 
                                            '<span class="badge bg-secondary ms-1">Unverified</span>'
                                        }
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-3">
                                    <div class="h4 text-secondary">₱${parseFloat(order.total_amount || 0).toFixed(2)}</div>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-ban me-1"></i>Rejected
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(${order.order_id})">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        ${order.return_reason ? `
                            <div class="rejection-reason">
                                <i class="fas fa-times-circle me-2 text-danger"></i>
                                <strong>Return Request Reason:</strong> ${order.return_reason}
                            </div>
                        ` : ''}
                        
                        <div class="rejection-reason">
                            <i class="fas fa-gavel me-2 text-danger"></i>
                            <strong>Rejection Status:</strong> This return request has been reviewed and rejected by the admin team. 
                            The customer has been notified of the decision.
                        </div>
                        
                        ${order.special_instructions ? `
                            <div class="mt-2 p-2 bg-light rounded">
                                <i class="fas fa-sticky-note me-2"></i>
                                <strong>Special Instructions:</strong> ${order.special_instructions}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }

        function viewOrderDetails(orderId) {
            alert(`Viewing details for rejected return Order #${orderId} - Feature coming soon!`);
        }

        function showLoading() {
            document.getElementById('loadingSpinner').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loadingSpinner').style.display = 'none';
        }

        function showError(message) {
            document.getElementById('rejectedReturnsList').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>${message}
                </div>
            `;
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshRejectedReturns, 30000);
    </script>
</body>
</html>