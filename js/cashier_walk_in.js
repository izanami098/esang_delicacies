document.addEventListener('DOMContentLoaded', () => {
    // --- UI Elements ---
    const welcomeGreeting = document.getElementById('welcome-greeting');
    const currentDateElement = document.getElementById('current-date');
    const searchBar = document.getElementById('search-bar');
    const orderList = document.getElementById('order-list');
    const totalPriceElement = document.getElementById('total-price');
    const paymentButtons = document.querySelectorAll('.payment-button');
    const paymentInputs = document.getElementById('payment-inputs');
    const cashAmountInput = document.getElementById('cash-amount');
    const cashChangeElement = document.getElementById('cash-change');
    const gcashAmountInput = document.getElementById('gcash-amount');
    const splitCashAmountInput = document.getElementById('split-cash-amount');
    const splitGcashAmountInput = document.getElementById('split-gcash-amount');
    const confirmPaymentButton = document.getElementById('confirm-payment-button');
    const clearOrderButton = document.querySelector('.clear-order-button');
    const receiptContent = document.getElementById('receipt-content');
    const downloadReceiptButton = document.getElementById('download-receipt-button');
    const allMenuButton = document.querySelector('.all-menu-button');
    const menuTabs = document.getElementById('menuTabs');
    const menuContents = document.getElementById('menuContents');

    // --- State Variables ---
    let order = [];
    let total = 0;
    let currentPaymentMethod = 'cash';

    // --- Initial Setup ---
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    welcomeGreeting.textContent = `Welcome, Cashier!`;
    currentDateElement.textContent = today.toLocaleDateString('en-US', options);

    // --- Load Menu Data from localStorage ---
    let menuData = JSON.parse(localStorage.getItem('menuData')) || [];

    function renderMenu() {
        menuTabs.innerHTML = '';
        menuContents.innerHTML = '';

        menuData.forEach((category, index) => {
            // Category button
            const button = document.createElement('button');
            button.className = 'menu-button';
            if (index === 0) button.classList.add('active');
            button.dataset.category = category.id;
            button.innerHTML = `<i class="fa-solid fa-utensils"></i><span style="color: white;"> ${category.name}</span>`;
            menuTabs.appendChild(button);

            // Category grid
            const grid = document.createElement('div');
            grid.id = `${category.id}-grid`;
            grid.className = 'menu-grid';
            if (index === 0) grid.classList.add('active-grid');

            category.items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'menu-item';
                itemDiv.dataset.name = item.name;

                if (category.itemType === 'single-price') {
                    itemDiv.dataset.price = item.price;
                    itemDiv.innerHTML = `
                        <img src="${item.image || 'https://placehold.co/200'}" alt="${item.name}">
                        <h3>${item.name}</h3>
                        <p class="price">₱${item.price.toFixed(2)}</p>
                    `;
                } else if (category.itemType === 'multi-price') {
                    let firstPrice = item.prices[0]?.price || 0;
                    itemDiv.dataset.price = firstPrice;
                    let buttons = item.prices.map(p =>
                        `<button class="bilao-button" data-price="${p.price}">${p.size}</button>`
                    ).join('');
                    itemDiv.innerHTML = `
                        <img src="${item.image || 'https://placehold.co/200'}" alt="${item.name}">
                        <h3>${item.name}</h3>
                        ${buttons}
                        <p class="price">₱${firstPrice.toFixed(2)}</p>
                    `;
                } else if (category.itemType === 'flavors') {
                    itemDiv.dataset.price = 0;
                    let flavors = item.flavors.map(f => `<li>${f.name}</li>`).join('');
                    itemDiv.innerHTML = `
                        <img src="${item.image || 'https://placehold.co/200'}" alt="${item.name}">
                        <h3>${item.name}</h3>
                        <ul>${flavors}</ul>
                    `;
                }

                // Add to order on click
                itemDiv.addEventListener('click', () => {
                    if (itemDiv.dataset.price && parseFloat(itemDiv.dataset.price) > 0) {
                        addItemToOrder({
                            name: itemDiv.dataset.name,
                            price: parseFloat(itemDiv.dataset.price)
                        });
                    }
                });

                grid.appendChild(itemDiv);
            });

            menuContents.appendChild(grid);
        });

        attachMenuEvents();
    }

    function attachMenuEvents() {
        const menuButtons = document.querySelectorAll('.menu-button');
        const menuGrids = document.querySelectorAll('.menu-grid');

        menuButtons.forEach(button => {
            button.addEventListener('click', () => {
                menuButtons.forEach(btn => btn.classList.remove('active'));
                menuGrids.forEach(grid => grid.classList.remove('active-grid'));

                button.classList.add('active');
                document.getElementById(`${button.dataset.category}-grid`).classList.add('active-grid');
            });
        });

        // Handle multi-price size selection
        document.querySelectorAll('.bilao-button').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const parent = btn.closest('.menu-item');
                const priceDisplay = parent.querySelector('.price');
                const newPrice = parseFloat(btn.dataset.price);
                parent.dataset.price = newPrice;
                priceDisplay.textContent = `₱${newPrice.toFixed(2)}`;
            });
        });
    }

    // --- Order Functions ---
    const addItemToOrder = (item) => {
        const existingItem = order.find(i => i.name === item.name);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            order.push({ ...item, quantity: 1 });
        }
        renderOrderSummary();
        updateTotals();
        updateReceiptPreview();
    };

    const renderOrderSummary = () => {
        orderList.innerHTML = '';
        order.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.classList.add('order-item');
            itemElement.innerHTML = `
                <div class="item-details">
                    <h4>${item.name}</h4>
                    <div class="quantity-controls">
                        <button class="decrease-qty" data-name="${item.name}">-</button>
                        <span>${item.quantity}</span>
                        <button class="increase-qty" data-name="${item.name}">+</button>
                    </div>
                </div>
                <span class="item-price">₱${(item.price * item.quantity).toFixed(2)}</span>
            `;
            orderList.appendChild(itemElement);
        });
    };

    const updateTotals = () => {
        total = order.reduce((sum, item) => sum + item.price * item.quantity, 0);
        totalPriceElement.textContent = `₱${total.toFixed(2)}`;
        calculateChange();
    };

    const clearOrder = () => {
        order = [];
        renderOrderSummary();
        updateTotals();
        updateReceiptPreview();
        cashAmountInput.value = '';
        gcashAmountInput.value = '';
        splitCashAmountInput.value = '';
        splitGcashAmountInput.value = '';
    };

    const calculateChange = () => {
        const cashPaid = parseFloat(cashAmountInput.value) || 0;
        const change = cashPaid - total;
        cashChangeElement.textContent = `₱${change.toFixed(2)}`;
    };

    // --- Receipt Functions ---
    const generateReceiptContent = () => {
        let content = 'ESANG DELICACIES\n\n';
        content += '----------------------------\n';
        order.forEach(item => {
            content += `${item.name} x${item.quantity}\n`;
            content += `   ₱${item.price.toFixed(2)} ea. = ₱${(item.price * item.quantity).toFixed(2)}\n`;
        });
        content += '----------------------------\n';
        content += `TOTAL: ₱${total.toFixed(2)}\n`;
        content += '----------------------------\n';
        content += `Payment Method: ${currentPaymentMethod}\n`;
        if (currentPaymentMethod === 'cash') {
            const cashPaid = parseFloat(cashAmountInput.value) || 0;
            const change = cashPaid - total;
            content += `Cash Paid: ₱${cashPaid.toFixed(2)}\n`;
            content += `Change Due: ₱${change.toFixed(2)}\n`;
        } else if (currentPaymentMethod === 'gcash') {
            const gcashPaid = parseFloat(gcashAmountInput.value) || 0;
            content += `GCash Paid: ₱${gcashPaid.toFixed(2)}\n`;
        } else if (currentPaymentMethod === 'split') {
            const cashPaid = parseFloat(splitCashAmountInput.value) || 0;
            const gcashPaid = parseFloat(splitGcashAmountInput.value) || 0;
            content += `Cash Paid: ₱${cashPaid.toFixed(2)}\n`;
            content += `GCash Paid: ₱${gcashPaid.toFixed(2)}\n`;
        }
        content += '----------------------------\n';
        content += `Thank you for your purchase!\n`;
        return content;
    };

    const updateReceiptPreview = () => {
        receiptContent.textContent = generateReceiptContent();
    };

    // --- Event Listeners ---
    searchBar.addEventListener('input', () => {
        const query = searchBar.value.toLowerCase();
        document.querySelectorAll('.menu-item').forEach(item => {
            const name = item.dataset.name.toLowerCase();
            item.style.display = name.includes(query) ? 'block' : 'none';
        });
    });

    orderList.addEventListener('click', (e) => {
        const target = e.target;
        const itemName = target.dataset.name;
        if (!itemName) return;
        const itemToUpdate = order.find(item => item.name === itemName);
        if (!itemToUpdate) return;
        if (target.classList.contains('increase-qty')) {
            itemToUpdate.quantity++;
        } else if (target.classList.contains('decrease-qty')) {
            itemToUpdate.quantity--;
            if (itemToUpdate.quantity <= 0) {
                order = order.filter(item => item.name !== itemName);
            }
        }
        renderOrderSummary();
        updateTotals();
        updateReceiptPreview();
    });

    clearOrderButton.addEventListener('click', clearOrder);

    paymentButtons.forEach(button => {
        button.addEventListener('click', () => {
            paymentButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            currentPaymentMethod = button.dataset.method;
            paymentInputs.querySelectorAll('.payment-input-group').forEach(group => group.classList.remove('active'));
            document.getElementById(`${currentPaymentMethod}-payment`).classList.add('active');
        });
    });

    cashAmountInput.addEventListener('input', calculateChange);

    confirmPaymentButton.addEventListener('click', () => {
        alert('Payment confirmed! Receipt is ready for download.');
    });

    downloadReceiptButton.addEventListener('click', () => {
        const content = generateReceiptContent();
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'receipt.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // --- Initialize ---
    renderMenu();
});
