/**
 * Modern Inventory Management System
 * Enhanced with better UX, loading states, animations, and error handling
 */

class InventoryManager {
    constructor() {
        this.currentView = 'inventory';
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.searchTerm = '';
        this.categoryFilter = 'all';
        this.statusFilter = 'all';
        
        // Data will be loaded from database
        this.inventoryData = [];
        this.stats = {};
        this.categories = [];
        
        this.dailyData = [
            { date: '2025-01-04', itemName: 'Biko', stock: 50, remaining: 40, sold: 10, status: 'high' },
            { date: '2025-01-04', itemName: 'Cassava Cake', stock: 30, remaining: 25, sold: 5, status: 'high' },
            { date: '2025-01-04', itemName: 'Carbonara', stock: 100, remaining: 90, sold: 10, status: 'high' },
            { date: '2025-01-04', itemName: 'Maja Blanca', stock: 40, remaining: 35, sold: 5, status: 'high' },
            { date: '2025-01-04', itemName: 'Turon Bites', stock: 15, remaining: 2, sold: 13, status: 'low' },
            { date: '2025-01-04', itemName: 'Puto', stock: 60, remaining: 10, sold: 50, status: 'low' }
        ];

        
        this.weeklyData = [
            { week: 'Jan 1 - Jan 7', itemName: 'Turon Bites', startingStock: 55, endingStock: 25, revenue: 1650 },
            { week: 'Jan 8 - Jan 14', itemName: 'Biko', startingStock: 60, endingStock: 30, revenue: 1350 },
            { week: 'Jan 15 - Jan 21', itemName: 'Carbonara', startingStock: 40, endingStock: 15, revenue: 2125 },
            { week: 'Jan 22 - Jan 28', itemName: 'Maja Blanca', startingStock: 35, endingStock: 10, revenue: 1375 }
        ];

        this.monthlyData = [
            { week: '1', totalSales: 1950.00, totalSold: 30, stocksLeft: 0, bestSeller: 'Turon Bites' },
            { week: '2', totalSales: 2550.00, totalSold: 35, stocksLeft: 5, bestSeller: 'Carbonara' },
            { week: '3', totalSales: 6500.00, totalSold: 100, stocksLeft: 8, bestSeller: 'Biko' },
            { week: '4', totalSales: 2500.00, totalSold: 40, stocksLeft: 12, bestSeller: 'Maja Blanca' }
        ];

        this.yearlyData = [
            { month: 'January', totalSales: 140000.00, bestSeller: 'Turon Bites' },
            { month: 'February', totalSales: 125000.00, bestSeller: 'Carbonara' },
            { month: 'March', totalSales: 165000.00, bestSeller: 'Biko' },
            { month: 'April', totalSales: 145000.00, bestSeller: 'Palabok' }
        ];
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateCurrentDate();
        this.loadInventoryData(); // Load data from database first
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            if (this.currentView === 'inventory') {
                this.loadInventoryData();
            }
        }, 30000);
    }
    
    async loadInventoryData() {
        try {
            const params = new URLSearchParams({
                search: this.searchTerm,
                category: this.categoryFilter,
                status: this.statusFilter,
                page: this.currentPage,
                limit: this.itemsPerPage
            });
            
            const response = await fetch(`/esang_delicacies/public/api/get_inventory_products.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.inventoryData = result.data || [];
                this.stats = result.stats || {};
                this.categories = result.categories || [];
                
                // Update UI
                this.updateDashboardStats();
                this.populateCategories();
                
                if (this.currentView === 'inventory') {
                    this.renderInventoryTable();
                }
            } else {
                throw new Error(result.message || 'Failed to load inventory data');
            }
        } catch (error) {
            console.error('Error loading inventory data:', error);
            console.log('Full error details:', error);
            
            // Try to get more error details
            let errorMessage = error.message;
            if (error.message.includes('Unexpected token')) {
                errorMessage = 'Server returned HTML instead of JSON. Check PHP errors in server logs.';
            }
            
            this.showNotification('Failed to load inventory data: ' + errorMessage, 'error');
            
            // Show error in table
            const tbody = document.getElementById('inventoryTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr class="error-row">
                        <td colspan="8">
                            <div class="error-content">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Error loading data: ${errorMessage}</span>
                                <button class="btn btn-primary btn-sm" onclick="inventoryManager.testDatabaseConnection()">
                                    <i class="fas fa-database"></i> Test DB
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="inventoryManager.loadInventoryData()">
                                    <i class="fas fa-refresh"></i> Retry
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
    }
    
    populateCategories() {
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter && this.categories.length > 0) {
            // Keep the "All Categories" option and add database categories
            const currentValue = categoryFilter.value;
            categoryFilter.innerHTML = '<option value="all">All Categories</option>';
            
            this.categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categoryFilter.appendChild(option);
            });
            
            // Restore the selected value
            categoryFilter.value = currentValue;
        }
    }
    
    async testDatabaseConnection() {
        try {
            const response = await fetch('/esang_delicacies/public/api/test_db.php');
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`Database test successful! Found ${result.product_count} products.`, 'success');
                console.log('Database test result:', result);
            } else {
                this.showNotification('Database test failed: ' + result.message, 'error');
                console.error('Database test failed:', result);
            }
        } catch (error) {
            console.error('Database test error:', error);
            this.showNotification('Database test error: ' + error.message, 'error');
        }
    }
    
    async debugInventory() {
        try {
            const response = await fetch('/esang_delicacies/public/api/debug_inventory.php');
            const text = await response.text();
            console.log('Debug API raw response:', text);
            
            const result = JSON.parse(text);
            console.log('Debug inventory result:', result);
            
            if (result.success) {
                const info = result.debug_info;
                this.showNotification(
                    `Debug: Products(${info.products_count}) Inventory(${info.inventory_count})`, 
                    'info'
                );
            } else {
                this.showNotification('Debug failed: ' + result.error, 'error');
            }
        } catch (error) {
            console.error('Debug error:', error);
            this.showNotification('Debug error: ' + error.message, 'error');
        }
    }
    
    bindEvents() {
        // Tab navigation
        document.getElementById('inventoryBtn').addEventListener('click', () => this.switchView('inventory'));
        document.getElementById('dailyBtn').addEventListener('click', () => this.switchView('daily'));
        document.getElementById('weeklyBtn').addEventListener('click', () => this.switchView('weekly'));
        document.getElementById('monthlyBtn').addEventListener('click', () => this.switchView('monthly'));
        document.getElementById('yearlyBtn').addEventListener('click', () => this.switchView('yearly'));
        
        // Search and filters
        document.getElementById('searchBar').addEventListener('input', (e) => {
            this.searchTerm = e.target.value;
            this.debounce(() => this.applyFilters(), 300)();
        });
        
        document.getElementById('categoryFilter').addEventListener('change', (e) => {
            this.categoryFilter = e.target.value;
            this.applyFilters();
        });
        
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.statusFilter = e.target.value;
            this.applyFilters();
        });
        
        // Action buttons
        document.getElementById('refreshBtn').addEventListener('click', () => this.refreshData());
        document.getElementById('exportBtn').addEventListener('click', () => this.exportData());
        
        // Modal events
        this.bindModalEvents();
        
        // Pagination
        document.getElementById('prevPage').addEventListener('click', () => this.previousPage());
        document.getElementById('nextPage').addEventListener('click', () => this.nextPage());
    }
    
    bindModalEvents() {
        // Stock modal
        const stockModal = document.getElementById('stockModal');
        const closeStockModal = document.getElementById('closeStockModal');
        const cancelStock = document.getElementById('cancelStock');
        
        [closeStockModal, cancelStock].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => this.closeModal('stockModal'));
            }
        });
        
        
        // Form submissions
        document.getElementById('stockForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateStock();
        });
        
        // Close modals on overlay click
        if (stockModal) {
            stockModal.addEventListener('click', (e) => {
                if (e.target === stockModal) {
                    this.closeModal(stockModal.id);
                }
            });
        }
        
        // ESC key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }

    
    // Core functionality methods
    switchView(view) {
        console.log(`Switching to ${view} view`);
        
        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.getElementById(`${view}Btn`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
        
        // Update active section
        document.querySelectorAll('.data-section').forEach(section => {
            section.classList.remove('active');
        });
        
        const activeSection = document.getElementById(`${view}Table`);
        if (activeSection) {
            activeSection.classList.add('active');
        }
        
        this.currentView = view;
        this.currentPage = 1; // Reset pagination for inventory view
        this.renderCurrentView();
    }
    
    renderCurrentView() {
        this.showLoading();
        
        setTimeout(() => {
            switch (this.currentView) {
                case 'inventory':
                    this.renderInventoryTable();
                    break;
                case 'daily':
                    this.renderDailyTable();
                    break;
                case 'weekly':
                    this.renderWeeklyTable();
                    break;
                case 'monthly':
                    this.renderMonthlyTable();
                    break;
                case 'yearly':
                    this.renderYearlyTable();
                    break;
            }
            this.hideLoading();
        }, 500); // Simulate loading time
    }
    
    showLoading() {
        const currentSection = document.getElementById(`${this.currentView}Table`);
        const tbody = currentSection.querySelector('tbody');
        if (tbody) {
            // Get the number of columns from the table header
            const thead = currentSection.querySelector('thead tr');
            const colCount = thead ? thead.children.length : 8;
            
            tbody.innerHTML = `
                <tr class="loading-row">
                    <td colspan="${colCount}">
                        <div class="loading-content">
                            <div class="loading-spinner"></div>
                            <span>Loading ${this.currentView} data...</span>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    hideLoading() {
        // Loading will be replaced by actual data rendering
    }
    
    updateDashboardStats() {
        // Use stats from database API response
        const totalProducts = this.stats.total_products || 0;
        const lowStock = this.stats.low_stock || 0;
        const outOfStock = this.stats.out_of_stock || 0;
        const totalValue = this.stats.total_value || 0;
        
        this.animateCounter('totalProducts', totalProducts);
        this.animateCounter('lowStock', lowStock);
        this.animateCounter('outOfStock', outOfStock);
        this.animateCounter('totalValue', totalValue, '₱');
    }
    
    animateCounter(elementId, targetValue, prefix = '') {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const startValue = 0;
        const duration = 1000;
        const startTime = performance.now();
        
        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
            
            if (prefix === '₱') {
                element.textContent = `${prefix}${currentValue.toLocaleString()}`;
            } else {
                element.textContent = currentValue.toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        };
        
        requestAnimationFrame(updateCounter);
    }
    
    updateCurrentDate() {
        const now = new Date();
        const dateElements = {
            currentDate: now.toLocaleDateString(),
            currentYear: now.getFullYear()
        };
        
        Object.entries(dateElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }

    
    // Table rendering methods
    renderInventoryTable() {
        const tbody = document.getElementById('inventoryTableBody');
        const filteredData = this.getFilteredInventoryData();
        const paginatedData = this.getPaginatedData(filteredData);
        
        tbody.innerHTML = paginatedData.map(item => `
            <tr class="inventory-row" data-id="${item.product_id}">
                <td><span class="product-id">#${item.product_id}</span></td>
                <td>
                    <div class="product-info">
                        <span class="product-name">${this.escapeHtml(item.name)}</span>
                        <small class="product-category">${item.category}</small>
                    </div>
                </td>
                <td>${item.category}</td>
                <td class="price-cell">₱${item.price.toFixed(2)}</td>
                <td class="stock-cell">
                    <span class="stock-number">${item.stock}</span>
                    <small>units</small>
                </td>
                <td class="min-stock-cell">${item.min_stock}</td>
                <td>
                    <span class="status-badge ${item.status}">
                        ${this.getStatusIcon(item.status)} ${this.formatStatus(item.status)}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-primary" onclick="inventoryManager.editStock(${item.product_id})" title="Update Stock">
                            <i class="fas fa-edit"></i> Update
                        </button>
                        <button class="btn btn-sm btn-info" onclick="inventoryManager.viewProduct(${item.product_id})" title="View Details">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        
        this.updateInventoryCount(filteredData.length);
        this.updatePagination(filteredData.length);
        this.bindSortingEvents();
        
        // Show pagination only for inventory view
        const paginationContainer = document.querySelector('.pagination-container');
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
        }
    }
    
    renderDailyTable() {
        const tbody = document.getElementById('dailyTableBody');
        tbody.innerHTML = this.dailyData.map(item => `
            <tr>
                <td>${item.date}</td>
                <td>${this.escapeHtml(item.itemName)}</td>
                <td>${item.stock}</td>
                <td>${item.remaining}</td>
                <td>${item.sold}</td>
                <td>
                    <span class="status-badge ${item.status}">
                        ${this.getStatusIcon(item.status)} ${this.formatStatus(item.status)}
                    </span>
                </td>
            </tr>
        `).join('');
        
        // Hide pagination for other views
        this.hidePagination();
    }
    
    renderWeeklyTable() {
        const tbody = document.getElementById('weeklyTableBody');
        tbody.innerHTML = this.weeklyData.map(item => `
            <tr>
                <td>${item.week}</td>
                <td>${this.escapeHtml(item.itemName)}</td>
                <td>${item.startingStock}</td>
                <td>${item.endingStock}</td>
                <td class="revenue-cell">₱${item.revenue.toLocaleString()}</td>
            </tr>
        `).join('');
        
        this.hidePagination();
    }
    
    renderMonthlyTable() {
        const tbody = document.getElementById('monthlyTableBody');
        tbody.innerHTML = this.monthlyData.map(item => `
            <tr>
                <td>Week ${item.week}</td>
                <td class="sales-cell">₱${item.totalSales.toLocaleString()}</td>
                <td>${item.totalSold}</td>
                <td>${item.stocksLeft}</td>
                <td class="best-seller">${this.escapeHtml(item.bestSeller)}</td>
            </tr>
        `).join('');
        
        this.hidePagination();
    }
    
    renderYearlyTable() {
        const tbody = document.getElementById('yearlyTableBody');
        tbody.innerHTML = this.yearlyData.map(item => `
            <tr>
                <td>${item.month}</td>
                <td class="sales-cell">₱${item.totalSales.toLocaleString()}</td>
                <td class="best-seller">${this.escapeHtml(item.bestSeller)}</td>
            </tr>
        `).join('');
        
        this.hidePagination();
    }
    
    hidePagination() {
        const paginationContainer = document.querySelector('.pagination-container');
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
    }

    
    // Filtering and pagination methods
    getFilteredInventoryData() {
        return this.inventoryData.filter(item => {
            const matchesSearch = !this.searchTerm || 
                item.name.toLowerCase().includes(this.searchTerm.toLowerCase());
            const matchesCategory = this.categoryFilter === 'all' || 
                item.category === this.categoryFilter;
            const matchesStatus = this.statusFilter === 'all' || 
                item.status === this.statusFilter;
            
            return matchesSearch && matchesCategory && matchesStatus;
        });
    }
    
    getPaginatedData(data) {
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        return data.slice(startIndex, endIndex);
    }
    
    applyFilters() {
        this.currentPage = 1; // Reset to first page when filtering
        if (this.currentView === 'inventory') {
            this.loadInventoryData(); // Reload from database with new filters
        } else {
            this.renderCurrentView();
        }
    }
    
    // Utility methods
    formatCategory(category) {
        return category.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    formatStatus(status) {
        const statusMap = {
            'high': 'In Stock',
            'medium': 'Medium Stock',
            'low': 'Low Stock',
            'out': 'Out of Stock'
        };
        return statusMap[status] || status;
    }
    
    getStatusIcon(status) {
        const iconMap = {
            'high': '<i class="fas fa-check-circle"></i>',
            'medium': '<i class="fas fa-exclamation-circle"></i>',
            'low': '<i class="fas fa-exclamation-triangle"></i>',
            'out': '<i class="fas fa-times-circle"></i>'
        };
        return iconMap[status] || '';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Modal methods
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    closeAllModals() {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            this.closeModal(modal.id);
        });
    }
    
    // Action methods
    editStock(productId) {
        const product = this.inventoryData.find(p => p.product_id === productId);
        if (!product) return;
        
        document.getElementById('productId').value = product.product_id;
        document.getElementById('productName').textContent = product.name;
        document.getElementById('currentStock').value = product.stock;
        document.getElementById('minStockLevel').value = product.min_stock;
        
        this.showModal('stockModal');
    }
    
    async updateStock() {
        const productId = parseInt(document.getElementById('productId').value);
        const stockQuantity = parseInt(document.getElementById('currentStock').value);
        const minStockLevel = parseInt(document.getElementById('minStockLevel').value);
        const notes = document.getElementById('stockNote') ? document.getElementById('stockNote').value : '';
        
        try {
            const response = await fetch('/esang_delicacies/public/api/update_product_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    stock_quantity: stockQuantity,
                    min_stock_level: minStockLevel,
                    notes: notes
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Stock updated successfully!', 'success');
                this.closeModal('stockModal');
                
                // Reload inventory data to reflect changes
                await this.loadInventoryData();
            } else {
                throw new Error(result.message || 'Failed to update stock');
            }
        } catch (error) {
            console.error('Error updating stock:', error);
            this.showNotification('Failed to update stock: ' + error.message, 'error');
        }
    }
    
    
    viewProduct(productId) {
        const product = this.inventoryData.find(p => p.product_id === productId);
        if (!product) return;
        
        // Show product details in a simple alert for now
        // In a real application, this would open a detailed view modal
        const details = `Product Details:\n\n` +
            `Name: ${product.name}\n` +
            `Category: ${product.category}\n` +
            `Price: ₱${product.price.toFixed(2)}\n` +
            `Current Stock: ${product.stock} units\n` +
            `Minimum Stock: ${product.min_stock} units\n` +
            `Status: ${this.formatStatus(product.status)}`;
        
        alert(details);
    }
    
    refreshData() {
        this.showNotification('Refreshing data...', 'info');
        if (this.currentView === 'inventory') {
            this.loadInventoryData();
        } else {
            this.renderCurrentView();
        }
    }
    
    exportData() {
        // Simple CSV export
        const csvData = [
            ['ID', 'Name', 'Category', 'Price', 'Stock', 'Min Stock', 'Status'],
            ...this.inventoryData.map(item => [
                item.id, item.name, item.category, item.price, item.stock, item.minStock, item.status
            ])
        ];
        
        const csvContent = csvData.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `inventory-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        
        URL.revokeObjectURL(url);
        this.showNotification('Data exported successfully!', 'success');
    }
    
    // Pagination methods
    updateInventoryCount(total) {
        const element = document.getElementById('inventoryCount');
        if (element) element.textContent = total;
    }
    
    updatePagination(totalItems) {
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
        const endItem = Math.min(this.currentPage * this.itemsPerPage, totalItems);
        
        // Update pagination info
        document.getElementById('startItem').textContent = totalItems > 0 ? startItem : 0;
        document.getElementById('endItem').textContent = endItem;
        document.getElementById('totalItems').textContent = totalItems;
        
        // Update pagination controls
        document.getElementById('prevPage').disabled = this.currentPage === 1;
        document.getElementById('nextPage').disabled = this.currentPage === totalPages || totalPages === 0;
        
        // Generate page numbers
        this.generatePageNumbers(totalPages);
    }
    
    generatePageNumbers(totalPages) {
        const pageNumbers = document.getElementById('pageNumbers');
        pageNumbers.innerHTML = '';
        
        for (let i = 1; i <= Math.min(totalPages, 5); i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-number ${i === this.currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => this.goToPage(i));
            pageNumbers.appendChild(pageBtn);
        }
    }
    
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.renderCurrentView();
        }
    }
    
    nextPage() {
        const totalPages = Math.ceil(this.getFilteredInventoryData().length / this.itemsPerPage);
        if (this.currentPage < totalPages) {
            this.currentPage++;
            this.renderCurrentView();
        }
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.renderCurrentView();
    }
    
    // Sorting methods
    bindSortingEvents() {
        document.querySelectorAll('.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const column = th.dataset.sort;
                this.sortData(column);
            });
        });
    }
    
    sortData(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        
        this.inventoryData.sort((a, b) => {
            let aValue = a[column];
            let bValue = b[column];
            
            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }
            
            if (this.sortDirection === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        this.updateSortingUI(column);
        this.renderCurrentView();
    }
    
    updateSortingUI(column) {
        document.querySelectorAll('.sortable').forEach(th => {
            th.classList.remove('sorted');
            const icon = th.querySelector('i');
            icon.className = 'fas fa-sort';
        });
        
        const activeHeader = document.querySelector(`[data-sort="${column}"]`);
        if (activeHeader) {
            activeHeader.classList.add('sorted');
            const icon = activeHeader.querySelector('i');
            icon.className = this.sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        }
    }
    
    // Notification system
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Initialize the inventory manager when the page loads
let inventoryManager;
document.addEventListener('DOMContentLoaded', function() {
    inventoryManager = new InventoryManager();
});
