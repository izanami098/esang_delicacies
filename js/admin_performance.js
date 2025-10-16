// Global variables for data
let currentPeriod = 'monthly';
let chartInstance = null;

// Function to load analytics data from new API
async function loadAnalyticsData(period = 'monthly') {
    try {
        const response = await fetch(`/esang_delicacies/public/api/admin_analytics.php?action=stats&period=${period}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateStatistics(result.stats);
            return true;
        } else {
            console.error('Failed to load analytics:', result.message);
            showNotification('Failed to load analytics data: ' + result.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
        showNotification('Error loading analytics data: ' + error.message, 'error');
        return false;
    }
}

// Function to load transaction data from new API
async function loadTransactionData(period = 'monthly') {
    try {
        const response = await fetch(`/esang_delicacies/public/api/admin_analytics.php?action=transactions&period=${period}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateTable(result.transactions);
            return true;
        } else {
            console.error('Failed to load transactions:', result.message);
            showNotification('Failed to load transaction data: ' + result.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
        showNotification('Error loading transaction data: ' + error.message, 'error');
        return false;
    }
}

// Function to load chart data
async function loadChartData(period = 'monthly') {
    try {
        const response = await fetch(`/esang_delicacies/public/api/admin_analytics.php?action=chart_data&period=${period}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            updateChart(result.chart_data, period);
            return true;
        } else {
            console.error('Failed to load chart data:', result.message);
            showNotification('Failed to load chart data: ' + result.message, 'error');
            return false;
        }
    } catch (error) {
        console.error('Error loading chart data:', error);
        showNotification('Error loading chart data: ' + error.message, 'error');
        return false;
    }
}

// Function to update statistics cards
function updateStatistics(stats) {
    if (!stats) return;
    
    document.getElementById('total-orders').textContent = stats.total_orders || 0;
    document.getElementById('total-revenue').textContent = '₱' + (stats.total_revenue || 0).toFixed(2);
    document.getElementById('avg-order-value').textContent = '₱' + (stats.avg_order_value || 0).toFixed(2);
    document.getElementById('unique-customers').textContent = stats.unique_customers || 0;
}

// Function to create or update the chart
function updateChart(chartData, period) {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Destroy the old chart instance if it exists
    if (chartInstance) {
        chartInstance.destroy();
    }

    if (!chartData || chartData.length === 0) {
        // Show empty chart message
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.font = "16px Arial";
        ctx.textAlign = "center";
        ctx.fillStyle = "#666";
        ctx.fillText("No data available", ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }

    const labels = chartData.map(item => item.label);
    const revenues = chartData.map(item => parseFloat(item.revenue));
    
    let chartType = 'line';
    let backgroundColor = 'rgba(59, 130, 246, 0.2)';
    let borderColor = 'rgba(59, 130, 246, 1)';
    
    if (period === 'weekly' || period === 'yearly') {
        chartType = 'bar';
        backgroundColor = period === 'yearly' ? 'rgba(16, 185, 129, 0.8)' : 'rgba(59, 130, 246, 0.8)';
        borderColor = period === 'yearly' ? 'rgba(16, 185, 129, 1)' : 'rgba(59, 130, 246, 1)';
    }
    
    // Create a new chart instance
    chartInstance = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: `${period.charAt(0).toUpperCase() + period.slice(1)} Revenue`,
                data: revenues,
                backgroundColor: backgroundColor,
                borderColor: borderColor,
                borderWidth: 2,
                fill: chartType === 'line',
                tension: 0.4,
                borderRadius: chartType === 'bar' ? 8 : 0,
                barPercentage: chartType === 'bar' ? 0.6 : undefined
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₱' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Function to populate the transaction table
function updateTable(transactions) {
    const tableBody = document.getElementById('transaction-table-body');
    tableBody.innerHTML = ''; // Clear existing rows

    if (!transactions || transactions.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="4" class="text-center" style="padding: 20px; color: #666;">
                No transaction data available for the selected period
            </td>
        `;
        tableBody.appendChild(row);
        return;
    }

    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${transaction.username || 'Guest Customer'}</td>
            <td>${transaction.payment_type || 'Cash'}</td>
            <td>${transaction.formatted_date || transaction.date}</td>
            <td class="amount">₱${parseFloat(transaction.amount || 0).toFixed(2)}</td>
        `;
        tableBody.appendChild(row);
    });
}

// Function to handle button clicks and update the UI
async function handleNavClick(period) {
    currentPeriod = period;
    
    // Remove 'active' class from all buttons
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));

    // Add 'active' class to the clicked button
    const selectedButton = document.getElementById(`${period}-btn`);
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // Load data for the selected period
    await Promise.all([
        loadAnalyticsData(period),
        loadTransactionData(period),
        loadChartData(period)
    ]);
}

// Function to show notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        max-width: 350px;
        word-wrap: break-word;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        ${type === 'success' ? 'background-color: #10b981;' : ''}
        ${type === 'error' ? 'background-color: #ef4444;' : ''}
        ${type === 'info' ? 'background-color: #3b82f6;' : ''}
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 4000);
}

// Function to add sample transaction data
async function addSampleData() {
    if (!confirm('This will add sample transaction data to the database. Continue?')) {
        return;
    }
    
    try {
        const response = await fetch('/esang_delicacies/public/api/admin_analytics.php?action=add_sample_data', {
            method: 'POST',
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Sample data added successfully!', 'success');
            // Reload data to show the new transactions
            await handleNavClick(currentPeriod);
        } else {
            showNotification('Failed to add sample data: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error adding sample data:', error);
        showNotification('Error adding sample data', 'error');
    }
}

// Event listeners for navigation buttons
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('weekly-btn').addEventListener('click', () => handleNavClick('weekly'));
    document.getElementById('monthly-btn').addEventListener('click', () => handleNavClick('monthly'));
    document.getElementById('yearly-btn').addEventListener('click', () => handleNavClick('yearly'));
    document.getElementById('add-sample-btn').addEventListener('click', addSampleData);
    
    // Initial setup - load monthly data by default
    handleNavClick('monthly');
});

// Auto-refresh data every 2 minutes
setInterval(() => {
    handleNavClick(currentPeriod);
}, 120000);