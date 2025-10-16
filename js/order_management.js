document.addEventListener('DOMContentLoaded', () => {
    const orders = [
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
            refNumber: '34568900',
            assignedRider: 'Not assigned'
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
            refNumber: '34568900',
            assignedRider: 'Not assigned'
        },
        {
            id: '#10342',
            customerName: 'Alice Smith',
            orderName: 'Cassava Cake',
            quantity: 3,
            amount: 550,
            status: 'on going',
            payment: 'paid',
            paymentMethod: 'Gcash',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+3',
            refNumber: '34568900',
            assignedRider: 'esang'
        },
        {
            id: '#10342',
            customerName: 'Sophia Reyes',
            orderName: 'chicken parmesan',
            quantity: 3,
            amount: 550,
            status: 'completed',
            payment: 'paid',
            paymentMethod: 'Gcash',
            proofOfPayment: 'https://placehold.co/150x150/d1d5db/000000?text=Proof+4',
            refNumber: '34568900',
            assignedRider: 'esang'
        },
        {
            id: '#10349',
            customerName: 'Marc Ramos',
            orderName: 'Macapuno',
            quantity: 3,
            amount: 200,
            status: 'pending',
            payment: 'unpaid',
            paymentMethod: 'Metrobank',
            proofOfPayment: '',
            refNumber: '500314',
            assignedRider: 'Not assigned'
        },
    ];

    const riders = [
        'Not assigned',
        'esang'
    ];

    const pendingOrdersBody = document.getElementById('pending-orders-body');
    const ongoingOrdersBody = document.getElementById('ongoing-orders-body');
    const completedOrdersBody = document.getElementById('completed-orders-body');
            
    const proofModal = document.getElementById('proof-modal');
    const modalImage = document.getElementById('modal-image');
    const closeButtons = proofModal.querySelectorAll('.btn-close, .btn-secondary');

    const generateTableRow = (order) => {
        const row = document.createElement('tr');
                
        const riderOptions = riders.map(rider => `<option value="${rider}" ${order.assignedRider === rider ? 'selected' : ''}>${rider}</option>`).join('');
                
        const isCompleted = order.status === 'completed';

        let proofOfPaymentContent = '';
        if (order.proofOfPayment) {
            proofOfPaymentContent = `<button class="btn btn-link view-proof-btn" data-image="${order.proofOfPayment}">View</button>`;
        }

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
            <td>
                <select class="form-select" ${isCompleted ? 'disabled' : ''}>
                    ${riderOptions}
                </select>
            </td>
            <td>
                <button class="btn ${isCompleted ? 'btn-secondary disabled' : 'btn-primary'} confirm-order-btn" ${isCompleted ? 'disabled' : ''}>Confirm Order</button>
            </td>
        `;

        return row;
    };

            const renderOrders = () => {
                pendingOrdersBody.innerHTML = '';
                ongoingOrdersBody.innerHTML = '';
                completedOrdersBody.innerHTML = '';

                const pendingOrders = orders.filter(order => order.status === 'pending');
                const ongoingOrders = orders.filter(order => order.status === 'on going');
                const completedOrders = orders.filter(order => order.status === 'completed');

                pendingOrders.forEach(order => pendingOrdersBody.appendChild(generateTableRow(order)));
                ongoingOrders.forEach(order => ongoingOrdersBody.appendChild(generateTableRow(order)));
                completedOrders.forEach(order => completedOrdersBody.appendChild(generateTableRow(order)));
            };

            const tabButtons = document.querySelectorAll('.tab-button');
            const tabPanes = document.querySelectorAll('.tab-pane');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    button.classList.add('active');
                    const targetPaneId = button.dataset.target;
                    document.getElementById(targetPaneId).classList.add('active');
                });
            });

            renderOrders();

            document.body.addEventListener('click', (e) => {
                if (e.target.classList.contains('view-proof-btn')) {
                    modalImage.src = e.target.dataset.image;
                    proofModal.classList.add('show');
                }

                if (e.target.classList.contains('confirm-order-btn')) {
                    if (!e.target.disabled) {
                        const row = e.target.closest('tr');
                        const orderId = row.querySelector('td:first-child').textContent;
                        console.log(`Order ${orderId} confirmed.`);
                    }
                }
            });

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