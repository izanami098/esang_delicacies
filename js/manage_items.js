document.addEventListener('DOMContentLoaded', () => {
    // Check if the sidebar.js is loaded
    if (typeof openNav === 'undefined') {
        console.error('sidebar.js is not loaded. Please check the script path.');
    }

    const productsTableBody = document.querySelector('#productsTable tbody');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const closeEditModalBtn = document.getElementById('closeEditModal');
    const cancelEditBtn = document.getElementById('cancelEdit');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    const closeDeleteModalBtn = document.getElementById('closeDeleteModal');

    // Product data from database
    let products = [];
    let categories = [];

    // Load products from database
    async function loadProducts() {
        try {
            const response = await fetch('/esang_delicacies/public/api/get_products_with_stock.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                products = result.products;
                categories = result.categories;
                renderCategoryFilters();
                renderTable(products);
            } else {
                console.error('Failed to load products:', result.message);
                alert('Failed to load products: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading products:', error);
            alert('Error loading products: ' + error.message);
        }
    }

    // Render categories in the filter dropdown and edit modal
    const renderCategoryFilters = () => {
        categoryFilter.innerHTML = '<option value="all">All Categories</option>';
        const editCategorySelect = document.getElementById('editItemCategory');
        editCategorySelect.innerHTML = '';
        
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categoryFilter.appendChild(option);

            const editOption = option.cloneNode(true);
            editCategorySelect.appendChild(editOption);
        });
    };

    // Render table rows
    const renderTable = (itemsToRender) => {
        productsTableBody.innerHTML = '';
        if (itemsToRender.length === 0) {
            productsTableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No items found.</td></tr>';
            return;
        }

        itemsToRender.forEach(item => {
            const row = document.createElement('tr');
            
            // Stock status indicator
            const stockStatus = item.stock_quantity <= item.min_stock_level ? 'low' : 'good';
            const stockText = item.stock_quantity <= item.min_stock_level ? 
                `Low Stock (${item.stock_quantity})` : 
                `In Stock (${item.stock_quantity})`;

            row.innerHTML = `
                <td>${item.name}</td>
                <td>₱${item.price.toFixed(2)}</td>
                <td>${item.category}</td>
                <td>
                    <div class="stock-container">
                        <span class="stock-display ${stockStatus}" data-id="${item.id}">
                            ${stockText}
                        </span>
                        <div class="stock-controls" style="display: none;">
                            <input type="number" class="stock-input" value="${item.stock_quantity}" min="0" placeholder="Stock Quantity">
                            <input type="number" class="min-stock-input" value="${item.min_stock_level}" min="0" placeholder="Min Stock Level">
                            <button class="save-stock-btn" data-id="${item.id}">Save</button>
                            <button class="cancel-stock-btn" data-id="${item.id}">Cancel</button>
                        </div>
                    </div>
                </td>
                <td class="actions-cell">
                    <button class="edit-btn" data-id="${item.id}">Edit</button>
                    <button class="delete-btn" data-id="${item.id}">Delete</button>
                </td>
            `;

            productsTableBody.appendChild(row);

            // Add event listeners for stock management
            const stockDisplay = row.querySelector('.stock-display');
            const stockControls = row.querySelector('.stock-controls');
            const saveStockBtn = row.querySelector('.save-stock-btn');
            const cancelStockBtn = row.querySelector('.cancel-stock-btn');

            stockDisplay.addEventListener('click', () => {
                // Hide all other stock controls
                document.querySelectorAll('.stock-controls').forEach(control => {
                    if (control !== stockControls) {
                        control.style.display = 'none';
                    }
                });
                stockControls.style.display = stockControls.style.display === 'none' ? 'flex' : 'none';
            });

            saveStockBtn.addEventListener('click', async () => {
                const stockQuantity = parseInt(row.querySelector('.stock-input').value);
                const minStockLevel = parseInt(row.querySelector('.min-stock-input').value);
                
                if (isNaN(stockQuantity) || isNaN(minStockLevel) || stockQuantity < 0 || minStockLevel < 0) {
                    alert('Please enter valid stock values');
                    return;
                }

                try {
                    const response = await fetch('/esang_delicacies/public/api/update_product_stock.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            product_id: item.id,
                            stock_quantity: stockQuantity,
                            min_stock_level: minStockLevel
                        }),
                        credentials: 'include'
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Update local data
                        item.stock_quantity = stockQuantity;
                        item.min_stock_level = minStockLevel;
                        
                        // Update display
                        const newStockStatus = stockQuantity <= minStockLevel ? 'low' : 'good';
                        const newStockText = stockQuantity <= minStockLevel ? 
                            `Low Stock (${stockQuantity})` : 
                            `In Stock (${stockQuantity})`;
                        
                        stockDisplay.className = `stock-display ${newStockStatus}`;
                        stockDisplay.textContent = newStockText;
                        stockControls.style.display = 'none';
                        
                        alert('Stock updated successfully!');
                    } else {
                        alert('Failed to update stock: ' + result.message);
                    }
                } catch (error) {
                    alert('Error updating stock: ' + error.message);
                }
            });

            cancelStockBtn.addEventListener('click', () => {
                // Reset inputs to original values
                row.querySelector('.stock-input').value = item.stock_quantity;
                row.querySelector('.min-stock-input').value = item.min_stock_level;
                stockControls.style.display = 'none';
            });
        });
    };

    // Filter and search functionality
    const filterAndSearch = () => {
        const searchText = searchInput.value.toLowerCase();
        const categoryFilterValue = categoryFilter.value;

        const filteredItems = products.filter(item => {
            const matchesSearch = item.name.toLowerCase().includes(searchText);
            const matchesCategory = categoryFilterValue === 'all' || item.category === categoryFilterValue;
            return matchesSearch && matchesCategory;
        });

        renderTable(filteredItems);
    };

    // Open Edit Modal
    const openEditModal = (item) => {
        document.getElementById('editItemId').value = item.id;
        document.getElementById('editItemName').value = item.name;
        document.getElementById('editItemPrice').value = item.price;
        document.getElementById('editItemCategory').value = item.category;

        editModal.classList.add('active');
    };

    // Close Modals
    const closeModal = (modal) => {
        modal.classList.remove('active');
    };
    
    // Event Listeners for modals
    closeEditModalBtn.addEventListener('click', () => closeModal(editModal));
    cancelEditBtn.addEventListener('click', () => closeModal(editModal));
    window.addEventListener('click', (e) => {
        if (e.target === editModal) closeModal(editModal);
        if (e.target === deleteModal) closeModal(deleteModal);
    });
    
    closeDeleteModalBtn.addEventListener('click', () => closeModal(deleteModal));
    cancelDeleteBtn.addEventListener('click', () => closeModal(deleteModal));

    // Edit form submission
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = parseInt(document.getElementById('editItemId').value);
        const newName = document.getElementById('editItemName').value.trim();
        const newPrice = parseFloat(document.getElementById('editItemPrice').value);
        const newCategory = document.getElementById('editItemCategory').value;

        if (!newName || !newPrice || !newCategory) {
            alert('Please fill out all fields');
            return;
        }

        try {
            const response = await fetch('/esang_delicacies/public/api/update_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: id,
                    name: newName,
                    category: newCategory,
                    price: newPrice
                }),
                credentials: 'include'
            });

            const result = await response.json();
            if (result.success) {
                // Update local data
                const productIndex = products.findIndex(p => p.id === id);
                if (productIndex > -1) {
                    products[productIndex].name = newName;
                    products[productIndex].price = newPrice;
                    products[productIndex].category = newCategory;
                }
                
                filterAndSearch(); // Re-render the table with updated data
                closeModal(editModal);
                alert('Product updated successfully!');
            } else {
                alert('Failed to update product: ' + result.message);
            }
        } catch (error) {
            alert('Error updating product: ' + error.message);
        }
    });

    // Handle delete confirmation
    confirmDeleteBtn.addEventListener('click', async () => {
        const idToDelete = parseInt(deleteModal.dataset.itemId);
        if (idToDelete) {
            try {
                const response = await fetch('/esang_delicacies/public/api/delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: idToDelete
                    }),
                    credentials: 'include'
                });

                const result = await response.json();
                if (result.success) {
                    // Remove from local data
                    products = products.filter(item => item.id !== idToDelete);
                    filterAndSearch(); // Re-render the table
                    closeModal(deleteModal);
                    alert('Product deleted successfully!');
                } else {
                    alert('Failed to delete product: ' + result.message);
                }
            } catch (error) {
                alert('Error deleting product: ' + error.message);
            }
        }
    });

    // Event delegation for table buttons
    productsTableBody.addEventListener('click', (e) => {
        const target = e.target;
        const id = parseInt(target.dataset.id);
        
        if (target.classList.contains('edit-btn')) {
            const itemToEdit = products.find(item => item.id === id);
            if (itemToEdit) {
                openEditModal(itemToEdit);
            }
        } else if (target.classList.contains('delete-btn')) {
            deleteModal.dataset.itemId = id;
            deleteModal.classList.add('active');
        }
    });

    // Event listeners for controls
    searchInput.addEventListener('keyup', filterAndSearch);
    categoryFilter.addEventListener('change', filterAndSearch);

    // Initial load
    loadProducts();
});