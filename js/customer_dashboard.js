document.addEventListener('DOMContentLoaded', () => {
    const menuTabs = document.querySelector('.menu-tab');
    const menuContents = document.querySelector('#menuGrids');
    const searchInput = document.querySelector('.search-bar input');

    const packagedMealsGrid = document.getElementById('packaged-meals-grid');
    let currentStep = 1;
    let selectedPackage = null;
    let selectedMain = null;
    let selectedVeggie = null;
    
    // --- Cart State and Elements ---
    let cart = JSON.parse(localStorage.getItem('customerCart')) || [];
    const cartButton = document.getElementById('cartButton');
    const cartModal = document.getElementById('cartModal');
    const closeCartModal = document.getElementById('closeCartModal');
    const cartItemsContainer = document.getElementById('cart-items-container');
    const cartCountBadge = document.getElementById('cart-count');
    const cartTotalDisplay = document.getElementById('cart-total');
    const notificationContainer = document.getElementById('notification-container');
    
    // --- Load Data from Database ---
    let menuData = [];
    
    // Load products from database
    async function loadProducts() {
        try {
            const response = await fetch('/esang_delicacies/public/api/get_customer_products.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                menuData = result.categories;
                initializeMenu();
            } else {
                console.error('Failed to load products:', result.message);
                showNotification('Failed to load products: ' + result.message, 5000, true);
            }
        } catch (error) {
            console.error('Error loading products:', error);
            showNotification('Error loading products. Please refresh the page.', 5000, true);
        }
    }

    // --- Utility Functions ---

    // Function to show a non-intrusive notification toast
    const showNotification = (message, duration = 3000, isError = false) => {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.textContent = message;
        
        if (isError) {
             toast.style.backgroundColor = '#d9534f'; 
        }

        notificationContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 500);
        }, duration);
    };

    // Function to save cart, update count, and render
    const updateCartDisplay = () => {
        localStorage.setItem('customerCart', JSON.stringify(cart));
        // Calculate total quantity of items (sum of all item quantities)
        cartCountBadge.textContent = cart.reduce((total, item) => total + item.quantity, 0);
        renderCartItems(); // Re-render the cart items to show the current state
    };

    // --- NEW: Adjust Quantity Function ---
    const adjustQuantity = (index, delta) => {
        if (index >= 0 && index < cart.length) {
            const currentQty = cart[index].quantity;
            const newQty = currentQty + delta;
            
            if (newQty > 0) {
                cart[index].quantity = newQty;
                updateCartDisplay();
            } else {
                // If quantity goes to zero or less, remove the item
                removeItemFromCart(index);
            }
        }
    };

    // --- Remove Item Function ---
    const removeItemFromCart = (index) => {
        if (index >= 0 && index < cart.length) {
            const removedItemName = cart[index].name.split('(')[0].trim();
            cart.splice(index, 1);
            updateCartDisplay();
            showNotification(`Removed "${removedItemName}" from cart.`, 2000, true);
        }
    };

    // --- MODIFIED: Render Cart Items to include +/- buttons ---
    const renderCartItems = () => {
        cartItemsContainer.innerHTML = '';
        let total = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p style="text-align: center; color: #888;">Your cart is empty. Start ordering!</p>';
        } else {
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                const itemDiv = document.createElement('div');
                itemDiv.className = 'cart-item';
                itemDiv.innerHTML = `
                    <div class="cart-item-details">
                        <strong>${item.name}</strong>
                        ${item.size ? `<small>Size: ${item.size}</small>` : ''}
                        ${item.flavors ? `<small>Flavors: ${item.flavors.join(', ')}</small>` : ''}
                        ${item.packageDetails ? `<small>${item.packageDetails}</small>` : ''}
                    </div>
                    <div class="item-quantity-price">
                        <div class="item-quantity-control">
                            <button class="qty-btn" data-action="minus" data-index="${index}">-</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" data-action="plus" data-index="${index}">+</button>
                        </div>
                        
                        <span>₱${itemTotal.toFixed(2)}</span>
                        
                        <button class="remove-item-btn" data-index="${index}">&times;</button>
                    </div>
                `;
                cartItemsContainer.appendChild(itemDiv);
            });
        }

        cartTotalDisplay.textContent = `₱${total.toFixed(2)}`;
    };

    // Function to add an item to the cart
    const addItemToCart = (item) => {
        const existingItemIndex = cart.findIndex(
            i => i.name === item.name && 
                 i.size === item.size && 
                 JSON.stringify(i.flavors || []) === JSON.stringify(item.flavors || []) &&
                 i.packageDetails === item.packageDetails
        );

        if (existingItemIndex > -1) {
            cart[existingItemIndex].quantity += 1;
        } else {
            cart.push({...item, quantity: 1});
        }
        updateCartDisplay();
    };

    // --- MODIFIED: Event Listeners for Cart Actions ---
    cartButton.addEventListener('click', () => {
        renderCartItems(); 
        cartModal.style.display = 'block';
    });

    closeCartModal.addEventListener('click', () => {
        cartModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === cartModal) {
            cartModal.style.display = 'none';
        }
    });

    // NEW: Listener for Quantity Adjust and Remove Buttons
    cartItemsContainer.addEventListener('click', (e) => {
        const target = e.target;
        const index = parseInt(target.dataset.index);

        if (target.classList.contains('remove-item-btn')) {
            removeItemFromCart(index);
        } 
        else if (target.classList.contains('qty-btn')) {
            const action = target.dataset.action;
            const delta = action === 'plus' ? 1 : -1;
            adjustQuantity(index, delta);
        }
    });

    document.getElementById('checkoutButton').addEventListener('click', () => {
        if (cart.length > 0) {
            // Save the cart again just to be safe
            localStorage.setItem('customerCart', JSON.stringify(cart));

            // Redirect to Orders page
            window.location.href = 'orders.php';
        } else {
            showNotification('Your cart is empty!', 2000, true);
        }
    });


    // --- Rest of the code remains UNCHANGED for brevity ---
    // (Category Buttons, Grids, Bilao Logic, Add to Cart Logic, Search, Packaged Meals Flow)

    function initializeMenu() {
        // --- Build Category Buttons ---
        menuTabs.innerHTML = '';
        menuData.forEach((category, index) => {
            const btn = document.createElement('button');
            btn.className = `menu-button ${index === 0 ? 'active' : ''}`;
            btn.dataset.category = category.id;
            btn.innerHTML = `<i class=\"fa-solid fa-utensils\"></i> ${category.name}
                                <span class=\"item-count\">${category.items.length} items</span>`;
            menuTabs.appendChild(btn);
        });

        // --- Build Category Grids ---
        menuContents.innerHTML = '';
        menuData.forEach((category, index) => {
            const grid = document.createElement('div');
            grid.id = `${category.id}-grid`;
            grid.className = `menu-grid ${index === 0 ? 'active-grid' : ''}`;

            category.items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'menu-item';

                let image = item.image || 'https://placehold.co/200x150?text=No+Image';
                let stockText, stockClass;
                if (item.stock_quantity <= 0) {
                    stockText = 'Out of stock';
                    stockClass = 'out-of-stock';
                } else if (item.stock_quantity <= item.min_stock_level) {
                    stockText = `Low stock (${item.stock_quantity})`;
                    stockClass = 'low';
                } else {
                    stockText = `In stock (${item.stock_quantity})`;
                    stockClass = 'good';
                }
                const disabled = item.stock_quantity <= 0 ? 'disabled' : '';
                const disabledText = item.stock_quantity <= 0 ? 'Out of Stock' : 'Add to Cart';
                
                if (category.itemType === 'single-price') {
                    itemDiv.innerHTML = `
                        <img src="${image}" alt="${item.name}">
                        <div class="item-details-wrapper">
                            <h3>${item.name}</h3>
                            <p class="price">₱${item.price.toFixed(2)}</p>
                            <p class="stock ${stockClass}">${stockText}</p>
                            <button class="add-to-cart-btn" ${disabled} data-name="${item.name}" data-price="${item.price.toFixed(2)}">${disabledText}</button>
                        </div>
                    `;
                }
                // Handle multi-price and flavor items if needed in the future
                else if (category.itemType === 'multi-price') {
                    let buttons = item.prices ? item.prices.map((p, i) =>
                        `<button class=\"bilao-button ${i===0?'active-bilao-button':''}\" data-price=\"${p.price}\" data-size=\"${p.size}\">
                            ${p.size}
                        </button>`).join('') : '';
                    itemDiv.innerHTML = `
                        <img src=\"${image}\" alt=\"${item.name}\">
                        <div class=\"item-details-wrapper\">
                            <h3>${item.name}</h3>
                            <div class=\"bilao-options\">${buttons}</div>
                            <p class=\"price\">₱${item.price.toFixed(2)}</p>
                            <p class=\"stock ${stockClass}\">${stockText}</p>
                            <button class=\"add-to-cart-btn\" ${disabled} data-name=\"${item.name}\" data-price=\"${item.price.toFixed(2)}\">${disabledText}</button>
                        </div>
                    `;
                }
                else if (category.itemType === 'flavors') {
                    const flavorBasePrice = item.price ? item.price.toFixed(2) : '0.00'; 
                    
                    let flavors = item.flavors ? item.flavors.map(f =>
                        `<label><input type=\"checkbox\" name=\"flavor\" value=\"${f.name}\" ${disabled ? 'disabled' : ''}> ${f.name}</label><br>`
                    ).join('') : '';
                    itemDiv.innerHTML = `
                        <img src=\"${image}\" alt=\"${item.name}\">
                        <div class=\"item-details-wrapper\">
                            <h3>${item.name}</h3>
                            <p class=\"price\">₱${flavorBasePrice}</p>
                            <p class=\"stock ${stockClass}\">${stockText}</p>
                            <div class=\"delicacy-selection\">${flavors}</div>
                            <button class=\"add-to-cart-btn add-flavor-btn\" ${disabled} data-name=\"${item.name}\" data-price=\"${flavorBasePrice}\">${disabledText}</button>
                        </div>
                    `;
                }

                grid.appendChild(itemDiv);
            });

            menuContents.appendChild(grid);
        });
        
        // Initialize category switching after menu is built
        initializeCategorySwitching();
    }

    // --- Category Switching ---
    function initializeCategorySwitching() {
        const menuButtons = document.querySelectorAll('.menu-button');
        const menuGrids = document.querySelectorAll('.menu-grid');

        menuButtons.forEach(button => {
            button.addEventListener('click', () => {
                const category = button.dataset.category;

                menuButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                menuGrids.forEach(grid => {
                    if (grid.id === `${category}-grid`) {
                        grid.classList.add('active-grid');
                    } else {
                        grid.classList.remove('active-grid');
                    }
                });

                searchInput.value = '';
                filterMenuItems();
            });
        });
    }

    // --- Bilao Button Logic ---
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('bilao-button')) {
            const parentItem = e.target.closest('.menu-item');
            if (parentItem) {
                parentItem.querySelectorAll('.bilao-button').forEach(btn => {
                    btn.classList.remove('active-bilao-button');
                });
                e.target.classList.add('active-bilao-button');
                
                const priceDisplay = parentItem.querySelector('.price');
                const addButton = parentItem.querySelector('.add-to-cart-btn');

                if (priceDisplay) {
                    priceDisplay.textContent = `₱${parseFloat(e.target.dataset.price).toFixed(2)}`;
                }
                if (addButton) {
                    addButton.dataset.price = parseFloat(e.target.dataset.price).toFixed(2);
                    addButton.dataset.size = e.target.dataset.size;
                }
            }
        }
    });

    // --- Add to Cart Logic ---
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('add-to-cart-btn')) {
            // Check if button is disabled (out of stock)
            if (e.target.hasAttribute('disabled')) {
                showNotification('This item is currently out of stock', 3000, true);
                return;
            }
            
            const name = e.target.dataset.name;
            const price = parseFloat(e.target.dataset.price);
            const size = e.target.dataset.size || null;
            const isFlavorBtn = e.target.classList.contains('add-flavor-btn');
            
            if (!isFlavorBtn) {
                const item = { name, price, size };
                addItemToCart(item);
                showNotification(`\"${name}${size ? ' (' + size + ')' : ''}\" added to cart!`);

            } else {
                const parentItem = e.target.closest('.menu-item');
                const selectedFlavorCheckboxes = parentItem.querySelectorAll('.delicacy-selection input[type="checkbox"]:checked');
                
                if (selectedFlavorCheckboxes.length > 0) {
                    const flavors = Array.from(selectedFlavorCheckboxes).map(cb => cb.value);
                    
                    const item = { 
                        name: name,
                        price: price,
                        flavors: flavors
                    };
                    addItemToCart(item);
                    showNotification(`"${name}" with ${flavors.length} flavor(s) added to cart!`);
                    selectedFlavorCheckboxes.forEach(cb => cb.checked = false);
                } else {
                    showNotification(`Please select at least one flavor for ${name}.`, 3000, true);
                }
            }
        }
    });

    // --- Search Filter ---
    searchInput.addEventListener('keyup', filterMenuItems);
    function filterMenuItems() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeGrid = document.querySelector('.menu-grid.active-grid');
        
        if (activeGrid) {
            const menuItems = activeGrid.querySelectorAll('.menu-item');
            menuItems.forEach(item => {
                const itemName = item.querySelector('h3').textContent.toLowerCase();
                item.style.display = itemName.includes(searchTerm) ? 'flex' : 'none';
            });
        }
    }

    // --- Packaged Meals Flow ---
    if (packagedMealsGrid) {
        const packagedMealSteps = packagedMealsGrid.querySelectorAll('.packaged-meal-step');
        const showStep = (stepNumber) => {
            packagedMealSteps.forEach(step => step.classList.remove('active-step'));
            const targetStep = document.getElementById(`step-${stepNumber}`);
            if (targetStep) targetStep.classList.add('active-step');
        };
        showStep(1);

        packagedMealsGrid.addEventListener('click', (e) => {
            const target = e.target.closest('.menu-item');
            if (!target) return;
            
            if (target.classList.contains('package-option')) {
                selectedPackage = target.dataset.package;
                currentStep = 2;
                showStep(currentStep);
            }
            else if (target.classList.contains('main-option')) {
                selectedMain = target.dataset.main;
                currentStep = 3;
                showStep(currentStep);
            }
            else if (target.classList.contains('veggie-option')) {
                document.querySelectorAll('.veggie-option').forEach(item => item.classList.remove('selected'));
                target.classList.add('selected');
                selectedVeggie = target.dataset.veggie;
            }
        });

        packagedMealsGrid.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-button')) {
                if (selectedPackage && selectedMain && selectedVeggie) {
                    const packageName = `Packaged Meal (${selectedPackage})`;
                    const packagePrice = 500; 
                    
                    const item = {
                        name: packageName,
                        price: packagePrice,
                        packageDetails: `Main: ${selectedMain}, Veggie: ${selectedVeggie}`
                    };

                    addItemToCart(item);
                    showNotification(`"${packageName}" configured and added to cart!`, 4000);

                    selectedPackage = null;
                    selectedMain = null;
                    selectedVeggie = null;
                    document.querySelectorAll('.menu-item.selected').forEach(item => item.classList.remove('selected'));
                    currentStep = 1;
                    showStep(currentStep);
                } else {
                    showNotification('Please complete all package selections before adding to cart.', 3000, true);
                }
            }
        });
    }

    // Initialize cart display and load products on page load
    updateCartDisplay();
    loadProducts();
});
