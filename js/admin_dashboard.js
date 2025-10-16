document.addEventListener('DOMContentLoaded', () => {
    // Initialize dashboard with database data
    loadDashboardData();
    
    // Default pie chart structure (will be updated with real data)
    let orderPieChart;
    
    function initializePieChart(data) {
        const orderData = {
            labels: ['Ongoing', 'Completed', 'Pending'],
            datasets: [{
                data: [data.ongoing || 0, data.completed || 0, data.pending || 0],
                backgroundColor: [
                    '#007bff', // Blue for Ongoing (Processed)
                    '#ffc107', // Yellow for Completed
                    '#28a745'  // Green for Pending
                ],
                hoverOffset: 4
            }]
        };

        // Configuration for the pie chart
        const orderConfig = {
            type: 'doughnut',
            data: orderData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        };

        // Create or update the pie chart
        if (orderPieChart) {
            orderPieChart.destroy();
        }
        orderPieChart = new Chart(
            document.getElementById('orderPieChart'),
            orderConfig
        );
    }

    // Load all dashboard data from database
    async function loadDashboardData() {
        try {
            const response = await fetch('/esang_delicacies/public/api/admin_dashboard.php?action=all_dashboard_data');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (result.success) {
                // Update summary cards
                updateSummaryCards(result);
                
                // Update order pie chart
                initializePieChart(result.order_summary);
                
                // Update menu items
                renderMenuItems(result.popular_products);
            } else {
                console.error('Failed to load dashboard data:', result.message);
                showErrorMessage('Failed to load dashboard data: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            showErrorMessage('Error loading dashboard data. Please refresh the page.');
        }
    }
    
    // Update summary cards with real data
    function updateSummaryCards(data) {
        document.getElementById('totalMenus').textContent = data.total_menus || 0;
        document.getElementById('totalCustomers').textContent = data.total_customers || 0;
        document.getElementById('totalSales').textContent = `₱${(data.total_sales || 0).toFixed(2)}`;
    }
    
    // Render menu items from database
    function renderMenuItems(products) {
        const menuGrid = document.getElementById('menuGrid');
        menuGrid.innerHTML = ''; // Clear existing items
        
        if (!products || products.length === 0) {
            menuGrid.innerHTML = '<p class="no-products">No menu items found.</p>';
            return;
        }
        
        products.forEach(item => {
            const menuItemDiv = document.createElement('div');
            menuItemDiv.classList.add('menu-item');
            
            if (item.is_popular) {
                const popularTag = document.createElement('span');
                popularTag.classList.add('popular-tag');
                popularTag.textContent = 'POPULAR choice';
                menuItemDiv.appendChild(popularTag);
            }
            
            // Add image if exists
            if (item.image) {
                const img = document.createElement('img');
                img.src = `/esang_delicacies/public/Images/products/${item.image}`;
                img.alt = item.name;
                img.onerror = function() {
                    this.style.display = 'none';
                };
                menuItemDiv.appendChild(img);
            }
            
            const name = document.createElement('h3');
            name.textContent = item.name;
            menuItemDiv.appendChild(name);
            
            const price = document.createElement('p');
            price.classList.add('price');
            price.textContent = `₱${item.price.toFixed(2)}`;
            menuItemDiv.appendChild(price);
            
            // Add order count for popular items
            if (item.total_ordered > 0) {
                const orderCount = document.createElement('small');
                orderCount.classList.add('order-count');
                orderCount.textContent = `${item.total_ordered} orders`;
                menuItemDiv.appendChild(orderCount);
            }
            
            menuGrid.appendChild(menuItemDiv);
        });
    }
    
    // Show error message
    function showErrorMessage(message) {
        // You can customize this to show a nice error notification
        console.error(message);
        // Optional: Show a toast or alert
        alert(message);
    }
    
    // Refresh data every 30 seconds
    setInterval(loadDashboardData, 30000);
});
