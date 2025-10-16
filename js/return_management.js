document.addEventListener('DOMContentLoaded', () => {
    // Simulated data for ALL return orders (matching the original)
    const allReturnOrders = [
        // Pending order from original data
        {
            id: '#10345',
            customerName: 'John Doe',
            orderName: 'Cassava Box',
            quantity: 3,
            amount: 550,
            status: 'pending',
            payment: 'paid',
            paymentMethod: 'Gcash',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+1',
            proofOfRefund: 'N/A', // Assuming no proof of refund if pending or rejected
            reason: 'Item was damaged upon arrival',
            refundMethod: 'Gcash',
            refNumber: '34568900',
        },
        // Pending order from original data
        {
            id: '#10346',
            customerName: 'Pm sq',
            orderName: 'Pichi Pichi',
            quantity: 3,
            amount: 65,
            status: 'pending',
            payment: 'paid',
            paymentMethod: 'Gcash',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+2',
            proofOfRefund: 'N/A', // Assuming no proof of refund if pending or rejected
            reason: 'Received wrong item',
            refundMethod: 'Bank Transfer',
            refNumber: '34568900',
        },
        // ADDED: A REJECTED order to be displayed on this page
        {
            id: '#10347',
            customerName: 'Alice Smith',
            orderName: 'Ube Halaya Jar',
            quantity: 1,
            amount: 150,
            status: 'rejected', // KEY STATUS CHANGE
            payment: 'paid',
            paymentMethod: 'PayMaya',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+3',
            proofOfRefund: 'N/A',
            reason: 'Return requested after 30 days',
            refundMethod: 'N/A',
            refNumber: 'N/A',
        },
        // ADDED: Another REJECTED order
        {
            id: '#10348',
            customerName: 'Bob Johnson',
            orderName: 'Sapin-Sapin',
            quantity: 5,
            amount: 75,
            status: 'rejected', // KEY STATUS CHANGE
            payment: 'paid',
            paymentMethod: 'Gcash',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+4',
            proofOfRefund: 'N/A',
            reason: 'Item was not in original packaging',
            refundMethod: 'N/A',
            refNumber: 'N/A',
        },
    ];

    // Filter the orders to only include rejected ones
    const rejectedOrders = allReturnOrders.filter(order => order.status === 'rejected');

    // NOTE: The ID is changed to match the new HTML body ID
    const rejectedOrdersBody = document.getElementById('rejected-orders-body');
    const proofModal = document.getElementById('proof-modal');
    const modalImage = document.getElementById('modal-image');
    const closeButtons = proofModal.querySelectorAll('.btn-close, .btn-secondary');

    const generateTableRow = (order) => {
        const row = document.createElement('tr');

        // Note: For rejected orders, proofOfRefund is typically 'N/A'
        let proofOfPaymentContent = order.proofOfPayment ? `<button class="btn-link view-proof-btn" data-image="${order.proofOfPayment}">View</button>` : 'N/A';
        let proofOfRefundContent = order.proofOfRefund && order.proofOfRefund !== 'N/A' ? `<button class="btn-link view-proof-btn" data-image="${order.proofOfRefund}">View</button>` : 'N/A';

        // Actions are not usually needed for rejected orders, so we display a note.
        const actionsContent = 'No further action required. Check reason.';

        row.innerHTML = `
            <td>${order.id}</td>
            <td>${order.customerName}</td>
            <td>${order.orderName}</td>
            <td>${order.quantity}</td>
            <td>₱${order.amount}</td>
            <td><span style="color: #dc2626; font-weight: bold;">${order.status.toUpperCase()}</span></td>
            <td>${order.payment}</td>
            <td>${order.paymentMethod}</td>
            <td>${proofOfPaymentContent}</td>
            <td>${order.refNumber !== 'N/A' ? order.refNumber : 'N/A'}</td>
            <td>${proofOfRefundContent}</td>
            <td>${order.reason}</td>
            <td>${order.refundMethod !== 'N/A' ? order.refundMethod : 'N/A'}</td>
            <td>${actionsContent}</td>
        `;

        return row;
    };

    const renderRejectedOrders = () => {
        rejectedOrdersBody.innerHTML = '';
        if (rejectedOrders.length === 0) {
             const row = document.createElement('tr');
             row.innerHTML = `<td colspan="14" style="text-align: center; color: #6b7280;">No rejected return orders found.</td>`;
             rejectedOrdersBody.appendChild(row);
        } else {
            rejectedOrders.forEach(order => rejectedOrdersBody.appendChild(generateTableRow(order)));
        }
    };

    renderRejectedOrders();

    document.body.addEventListener('click', (e) => {
        // Handle "View Proof" buttons for payment
        if (e.target.classList.contains('view-proof-btn')) {
            modalImage.src = e.target.dataset.image;
            proofModal.classList.add('show');
        }
        // Note: Actions (Accept/Reject) from the original code are removed as they are not relevant here.
    });

    // Close proof modal buttons
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            proofModal.classList.remove('show');
        });
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', (e) => {
        if (e.target === proofModal) {
            proofModal.classList.remove('show');
        }
    });
});