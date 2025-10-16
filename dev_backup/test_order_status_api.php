<?php
session_start();

// Mock customer session for testing
if (!isset($_SESSION['customerId'])) {
    $_SESSION['customerId'] = 20; // Use an existing customer ID from your debug report
    $_SESSION['role'] = 'CUSTOMER';
    $_SESSION['user_name'] = 'Test Customer';
}

$statuses = ['pending', 'ongoing', 'completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status API Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        .orders-container { margin-top: 10px; }
        .order-item { background: #f5f5f5; padding: 10px; margin: 5px 0; border-radius: 3px; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 3px; }
        button:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Order Status API Test</h1>
    <p><strong>Current Customer ID:</strong> <?php echo $_SESSION['customerId']; ?> (<?php echo $_SESSION['user_name']; ?>)</p>
    
    <?php foreach ($statuses as $status): ?>
    <div class="status-section">
        <h2><?php echo ucfirst($status); ?> Orders</h2>
        <button onclick="loadOrders('<?php echo $status; ?>')">Load <?php echo ucfirst($status); ?> Orders</button>
        <button onclick="showRawResponse('<?php echo $status; ?>')">Show Raw API Response</button>
        <div id="<?php echo $status; ?>-orders" class="orders-container">
            <p>Click the button to load orders...</p>
        </div>
        <pre id="<?php echo $status; ?>-raw" style="display: none;"></pre>
    </div>
    <?php endforeach; ?>
    
    <script>
        async function loadOrders(status) {
            const container = document.getElementById(status + '-orders');
            const rawContainer = document.getElementById(status + '-raw');
            
            container.innerHTML = '<p>Loading...</p>';
            rawContainer.style.display = 'none';
            
            try {
                const apiBase = window.location.protocol + '//' + window.location.host + '/esang_delicacies';
                const url = `${apiBase}/public/api/get_customer_orders.php?status=${status}`;
                console.log(`Fetching: ${url}`);
                
                const response = await fetch(url);
                const result = await response.json();
                
                console.log(`${status} orders result:`, result);
                
                if (result.success) {
                    if (result.orders.length === 0) {
                        container.innerHTML = `<p>No ${status} orders found.</p>`;
                    } else {
                        let html = `<p>Found ${result.orders.length} ${status} orders:</p>`;
                        result.orders.forEach(order => {
                            html += `
                                <div class="order-item">
                                    <strong>Order #${order.id}</strong> - ${order.orderNumber}<br>
                                    <strong>Status:</strong> ${order.status}<br>
                                    <strong>Total:</strong> ₱${order.total}<br>
                                    <strong>Date:</strong> ${order.date} ${order.time}<br>
                                    <strong>Items:</strong> ${order.items ? order.items.length : 0}<br>
                                    <strong>Payment:</strong> ${order.payment}
                                </div>
                            `;
                        });
                        container.innerHTML = html;
                    }
                } else {
                    container.innerHTML = `<p style="color: red;">Error: ${result.message}</p>`;
                }
            } catch (error) {
                console.error(`Error loading ${status} orders:`, error);
                container.innerHTML = `<p style="color: red;">Network error: ${error.message}</p>`;
            }
        }
        
        async function showRawResponse(status) {
            const rawContainer = document.getElementById(status + '-raw');
            
            try {
                const apiBase = window.location.protocol + '//' + window.location.host + '/esang_delicacies';
                const url = `${apiBase}/public/api/get_customer_orders.php?status=${status}`;
                const response = await fetch(url);
                const result = await response.json();
                
                rawContainer.textContent = JSON.stringify(result, null, 2);
                rawContainer.style.display = rawContainer.style.display === 'none' ? 'block' : 'none';
                
            } catch (error) {
                rawContainer.textContent = `Error: ${error.message}`;
                rawContainer.style.display = 'block';
            }
        }
    </script>
</body>
</html>