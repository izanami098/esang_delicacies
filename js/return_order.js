document.addEventListener('DOMContentLoaded', () => {
    // Simulated data for return orders
    const returnOrders = [
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
            proofOfRefund: 'https://placehold.co/150x150/d1d5db/000000?text=Refund+1',
            reason: 'Item was damaged upon arrival',
            refundMethod: 'Gcash',
            refNumber: '34568900',
        },
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
            proofOfRefund: 'https://placehold.co/150x150/d1d5db/000000?text=Refund+1',
            reason: 'Received wrong item',
            refundMethod: 'Bank Transfer',
            refNumber: '34568900',
        },
    ];

    const returnOrdersBody = document.getElementById('return-orders-body');
    const proofModal = document.getElementById('proof-modal');
    const modalImage = document.getElementById('modal-image');
    const closeButtons = proofModal.querySelectorAll('.btn-close, .btn-secondary');

    // NEW: Get elements for the confirmation modal
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmationModalTitle = document.getElementById('confirmationModalTitle');
    const confirmationMessage = document.getElementById('confirmationMessage');
    const confirmationConfirmBtn = document.querySelector('.confirmation-confirm-btn');
    const confirmationCancelBtn = document.querySelector('.confirmation-cancel-btn');
    const confirmationCloseBtn = document.querySelector('.confirmation-close-btn');

    // Reusable function to show the confirmation modal
    const showConfirmationModal = (title, message, callback) => {
        confirmationModalTitle.textContent = title;
        confirmationMessage.textContent = message;
        confirmationModal.classList.add('show');

        // Remove previous event listeners
        confirmationConfirmBtn.onclick = null;

        // Attach new listener for the confirmation action
        confirmationConfirmBtn.onclick = () => {
            callback();
            hideConfirmationModal();
        };
    };

    // Reusable function to hide the confirmation modal
    const hideConfirmationModal = () => {
        confirmationModal.classList.remove('show');
    };

    const generateTableRow = (order) => {
        const row = document.createElement('tr');

        let proofOfPaymentContent = order.proofOfPayment ? `<button class="btn-link view-proof-btn" data-image="${order.proofOfPayment}">View</button>` : '';
        let proofOfRefundContent = order.proofOfRefund ? `<button class="btn-link view-proof-btn" data-image="${order.proofOfRefund}">View</button>` : '';

        const actionsContent = `
            ${order.status === 'pending' ?
                `<button class="btn-action btn-accept">Accept</button>
                 <button class="btn-action btn-reject">Reject</button>`
            : 'N/A'
            }
        `;

        row.innerHTML = `
            <td>${order.id}</td>
            <td>${order.customerName}</td>
            <td>${order.orderName}</td>
            <td>${order.quantity}</td>
            <td>₱${order.amount}</td>
            <td>${order.status}</td>
            <td>${order.payment}</td>
            <td>${order.paymentMethod}</td>
            <td>${proofOfPaymentContent}</td>
            <td>${order.refNumber}</td>
            <td>${proofOfRefundContent}</td>
            <td>${order.reason}</td>
            <td>${order.refundMethod}</td>
            <td>${actionsContent}</td>
        `;

        return row;
    };

    const renderReturnOrders = () => {
        returnOrdersBody.innerHTML = '';
        returnOrders.forEach(order => returnOrdersBody.appendChild(generateTableRow(order)));
    };

    renderReturnOrders();

    document.body.addEventListener('click', (e) => {
        // Handle "View Proof" buttons for both payment and refund
        if (e.target.classList.contains('view-proof-btn')) {
            modalImage.src = e.target.dataset.image;
            proofModal.classList.add('show');
        }

        // Handle "Accept" button
        if (e.target.classList.contains('btn-accept')) {
            const row = e.target.closest('tr');
            const orderId = row.querySelector('td:first-child').textContent;
            showConfirmationModal('Accept Refund', `Are you sure you want to accept the refund for order ${orderId}?`, () => {
                console.log(`Return order ${orderId} accepted.`);
                // Implement logic to update the order status, process refund, etc.
            });
        }

        // Handle "Reject" button
        if (e.target.classList.contains('btn-reject')) {
            const row = e.target.closest('tr');
            const orderId = row.querySelector('td:first-child').textContent;
            showConfirmationModal('Reject Refund', `Are you sure you want to reject the refund for order ${orderId}?`, () => {
                console.log(`Return order ${orderId} rejected.`);
                // Implement logic to update the order status, notify customer, etc.
            });
        }
    });

    // Close proof modal buttons
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            proofModal.classList.remove('show');
        });
    });

    // Close confirmation modal buttons
    confirmationCancelBtn.addEventListener('click', hideConfirmationModal);
    confirmationCloseBtn.addEventListener('click', hideConfirmationModal);

    // Close modals when clicking outside of them
    window.addEventListener('click', (e) => {
        if (e.target === proofModal) {
            proofModal.classList.remove('show');
        }
        if (e.target === confirmationModal) {
            hideConfirmationModal();
        }
    });
});