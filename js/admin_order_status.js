class AdminOrderStatus {
    constructor() {
        this.orders = [];
        this.selectedOrder = null;
        this.syncService = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeSyncService();
        this.loadOrders();
    }
    
    initializeSyncService() {
        if (typeof OrderSyncService !== 'undefined') {
            this.syncService = new OrderSyncService({
                pollType: 'admin',
                pollInterval: 15000, // 15 seconds for admin updates
                onStatusUpdate: (update) => this.handleStatusUpdate(update),
                onError: (error) => this.handleSyncError(error),
                onConnect: () => console.log('Admin sync service connected')
            });
        } else {
            console.warn('OrderSyncService not available, falling back to periodic refresh');
            // Fallback to periodic refresh
            setInterval(() => {
                this.loadOrders();
            }, 30000);
        }
    }

    bindEvents() {
        // Modal events
        const orderModal = document.getElementById('orderDetailModal');
        const closeBtn = document.getElementById('closeOrderModal');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
        
        if (orderModal) {
            orderModal.addEventListener('click', (e) => {
                if (e.target === orderModal) {
                    this.closeModal();
                }
            });
        }
        
        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    async loadOrders() {
        const loadingEl = document.getElementById('loading');
        const noOrdersEl = document.getElementById('noOrders');
        const tableBody = document.getElementById('ordersTableBody');
        
        if (loadingEl) loadingEl.style.display = 'block';
        if (noOrdersEl) noOrdersEl.style.display = 'none';
        
        try {
            console.log('Loading orders from API...');
            const response = await fetch('/esang_delicacies/public/api/get_pending_orders.php');
            console.log('API Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            console.log('Raw response:', responseText.substring(0, 200) + '...');
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON parsing error:', jsonError);
                throw new Error('Invalid JSON response from server');
            }
            
            if (loadingEl) loadingEl.style.display = 'none';
            
            if (result.ok && result.data) {
                this.orders = result.data;
                this.renderOrdersTable();
                
                if (result.data.length === 0) {
                    if (noOrdersEl) noOrdersEl.style.display = 'block';
                }
            } else {
                throw new Error(result.error || 'Failed to load orders');
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            if (loadingEl) loadingEl.style.display = 'none';
            if (noOrdersEl) {
                noOrdersEl.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading orders: ${error.message}</p>
                `;
                noOrdersEl.style.display = 'block';
            }
        }
    }

    renderOrdersTable() {
        const tableBody = document.getElementById('ordersTableBody');
        if (!tableBody) return;

        if (this.orders.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="no-data">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No pending orders found</p>
                    </td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = this.orders.map(order => `
            <tr class="order-row" data-order-id="${order.order_id}">
                <td class="order-id">#${order.order_id}</td>
                <td class="customer-name">${this.escapeHtml(order.customer_display)}</td>
                <td class="amount">${order.formatted_amount}</td>
                <td>
                    <span class="status-badge status-${order.status}">
                        ${order.status_display}
                    </span>
                </td>
                <td class="order-date">${order.formatted_date}</td>
                <td class="actions">
                    <div class="action-buttons">
                        <button class="btn btn-info btn-sm" onclick="adminOrderStatus.viewOrderDetails(${order.order_id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        ${this.canAdvanceStatus(order.status) ? `
                            <button class="btn btn-success btn-sm" onclick="adminOrderStatus.updateOrderStatus(${order.order_id}, 'next')">
                                <i class="fas fa-arrow-right"></i> Next
                            </button>
                        ` : ''}
                        ${order.status !== 'cancelled' ? `
                            <button class="btn btn-danger btn-sm" onclick="adminOrderStatus.updateOrderStatus(${order.order_id}, 'cancel')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    canAdvanceStatus(status) {
        const advanceable = ['pending', 'confirmed', 'preparing', 'ready_for_pickup', 'out_for_delivery'];
        return advanceable.includes(status);
    }

    async viewOrderDetails(orderId) {
        try {
            const response = await fetch(`/esang_delicacies/public/api/get_order_details.php?order_id=${orderId}`);
            const result = await response.json();
            
            if (result.ok && result.data) {
                this.showOrderModal(result.data);
            } else {
                throw new Error(result.error || 'Failed to load order details');
            }
        } catch (error) {
            console.error('Error loading order details:', error);
            alert('Failed to load order details: ' + error.message);
        }
    }

    showOrderModal(order) {
        const modal = document.getElementById('orderDetailModal');
        const content = document.getElementById('orderDetailContent');
        
        if (!modal || !content) return;

        const itemsHtml = order.items && order.items.length > 0 ? 
            order.items.map(item => `
                <tr>
                    <td>${this.escapeHtml(item.product_name || 'N/A')}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price || 0).toFixed(2)}</td>
                    <td>₱${parseFloat(item.subtotal || 0).toFixed(2)}</td>
                </tr>
            `).join('') : '<tr><td colspan="4">No items found</td></tr>';

        content.innerHTML = `
            <div class="order-detail-section">
                <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Order ID:</label>
                        <span>#${order.order_id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Customer:</label>
                        <span>${this.escapeHtml(order.customer_display)}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge status-${order.status}">${order.status_display}</span>
                    </div>
                    <div class="detail-item">
                        <label>Order Type:</label>
                        <span>${this.capitalize(order.order_type || 'delivery')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Payment Method:</label>
                        <span>${this.capitalize(order.payment_method || 'cash')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Payment Status:</label>
                        <span class="payment-status ${order.payment_status}">${this.capitalize(order.payment_status || 'pending')}</span>
                    </div>
                    <div class="detail-item">
                        <label>Total Amount:</label>
                        <span class="amount-highlight">${order.formatted_amount}</span>
                    </div>
                    <div class="detail-item">
                        <label>Order Date:</label>
                        <span>${order.formatted_date}</span>
                    </div>
                </div>
            </div>

            ${order.delivery_address ? `
                <div class="order-detail-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Delivery Address</h3>
                    <p class="address">${this.escapeHtml(order.delivery_address)}</p>
                </div>
            ` : ''}

            ${order.special_instructions ? `
                <div class="order-detail-section">
                    <h3><i class="fas fa-sticky-note"></i> Special Instructions</h3>
                    <p class="instructions">${this.escapeHtml(order.special_instructions)}</p>
                </div>
            ` : ''}

            ${order.phone || order.email ? `
                <div class="order-detail-section">
                    <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                    <div class="detail-grid">
                        ${order.phone ? `
                            <div class="detail-item">
                                <label>Phone:</label>
                                <span>${this.escapeHtml(order.phone)}</span>
                            </div>
                        ` : ''}
                        ${order.email ? `
                            <div class="detail-item">
                                <label>Email:</label>
                                <span>${this.escapeHtml(order.email)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            ` : ''}

            <div class="order-detail-section">
                <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                <div class="items-table-container">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        const modal = document.getElementById('orderDetailModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    async updateOrderStatus(orderId, action) {
        const actionText = action === 'next' ? 'advance this order to the next status' : 'cancel this order';
        
        if (!confirm(`Are you sure you want to ${actionText}?`)) {
            return;
        }

        try {
            let result;
            
            if (this.syncService) {
                // Use sync service for update (includes automatic synchronization)
                result = await this.syncService.updateOrderStatus(orderId, action);
            } else {
                // Fallback to direct API call
                const response = await fetch('/esang_delicacies/public/api/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        orderId: orderId,
                        action: action
                    })
                });
                
                result = await response.json();
            }
            
            if (result.success || result.ok) {
                if (result.order_completed) {
                    showStatusNotification('Order has been marked as completed and will be removed from the list.', 'success');
                } else {
                    showStatusNotification('Order status updated successfully.', 'success');
                }
                
                // Reload orders to reflect changes if not using sync service
                if (!this.syncService) {
                    await this.loadOrders();
                }
            } else {
                throw new Error(result.error || result.message || 'Failed to update order status');
            }
        } catch (error) {
            console.error('Error updating order status:', error);
            showStatusNotification('Failed to update order status: ' + error.message, 'error');
        }
    }
    
    handleStatusUpdate(update) {
        console.log('Admin received order update:', update);
        
        // Find and update the order in our current list
        const orderIndex = this.orders.findIndex(order => order.order_id === update.order_id);
        
        if (orderIndex >= 0) {
            // Update the order in our list
            this.orders[orderIndex].status = update.status;
            this.orders[orderIndex].status_display = update.status_display;
            
            // Re-render the table to show updates
            this.renderOrdersTable();
        } else {
            // Order not in current list, reload to get fresh data
            this.loadOrders();
        }
    }
    
    handleSyncError(error) {
        console.error('Admin sync error:', error);
        // Could show a connection status indicator
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ') : '';
    }
}

// Initialize the admin order status when the page loads
let adminOrderStatus;
document.addEventListener('DOMContentLoaded', function() {
    adminOrderStatus = new AdminOrderStatus();
});