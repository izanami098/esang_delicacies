document.addEventListener('DOMContentLoaded', () => {
    const paymentsDropdownBtn = document.getElementById('paymentsDropdown');
    const paymentsDropdownContainer = document.querySelector('.dropdown-container');
    const paymentForms = document.querySelectorAll('.payment-form');
    const paymentButtons = document.querySelectorAll('[data-payment]');
    const instructionMessage = document.getElementById('instruction-message');
    const invoicesBtn = document.querySelector('.btn-light.rounded.shadow-small'); // Select the Invoices button
    const paymentFormsContainer = document.getElementById('payment-forms-container');

    // Function to toggle the dropdown menu
    paymentsDropdownBtn.addEventListener('click', () => {
        paymentsDropdownContainer.classList.toggle('show');
    });

    // Hide dropdown when clicking outside
    window.addEventListener('click', (event) => {
        if (!paymentsDropdownContainer.contains(event.target)) {
            paymentsDropdownContainer.classList.remove('show');
        }
    });

    // Function to hide all forms and show a specific one
    function showForm(formId) {
        // Hide all forms and the instruction message
        paymentForms.forEach(form => form.classList.add('hidden'));
        instructionMessage.classList.add('hidden');
        paymentsDropdownContainer.classList.remove('show');

        // Show the requested form
        const formToShow = document.getElementById(formId);
        if (formToShow) {
            formToShow.classList.remove('hidden');
        }
    }

    // Add click listeners to the payment dropdown items
    paymentButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const paymentType = event.target.dataset.payment;
            if (paymentType === 'cod') {
                showForm('form-cod');
                // Load saved address from localStorage
                const savedAddress = localStorage.getItem('customerCODAddress') || "No address found";
                document.getElementById('cod-address').value = savedAddress;
            } else if (paymentType === 'gcash') {
                showForm('form-gcash');
                const btn = document.querySelector('#form-gcash .btn.btn-primary');
                btn.onclick = async () => {
                    try {
                        const customerId = window.customerId || 1;
                        const latestOrderId = window.latestOrderId || parseInt(localStorage.getItem('latestOrderId') || '0', 10);
                        if (!latestOrderId) { alert('No order selected to pay.'); return; }
                        const payload = {
                            orderId: latestOrderId,
                            customerId,
                            paymentMethod: 'gcash',
                            referenceNumber: document.getElementById('gcash-reference-number').value || null
                        };
                        const res = await fetch('/esang_delicacies/public/api/confirm_payment.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Payment failed');
                        alert('Payment submitted. Waiting for verification.');
                    } catch (e) { alert('Error: ' + e.message); }
                };
            } else if (paymentType === 'metrobank') {
                showForm('form-metrobank');
                const btn = document.querySelector('#form-metrobank .btn.btn-primary');
                btn.onclick = async () => {
                    try {
                        const customerId = window.customerId || 1;
                        const latestOrderId = window.latestOrderId || parseInt(localStorage.getItem('latestOrderId') || '0', 10);
                        if (!latestOrderId) { alert('No order selected to pay.'); return; }
                        const payload = {
                            orderId: latestOrderId,
                            customerId,
                            paymentMethod: 'bank_transfer',
                            referenceNumber: document.getElementById('metrobank-reference-number').value || null
                        };
                        const res = await fetch('/esang_delicacies/public/api/confirm_payment.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
                        const data = await res.json();
                        if (!data.ok) throw new Error(data.error || 'Payment failed');
                        alert('Payment submitted. Waiting for verification.');
                    } catch (e) { alert('Error: ' + e.message); }
                };
            }
        });
    });

    // Add click listener for the Invoices button (toggle behavior)
    invoicesBtn.addEventListener('click', async () => {
        // Toggle: if invoices are already visible, collapse back to instructions
        if (paymentFormsContainer.querySelector('.invoice-box')) {
            paymentFormsContainer.innerHTML = '';
            instructionMessage.classList.remove('hidden');
            paymentsDropdownContainer.classList.remove('show');
            return;
        }

        paymentFormsContainer.innerHTML = '';
        instructionMessage.classList.add('hidden');

        try {
            const customerId = window.customerId || 1;
            const res = await fetch('/esang_delicacies/public/api/invoices.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customerId })
            });
            const data = await res.json();
            if (!data.ok) { throw new Error(data.error || 'Failed to load invoices'); }

            const invoices = data.data || [];
            if (invoices.length === 0) {
                paymentFormsContainer.innerHTML = '<p style="padding: 1rem; color: #6b7280;">No invoices found.</p>';
                return;
            }

            invoices.forEach(inv => {
                const invoiceHtml = `
                <div class="invoice-box" data-order-id="${inv.orderId}">
                    <div class="invoice-header">
                        <div class="user-info">
                            <span class="username">Customer #${customerId}</span>
                            <span class="invoice-number">#${inv.orderId}</span>
                            <span class="date">${inv.createdAt || ''}</span>
                        </div>
                        <p class="address">Address: ${inv.deliveryAddress || ''}</p>
                        <p class="payment-type">Payment type: ${inv.paymentMethod || ''}</p>
                    </div>
                    <div class="invoice-items">
                        <table>
                            <thead>
                                <tr>
                                    <th>QTY</th>
                                    <th>ORDER</th>
                                    <th>UNIT PRICE</th>
                                    <th>AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${inv.items.map(it => `
                                    <tr>
                                        <td>${it.quantity}</td>
                                        <td>${it.name}</td>
                                        <td>₱${Number(it.price).toFixed(2)}</td>
                                        <td>₱${Number(it.price * it.quantity).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="invoice-summary">
                        <div class="summary-line">
                            <span class="label total-label">Total:</span>
                            <span class="value total-value">₱${Number(inv.total).toFixed(2)}</span>
                        </div>
                    </div>
                </div>`;

                paymentFormsContainer.insertAdjacentHTML('beforeend', invoiceHtml);
            });

            // Add a subtle selected style without layout shift
            if (!document.getElementById('invoice-select-style')) {
                const style = document.createElement('style');
                style.id = 'invoice-select-style';
                style.textContent = `.invoice-box{cursor:pointer} .invoice-box.selected{outline:2px solid #f59e0b; border-radius:8px;} .header-section{position:sticky; top:0; z-index:10; background:#ffffff; padding-top:0.5rem;}`;
                document.head.appendChild(style);
            }

            // Allow selecting an invoice to pay (bind only once)
            if (!window._invoiceClickBound) {
                paymentFormsContainer.addEventListener('click', (e) => {
                    const card = e.target.closest('.invoice-box');
                    if (!card) return;
                    // Toggle selection
                    if (card.classList.contains('selected')) {
                        card.classList.remove('selected');
                        window.latestOrderId = undefined;
                        localStorage.removeItem('latestOrderId');
                        return;
                    }
                    document.querySelectorAll('.invoice-box.selected').forEach(el => el.classList.remove('selected'));
                    card.classList.add('selected');
                    const oid = parseInt(card.dataset.orderId, 10);
                    window.latestOrderId = oid;
                    localStorage.setItem('latestOrderId', String(oid));
                    // Open the payments dropdown to guide the user next
                    paymentsDropdownContainer.classList.add('show');
                });
                // Deselect if clicking outside cards
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.invoice-box') && !e.target.closest('#paymentsDropdown')) {
                        document.querySelectorAll('.invoice-box.selected').forEach(el => el.classList.remove('selected'));
                    }
                });
                window._invoiceClickBound = true;
            }
        } catch (err) {
            paymentFormsContainer.innerHTML = `<p style="padding: 1rem; color: #ef4444;">Error: ${err.message}</p>`;
        }
    });
});