<?php
session_start();
require_once 'includes/db.php';
require_once 'app/classes/ProfileHashManager.php';
require_once 'app/auth/HashBasedAuth.php';

// Check if customer is logged in via profile hash
$auth = new HashBasedAuth($pdo);
if (!$auth->isCustomerAuthenticated()) {
    header("Location: customer_login.php");
    exit();
}

$customerData = $auth->getAuthenticatedCustomer();
$profileHashManager = new ProfileHashManager($pdo);

// Get customer information
$customerId = $customerData['customer_id'];
$customerName = $customerData['name'] ?? 'Customer';
$profileHash = $customerData['profile_hash'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'submit_payment':
            try {
                // Validate required fields
                $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
                $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
                $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
                $referenceNumber = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
                
                if (!$orderId || !$amount || !$paymentMethod) {
                    throw new Exception('Missing required payment information');
                }
                
                // CRITICAL: Verify this order belongs to the authenticated customer
                $verifyStmt = $pdo->prepare("SELECT customerId FROM orders WHERE order_id = ?");
                $verifyStmt->execute([$orderId]);
                $orderCustomer = $verifyStmt->fetchColumn();
                
                if (!$orderCustomer || $orderCustomer != $customerId) {
                    throw new Exception('Access denied - Order not found or not owned by customer');
                }
                
                // Handle file upload if present
                $proofFile = null;
                if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'public/uploads/payment_proofs/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
                    $fileName = $profileHash . '_' . $orderId . '_' . time() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadPath)) {
                        $proofFile = $uploadPath;
                    }
                }
                
                // Insert payment record with profile hash logging
                $stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, customer_id, amount, payment_method, reference_number, proof_file, payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$orderId, $customerId, $amount, $paymentMethod, $referenceNumber, $proofFile]);
                
                // Update order payment status - ONLY for this customer's order
                $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = 'pending' WHERE order_id = ? AND customerId = ?");
                $affected = $updateStmt->execute([$orderId, $customerId]);
                
                if ($updateStmt->rowCount() == 0) {
                    throw new Exception('Failed to update order - security validation failed');
                }
                
                // Log this payment action for security audit
                $profileHashManager->logProfileAccess($profileHash, 'payment_submitted', [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Payment submitted successfully']);
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Billing - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .billing-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: #6c757d;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            background-color: transparent;
            border-color: #0d6efd;
            color: #0d6efd;
        }
        
        .tab-content {
            padding: 2rem 0;
        }
        
        .invoice-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .invoice-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .invoice-card.selected {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .payment-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .payment-method-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-card:hover {
            border-color: #0d6efd;
        }
        
        .payment-method-card.selected {
            border-color: #0d6efd;
            background-color: rgba(13,110,253,0.05);
        }
        
        .loading-spinner {
            display: none;
        }
        
        .loading .loading-spinner {
            display: inline-block;
        }
        
        .loading .btn-text {
            display: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">
                <i class="fas fa-utensils me-2"></i>Esang Delicacies
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($customerName); ?></span>
                <a class="nav-link" href="customer_logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="billing-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i>Billing & Payments
            </h1>
            <a href="customer_dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="billingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" 
                        type="button" role="tab" aria-controls="invoices" aria-selected="true">
                    <i class="fas fa-file-invoice me-2"></i>My Invoices
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" 
                        type="button" role="tab" aria-controls="payment" aria-selected="false">
                    <i class="fas fa-credit-card me-2"></i>Make Payment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" 
                        type="button" role="tab" aria-controls="history" aria-selected="false">
                    <i class="fas fa-history me-2"></i>Payment History
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="billingTabContent">
            <!-- Invoices Tab -->
            <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h4 mb-0">Outstanding Invoices</h3>
                    <button class="btn btn-outline-primary" onclick="loadInvoices()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div id="invoicesContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading invoices...</p>
                    </div>
                </div>
            </div>

            <!-- Payment Tab -->
            <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                <h3 class="h4 mb-3">Make Payment</h3>
                
                <div id="selectedInvoiceInfo" class="alert alert-info" style="display: none;">
                    <h5>Selected Invoice</h5>
                    <div id="invoiceDetails"></div>
                </div>
                
                <div class="payment-form">
                    <form id="paymentForm" enctype="multipart/form-data">
                        <input type="hidden" id="paymentOrderId" name="order_id">
                        <input type="hidden" id="paymentAmount" name="amount">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="invoiceSelect" class="form-label">Select Invoice to Pay</label>
                                <select class="form-select" id="invoiceSelect" required>
                                    <option value="">Choose an invoice...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="amountDisplay" class="form-label">Amount to Pay</label>
                                <input type="text" class="form-control" id="amountDisplay" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="payment-method-card" data-method="gcash">
                                        <input type="radio" class="form-check-input me-2" name="payment_method" value="gcash" id="gcash">
                                        <label for="gcash" class="form-check-label">
                                            <strong>GCash</strong><br>
                                            <small class="text-muted">Mobile payment</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="payment-method-card" data-method="bank_transfer">
                                        <input type="radio" class="form-check-input me-2" name="payment_method" value="bank_transfer" id="bank_transfer">
                                        <label for="bank_transfer" class="form-check-label">
                                            <strong>Bank Transfer</strong><br>
                                            <small class="text-muted">Online banking</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="payment-method-card" data-method="cash">
                                        <input type="radio" class="form-check-input me-2" name="payment_method" value="cash" id="cash">
                                        <label for="cash" class="form-check-label">
                                            <strong>Cash Payment</strong><br>
                                            <small class="text-muted">Pay in person</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="referenceNumberField" class="mb-3" style="display: none;">
                            <label for="referenceNumber" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="referenceNumber" name="reference_number" 
                                   placeholder="Enter transaction reference number">
                            <div class="form-text">Required for GCash and Bank Transfer payments</div>
                        </div>
                        
                        <div id="proofUploadField" class="mb-3" style="display: none;">
                            <label for="paymentProof" class="form-label">Payment Proof</label>
                            <input type="file" class="form-control" id="paymentProof" name="payment_proof" 
                                   accept="image/*,.pdf">
                            <div class="form-text">Upload screenshot or receipt (optional but recommended)</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitPaymentBtn">
                                <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
                                <span class="btn-text">Submit Payment</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h4 mb-0">Payment History</h3>
                    <button class="btn btn-outline-primary" onclick="loadPaymentHistory()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                
                <div id="paymentHistoryContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading payment history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Modals -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalTitle">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentInvoices = [];
        let selectedInvoice = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadInvoices();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Payment method selection
            document.querySelectorAll('.payment-method-card').forEach(card => {
                card.addEventListener('click', function() {
                    const method = this.dataset.method;
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Update visual selection
                    document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Show/hide additional fields
                    updatePaymentFields(method);
                });
            });

            // Invoice selection
            document.getElementById('invoiceSelect').addEventListener('change', function() {
                const orderId = this.value;
                if (orderId) {
                    selectInvoiceForPayment(orderId);
                } else {
                    clearSelectedInvoice();
                }
            });

            // Payment form submission
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitPayment();
            });
        }

        function updatePaymentFields(method) {
            const referenceField = document.getElementById('referenceNumberField');
            const proofField = document.getElementById('proofUploadField');
            const referenceInput = document.getElementById('referenceNumber');
            
            if (method === 'gcash' || method === 'bank_transfer') {
                referenceField.style.display = 'block';
                proofField.style.display = 'block';
                referenceInput.required = true;
            } else {
                referenceField.style.display = 'none';
                proofField.style.display = 'none';
                referenceInput.required = false;
            }
        }

        function loadInvoices() {
            const container = document.getElementById('invoicesContainer');
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading invoices...</p>
                </div>
            `;

            fetch('enhanced_invoices.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.invoices) {
                        currentInvoices = data.invoices;
                        displayInvoices(data.invoices);
                        populateInvoiceSelect(data.invoices);
                    } else {
                        showEmptyInvoices();
                    }
                })
                .catch(error => {
                    console.error('Error loading invoices:', error);
                    showInvoiceError();
                });
        }

        function displayInvoices(invoices) {
            const container = document.getElementById('invoicesContainer');
            
            if (invoices.length === 0) {
                showEmptyInvoices();
                return;
            }

            let html = '';
            invoices.forEach(invoice => {
                const statusClass = getStatusBadgeClass(invoice.payment_status);
                html += `
                    <div class="invoice-card" data-order-id="${invoice.order_id}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-1">Invoice #${invoice.order_id}</h6>
                                    <small class="text-muted">${formatDate(invoice.order_date)}</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-1">${invoice.delivery_address}</div>
                                    <small class="text-muted">${invoice.items_summary}</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge ${statusClass}">${invoice.payment_status}</span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <h5 class="mb-0">₱${parseFloat(invoice.total_amount).toFixed(2)}</h5>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-primary btn-sm pay-now-btn" 
                                            onclick="selectInvoiceForPayment('${invoice.order_id}')"
                                            ${invoice.payment_status === 'completed' ? 'disabled' : ''}>
                                        ${invoice.payment_status === 'completed' ? 'Paid' : 'Pay Now'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function populateInvoiceSelect(invoices) {
            const select = document.getElementById('invoiceSelect');
            select.innerHTML = '<option value="">Choose an invoice...</option>';
            
            invoices.forEach(invoice => {
                if (invoice.payment_status !== 'completed') {
                    const option = document.createElement('option');
                    option.value = invoice.order_id;
                    option.textContent = `Invoice #${invoice.order_id} - ₱${parseFloat(invoice.total_amount).toFixed(2)}`;
                    select.appendChild(option);
                }
            });
        }

        function selectInvoiceForPayment(orderId) {
            const invoice = currentInvoices.find(inv => inv.order_id == orderId);
            if (!invoice) return;

            selectedInvoice = invoice;
            
            // Update form fields
            document.getElementById('paymentOrderId').value = invoice.order_id;
            document.getElementById('paymentAmount').value = invoice.total_amount;
            document.getElementById('amountDisplay').value = `₱${parseFloat(invoice.total_amount).toFixed(2)}`;
            document.getElementById('invoiceSelect').value = orderId;
            
            // Show invoice details
            const infoDiv = document.getElementById('selectedInvoiceInfo');
            const detailsDiv = document.getElementById('invoiceDetails');
            
            detailsDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Invoice #:</strong> ${invoice.order_id}<br>
                        <strong>Date:</strong> ${formatDate(invoice.order_date)}<br>
                        <strong>Items:</strong> ${invoice.items_summary}
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> ₱${parseFloat(invoice.total_amount).toFixed(2)}<br>
                        <strong>Status:</strong> <span class="badge ${getStatusBadgeClass(invoice.payment_status)}">${invoice.payment_status}</span><br>
                        <strong>Address:</strong> ${invoice.delivery_address}
                    </div>
                </div>
            `;
            
            infoDiv.style.display = 'block';
            
            // Switch to payment tab
            document.getElementById('payment-tab').click();
            
            // Highlight selected invoice
            document.querySelectorAll('.invoice-card').forEach(card => card.classList.remove('selected'));
            document.querySelector(`[data-order-id="${orderId}"]`)?.classList.add('selected');
        }

        function clearSelectedInvoice() {
            selectedInvoice = null;
            document.getElementById('selectedInvoiceInfo').style.display = 'none';
            document.getElementById('paymentOrderId').value = '';
            document.getElementById('paymentAmount').value = '';
            document.getElementById('amountDisplay').value = '';
            document.querySelectorAll('.invoice-card').forEach(card => card.classList.remove('selected'));
        }

        function submitPayment() {
            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitPaymentBtn');
            const formData = new FormData(form);
            formData.append('action', 'submit_payment');
            
            // Validate form
            if (!validatePaymentForm()) {
                return;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            fetch('customer_billing.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Success', data.message, 'success');
                    form.reset();
                    clearSelectedInvoice();
                    loadInvoices();
                    loadPaymentHistory();
                } else {
                    showMessage('Error', data.message || 'Payment submission failed', 'error');
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                showMessage('Error', 'Network error. Please try again.', 'error');
            })
            .finally(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            });
        }

        function validatePaymentForm() {
            const orderId = document.getElementById('paymentOrderId').value;
            const amount = document.getElementById('paymentAmount').value;
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!orderId || !amount) {
                showMessage('Error', 'Please select an invoice to pay', 'error');
                return false;
            }
            
            if (!paymentMethod) {
                showMessage('Error', 'Please select a payment method', 'error');
                return false;
            }
            
            // Check reference number for GCash and Bank Transfer
            if ((paymentMethod.value === 'gcash' || paymentMethod.value === 'bank_transfer')) {
                const refNumber = document.getElementById('referenceNumber').value.trim();
                if (!refNumber) {
                    showMessage('Error', 'Reference number is required for this payment method', 'error');
                    return false;
                }
            }
            
            return true;
        }

        function loadPaymentHistory() {
            const container = document.getElementById('paymentHistoryContainer');
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading payment history...</p>
                </div>
            `;

            fetch('payment_history.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.payments) {
                        displayPaymentHistory(data.payments);
                    } else {
                        showEmptyPaymentHistory();
                    }
                })
                .catch(error => {
                    console.error('Error loading payment history:', error);
                    showPaymentHistoryError();
                });
        }

        function displayPaymentHistory(payments) {
            const container = document.getElementById('paymentHistoryContainer');
            
            if (payments.length === 0) {
                showEmptyPaymentHistory();
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-striped">';
            html += `
                <thead>
                    <tr>
                        <th>Payment Date</th>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;

            payments.forEach(payment => {
                const statusClass = getStatusBadgeClass(payment.payment_status);
                html += `
                    <tr>
                        <td>${formatDate(payment.payment_date)}</td>
                        <td>#${payment.order_id}</td>
                        <td>₱${parseFloat(payment.amount).toFixed(2)}</td>
                        <td>${formatPaymentMethod(payment.payment_method)}</td>
                        <td>${payment.reference_number || '-'}</td>
                        <td><span class="badge ${statusClass}">${payment.payment_status}</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function showEmptyInvoices() {
            document.getElementById('invoicesContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-file-invoice text-muted"></i>
                    <h4>No Outstanding Invoices</h4>
                    <p>You don't have any pending invoices at the moment.</p>
                </div>
            `;
        }

        function showInvoiceError() {
            document.getElementById('invoicesContainer').innerHTML = `
                <div class="empty-state text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Error Loading Invoices</h4>
                    <p>Unable to load your invoices. Please try again.</p>
                    <button class="btn btn-primary" onclick="loadInvoices()">Retry</button>
                </div>
            `;
        }

        function showEmptyPaymentHistory() {
            document.getElementById('paymentHistoryContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-history text-muted"></i>
                    <h4>No Payment History</h4>
                    <p>You haven't made any payments yet.</p>
                </div>
            `;
        }

        function showPaymentHistoryError() {
            document.getElementById('paymentHistoryContainer').innerHTML = `
                <div class="empty-state text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Error Loading Payment History</h4>
                    <p>Unable to load your payment history. Please try again.</p>
                    <button class="btn btn-primary" onclick="loadPaymentHistory()">Retry</button>
                </div>
            `;
        }

        function getStatusBadgeClass(status) {
            switch(status) {
                case 'completed': return 'bg-success';
                case 'pending': return 'bg-warning text-dark';
                case 'failed': return 'bg-danger';
                case 'cancelled': return 'bg-secondary';
                default: return 'bg-secondary';
            }
        }

        function formatPaymentMethod(method) {
            switch(method) {
                case 'gcash': return 'GCash';
                case 'bank_transfer': return 'Bank Transfer';
                case 'cash': return 'Cash Payment';
                default: return method;
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function showMessage(title, message, type) {
            const modal = new bootstrap.Modal(document.getElementById('messageModal'));
            const titleEl = document.getElementById('messageModalTitle');
            const bodyEl = document.getElementById('messageModalBody');
            
            titleEl.textContent = title;
            bodyEl.innerHTML = `<div class="alert alert-${type === 'error' ? 'danger' : 'success'} mb-0">${message}</div>`;
            modal.show();
        }

        // Load payment history when history tab is clicked
        document.getElementById('history-tab').addEventListener('click', function() {
            loadPaymentHistory();
        });
    </script>
</body>
</html>