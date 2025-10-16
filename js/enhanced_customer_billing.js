/**
 * Enhanced Customer Billing JavaScript
 * Provides smooth invoice management and payment processing
 */

document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let invoices = [];
    let paymentHistory = [];
    
    // DOM Elements
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const paymentMethods = document.querySelectorAll('.payment-method');
    const submitPaymentBtn = document.getElementById('submitPayment');
    const paymentForm = document.getElementById('paymentForm');
    
    // Initialize
    init();
    
    function init() {
        setupTabSwitching();
        setupPaymentMethods();
        loadInvoices();
        setupFormValidation();
    }
    
    // Tab switching functionality
    function setupTabSwitching() {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Update active states
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tabName).classList.add('active');
                
                // Load content for the active tab
                switch(tabName) {
                    case 'invoices':
                        loadInvoices();
                        break;
                    case 'payments':
                        // Payment form is already loaded
                        break;
                    case 'history':
                        loadPaymentHistory();
                        break;
                }
            });
        });
    }
    
    // Payment method selection
    function setupPaymentMethods() {
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                // Remove active from all methods
                paymentMethods.forEach(m => m.classList.remove('selected'));
                
                // Add active to clicked method
                this.classList.add('selected');
                
                // Update hidden input
                const methodValue = this.dataset.method;
                document.getElementById('selectedPaymentMethod').value = methodValue;
                selectedPaymentMethod = methodValue;
                
                // Show/hide relevant fields
                const referenceGroup = document.getElementById('referenceGroup');
                const proofGroup = document.getElementById('proofGroup');
                
                if (methodValue === 'gcash' || methodValue === 'bank_transfer') {
                    referenceGroup.style.display = 'block';
                    proofGroup.style.display = 'block';
                    
                    // Make reference required for these methods
                    document.getElementById('reference_number').required = true;
                } else {
                    referenceGroup.style.display = 'none';
                    proofGroup.style.display = 'none';
                    
                    // Remove required for cash payments
                    document.getElementById('reference_number').required = false;
                }
                
                validateForm();
            });
        });
    }
    
    // Load invoices from API
    async function loadInvoices() {
        const loading = document.getElementById('invoicesLoading');
        const container = document.getElementById('invoicesContainer');
        const empty = document.getElementById('invoicesEmpty');
        
        try {
            loading.style.display = 'block';
            container.style.display = 'none';
            empty.style.display = 'none';
            
            const response = await fetch('/esang_delicacies/public/api/enhanced_invoices.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    profile_hash: profileHash
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load invoices');
            }
            
            invoices = data.invoices || [];
            
            loading.style.display = 'none';
            
            if (invoices.length === 0) {
                empty.style.display = 'block';
            } else {
                renderInvoices();
                container.style.display = 'block';
            }
            
        } catch (error) {
            console.error('Error loading invoices:', error);
            loading.style.display = 'none';
            
            container.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading invoices: ${error.message}
                    <button onclick="loadInvoices()" style="margin-left: 10px; padding: 5px 10px; border: none; background: #dc3545; color: white; border-radius: 4px; cursor: pointer;">
                        Retry
                    </button>
                </div>
            `;
            container.style.display = 'block';
        }
    }
    
    // Render invoices in the container
    function renderInvoices() {
        const container = document.getElementById('invoicesContainer');
        
        container.innerHTML = invoices.map(invoice => {
            const statusClass = getStatusClass(invoice.payment_status);
            const statusText = getStatusText(invoice.payment_status);
            
            return `
                <div class="invoice-card" data-invoice-id="${invoice.order_id}">
                    <div class="invoice-header">
                        <div class="invoice-info">
                            <h3>Invoice #${String(invoice.order_id).padStart(6, '0')}</h3>
                            <div class="invoice-meta">
                                <span>Date: ${formatDate(invoice.created_at)}</span>
                                <span style="margin-left: 16px;">Items: ${invoice.items.length}</span>
                            </div>
                        </div>
                        <span class="invoice-status ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="invoice-details">
                        <p><strong>Delivery Address:</strong> ${invoice.delivery_address || 'N/A'}</p>
                        <p><strong>Payment Method:</strong> ${invoice.payment_method || 'N/A'}</p>
                    </div>
                    
                    <div class="invoice-items">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>QTY</th>
                                    <th>ORDER</th>
                                    <th>UNIT PRICE</th>
                                    <th>AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${invoice.items.map(item => `
                                    <tr>
                                        <td>${item.quantity}</td>
                                        <td>${item.product_name}</td>
                                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                        <td>₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="invoice-total">
                        <div class="total-amount">
                            Total: ₱${parseFloat(invoice.total_amount).toFixed(2)}
                        </div>
                    </div>
                    
                    ${invoice.payment_status !== 'completed' ? `
                        <div style="margin-top: 16px; text-align: center;">
                            <button class="btn-primary" onclick="selectInvoiceForPayment(${invoice.order_id})">
                                <i class="fas fa-credit-card"></i> Pay Now
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
        
        // Add click handlers for invoice cards
        addInvoiceClickHandlers();
    }
    
    // Add click handlers to invoice cards
    function addInvoiceClickHandlers() {
        document.querySelectorAll('.invoice-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on the pay button
                if (e.target.closest('button')) return;
                
                // Toggle selection
                const isSelected = this.classList.contains('selected');
                
                // Remove selection from all cards
                document.querySelectorAll('.invoice-card').forEach(c => c.classList.remove('selected'));
                
                if (!isSelected) {
                    this.classList.add('selected');
                    const invoiceId = parseInt(this.dataset.invoiceId);
                    selectInvoice(invoiceId);
                } else {
                    selectedInvoice = null;
                    updateSelectedInvoiceInfo();
                }
            });
        });
    }
    
    // Select an invoice for payment
    function selectInvoice(invoiceId) {
        selectedInvoice = invoices.find(inv => inv.order_id === invoiceId);
        updateSelectedInvoiceInfo();
    }
    
    // Update selected invoice information in payment form
    function updateSelectedInvoiceInfo() {
        const infoDiv = document.getElementById('selectedInvoiceInfo');
        const detailsP = document.getElementById('selectedInvoiceDetails');
        const amountInput = document.getElementById('amount');
        const orderIdInput = document.getElementById('paymentOrderId');
        
        if (selectedInvoice) {
            detailsP.innerHTML = `
                <strong>Invoice #${String(selectedInvoice.order_id).padStart(6, '0')}</strong><br>
                Amount: ₱${parseFloat(selectedInvoice.total_amount).toFixed(2)}<br>
                Date: ${formatDate(selectedInvoice.created_at)}
            `;
            infoDiv.style.display = 'block';
            amountInput.value = parseFloat(selectedInvoice.total_amount).toFixed(2);
            orderIdInput.value = selectedInvoice.order_id;
        } else {
            infoDiv.style.display = 'none';
            amountInput.value = '';
            orderIdInput.value = '';
        }
        
        validateForm();
    }
    
    // Global function for pay now button
    window.selectInvoiceForPayment = function(invoiceId) {
        selectInvoice(invoiceId);
        
        // Switch to payments tab
        document.querySelector('[data-tab="payments"]').click();
        
        // Scroll to payment form
        document.querySelector('.payment-form').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    };
    
    // Load payment history
    async function loadPaymentHistory() {
        const loading = document.getElementById('historyLoading');
        const container = document.getElementById('historyContainer');
        const empty = document.getElementById('historyEmpty');
        
        try {
            loading.style.display = 'block';
            container.style.display = 'none';
            empty.style.display = 'none';
            
            const response = await fetch('/esang_delicacies/public/api/payment_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    profile_hash: profileHash
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load payment history');
            }
            
            paymentHistory = data.payments || [];
            
            loading.style.display = 'none';
            
            if (paymentHistory.length === 0) {
                empty.style.display = 'block';
            } else {
                renderPaymentHistory();
                container.style.display = 'block';
            }
            
        } catch (error) {
            console.error('Error loading payment history:', error);
            loading.style.display = 'none';
            
            container.innerHTML = `
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading payment history: ${error.message}
                </div>
            `;
            container.style.display = 'block';
        }
    }
    
    // Render payment history
    function renderPaymentHistory() {
        const container = document.getElementById('historyContainer');
        
        container.innerHTML = `
            <div class="payment-form">
                <h2><i class="fas fa-history"></i> Payment History</h2>
                <div class="payment-history-list">
                    ${paymentHistory.map(payment => `
                        <div class="payment-history-item" style="border-bottom: 1px solid #f0f0f0; padding: 16px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #333;">Invoice #${String(payment.order_id).padStart(6, '0')}</h4>
                                    <p style="margin: 4px 0; color: #6c757d;">
                                        ${formatDate(payment.payment_date)} • ${payment.payment_method}
                                        ${payment.reference_number ? ` • Ref: ${payment.reference_number}` : ''}
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 18px; font-weight: 600; color: #333;">
                                        ₱${parseFloat(payment.amount).toFixed(2)}
                                    </div>
                                    <div class="payment-status ${getStatusClass(payment.payment_status)}">
                                        ${getStatusText(payment.payment_status)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Form validation
    function setupFormValidation() {
        const form = document.getElementById('paymentForm');
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            input.addEventListener('input', validateForm);
        });
        
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showAlert('Please fill all required fields', 'error');
            } else {
                // Show loading state
                submitPaymentBtn.disabled = true;
                submitPaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
    }
    
    // Validate payment form
    function validateForm() {
        const hasInvoice = selectedInvoice !== null;
        const hasPaymentMethod = selectedPaymentMethod !== null;
        const amount = document.getElementById('amount').value;
        const referenceRequired = (selectedPaymentMethod === 'gcash' || selectedPaymentMethod === 'bank_transfer');
        const hasReference = !referenceRequired || document.getElementById('reference_number').value.trim() !== '';
        
        const isValid = hasInvoice && hasPaymentMethod && amount > 0 && hasReference;
        
        submitPaymentBtn.disabled = !isValid;
        
        return isValid;
    }
    
    // Utility functions
    function getStatusClass(status) {
        switch(status) {
            case 'completed':
            case 'paid':
                return 'status-paid';
            case 'pending':
                return 'status-pending';
            case 'overdue':
                return 'status-overdue';
            default:
                return 'status-pending';
        }
    }
    
    function getStatusText(status) {
        switch(status) {
            case 'completed':
            case 'paid':
                return 'Paid';
            case 'pending':
                return 'Pending Payment';
            case 'overdue':
                return 'Overdue';
            default:
                return 'Pending Payment';
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
    
    function showAlert(message, type) {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
        `;
        
        // Insert at top of content
        const content = document.querySelector('.billing-enhanced');
        content.insertBefore(alert, content.firstChild);
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }
    
    // Auto-refresh invoices every 30 seconds if on invoices tab
    setInterval(() => {
        const invoicesTab = document.getElementById('invoices');
        if (invoicesTab.classList.contains('active')) {
            loadInvoices();
        }
    }, 30000);
    
    // Expose functions globally
    window.loadInvoices = loadInvoices;
    window.loadPaymentHistory = loadPaymentHistory;
});