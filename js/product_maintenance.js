document.addEventListener('DOMContentLoaded', () => {
    let menuData = [];
    let currentCategoryId = null;

    // DOM Elements
    const addCategoryForm = document.getElementById('addCategoryForm');
    const categoryNameInput = document.getElementById('categoryNameInput');
    const itemTypeSelect = document.getElementById('itemTypeSelect');
    const categoryList = document.getElementById('categoryList');
    const menuItemsTitle = document.getElementById('menuItemsTitle');
    const addItemButton = document.getElementById('addItemButton');
    const menuItemList = document.getElementById('menuItemList');
    const selectCategoryPrompt = document.getElementById('selectCategoryPrompt');

    const addItemModal = document.getElementById('addItemModal');
    const closeModalButton = document.getElementById('closeModalButton');
    const singlePriceForm = document.getElementById('singlePriceForm');
    const multiPriceForm = document.getElementById('multiPriceForm');
    const flavorsForm = document.getElementById('flavorsForm');
            
    // Single price form elements
    const itemNameInput_single = document.getElementById('itemNameInput_single');
    const itemPriceInput_single = document.getElementById('itemPriceInput_single');
    const itemImageInput_single = document.getElementById('itemImageInput_single');

    // Multi price form elements
    const itemNameInput_multi = document.getElementById('itemNameInput_multi');
    const itemImageInput_multi = document.getElementById('itemImageInput_multi');
    const priceVariationsContainer = document.getElementById('priceVariationsContainer');
    const addPriceButton = document.getElementById('addPriceButton');

    // Flavors form elements
    const itemNameInput_flavors = document.getElementById('itemNameInput_flavors');
    const itemImageInput_flavors = document.getElementById('itemImageInput_flavors');
    const flavorsContainer = document.getElementById('flavorsContainer');
    const addFlavorButton = document.getElementById('addFlavorButton');

    // Predefined sizes for multi-price items
    const defaultSizes = ["12 inches", "14 inches", "16 inches", "18 inches"];


    // Load data from database
    async function loadData() {
        try {
            const response = await fetch('/esang_delicacies/public/api/get_products.php', {
                credentials: 'include'
            });
            const result = await response.json();
            
            if (result.success) {
                // Group products by category
                const categories = {};
                result.products.forEach(product => {
                    if (!categories[product.category]) {
                        categories[product.category] = {
                            id: product.category,
                            name: product.category,
                            items: [],
                            itemType: 'single-price' // Default to single price
                        };
                    }
                    categories[product.category].items.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        image: product.image,
                        prodId: product.id
                    });
                });
                
                menuData = Object.values(categories);
                renderCategories();
            } else {
                console.error('Failed to load products:', result.message);
                alert('Failed to load products: ' + result.message);
            }
        } catch (error) {
            console.error('Error loading products:', error);
            alert('Error loading products: ' + error.message);
        }
    }

    // --- BACKEND INTEGRATION: Add product to DB when admin adds a single-price item ---
    singlePriceForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = itemNameInput_single.value.trim();
        const price = parseFloat(itemPriceInput_single.value);
        const category = currentCategoryId ? (menuData.find(cat => cat.id === currentCategoryId)?.name || '') : '';
        const imageFile = itemImageInput_single.files[0];
        
        if (!name || !category || !price || price <= 0) {
            alert('Please fill out all fields.');
            return;
        }
        
        try {
            let imageUrl = '';
            
            // Upload image if provided
            if (imageFile) {
                const formData = new FormData();
                formData.append('image', imageFile);
                
                const uploadResponse = await fetch('/esang_delicacies/public/api/upload_image.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                
                const uploadResult = await uploadResponse.json();
                if (uploadResult.success) {
                    imageUrl = uploadResult.image_url;
                } else {
                    alert('Image upload failed: ' + uploadResult.message);
                    return;
                }
            }
            
            // Add product to database
            const response = await fetch('/esang_delicacies/public/api/add_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    prodName: name, 
                    prodCategory: category, 
                    prodPrice: price,
                    image_url: imageUrl
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            if (data.success) {
                // Reload data from database
                await loadData();
                alert('Product added successfully!');
                singlePriceForm.reset();
                addItemModal.classList.remove('modal-active');
            } else {
                alert('Failed to add product: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            alert('Error adding product: ' + err.message);
        }
    });

    // Function to render all categories
    function renderCategories() {
        categoryList.innerHTML = '';
        menuData.forEach(category => {
            const categoryElement = document.createElement('div');
            categoryElement.className = `category-item`;
            categoryElement.dataset.id = category.id;
            categoryElement.innerHTML = `
                <div class="category-content">
                    <span class="item-text-main">${category.name}</span>
                    <span class="item-text-secondary">${category.items.length} items</span>
                </div>
                <button class="delete-btn" data-id="${category.id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                </button>
            `;
            categoryElement.querySelector('.category-content').addEventListener('click', () => selectCategory(category.id));
            categoryElement.querySelector('.delete-btn').addEventListener('click', (e) => {
                e.stopPropagation();
                deleteCategory(category.id);
            });
            categoryList.appendChild(categoryElement);
        });
    }

    // Function to delete a category
    function deleteCategory(categoryId) {
        menuData = menuData.filter(category => category.id !== categoryId);
        saveData();
        renderCategories();
            
        if (currentCategoryId === categoryId) {
            currentCategoryId = null;
            menuItemsTitle.textContent = 'Menu Items';
            addItemButton.style.display = 'none';
            selectCategoryPrompt.style.display = 'block';
            menuItemList.innerHTML = '';
        }
    }

    // Function to select a category and display its items
    function selectCategory(categoryId) {
        currentCategoryId = categoryId;
        const selectedCategory = menuData.find(cat => cat.id === categoryId);

        menuItemsTitle.textContent = selectedCategory ? `Menu Items for ${selectedCategory.name}` : 'Menu Items';
        addItemButton.style.display = 'block';
        selectCategoryPrompt.style.display = 'none';

        renderMenuItems(selectedCategory.items, selectedCategory.itemType);

        document.querySelectorAll('#categoryList > .category-item').forEach(el => {
            if (el.dataset.id === categoryId) {
                el.classList.add('list-item-selected');
            } else {
                el.classList.remove('list-item-selected');
            }
        });
    }

    // Function to render menu items for the selected category
    function renderMenuItems(items, itemType) {
        menuItemList.innerHTML = '';
        if (!items.length) {
          menuItemList.innerHTML = '<p class="item-prompt">No items in this category.</p>';
          return;
        }
      
        items.forEach((item) => {
          const itemEl = document.createElement('div');
          itemEl.className = 'list-item';
      
          const imageHtml = item.image
            ? `<img src="${item.image}" alt="${item.name}" class="item-image">`
            : `<div class="no-image-placeholder">No Img</div>`;
      
          let middleHtml = '';
          if (itemType === 'single-price') {
            middleHtml = `
              <div class="item-info">
                <span class="item-text-main">${item.name}</span>
                <span class="item-text-price">₱${Number(item.price).toFixed(2)}</span>
              </div>`;
          } else if (itemType === 'multi-price') {
            const prices = (item.prices || [])
              .map((p) => `<li>${p.size}: ₱${Number(p.price).toFixed(2)}</li>`)
              .join('');
            middleHtml = `
              <div class="item-info">
                <span class="item-text-main">${item.name}</span>
                <ul class="price-list">${prices}</ul>
              </div>`;
          } else if (itemType === 'flavors') {
            const flavors = (item.flavors || []).map((f) => `<li>${f.name}</li>`).join('');
            middleHtml = `
              <div class="item-info">
                <span class="item-text-main">${item.name}</span>
                <ul class="flavor-list">${flavors}</ul>
              </div>`;
          }
      
          itemEl.innerHTML = `
            <div class="item-row">
              ${imageHtml}
              ${middleHtml}
              <div class="item-actions">
                <button
                  class="item-delete-btn"
                  data-id="${item.id || item.prodId}"
                  data-prod-id="${item.prodId || item.id || ''}"
                  data-name="${item.name}"
                  aria-label="Delete ${item.name}"
                  title="Delete"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                       viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                    <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                  </svg>
                  <span>Delete</span>
                </button>
              </div>
            </div>
          `;
      
          menuItemList.appendChild(itemEl);
        });
      }

      menuItemList.addEventListener('click', async (e) => {
        const btn = e.target.closest('.item-delete-btn');
        if (!btn) return;
      
        const prodId = btn.dataset.prodId || btn.dataset.id;
        const itemName = btn.dataset.name || 'this item';
      
        if (!confirm(`Delete "${itemName}"?`)) return;
      
        try {
          const res = await deleteProductOnServer(prodId);
          // update UI only after confirmed success
          const cat = menuData.find(c => c.id === currentCategoryId);
          if (cat) {
            const idx = cat.items.findIndex(it => (it.prodId || it.id) == prodId);
            if (idx > -1) {
              cat.items.splice(idx, 1);
              renderMenuItems(cat.items, cat.itemType);
              renderCategories();
            }
          }
          alert(res.message || 'Deleted');
        } catch (err) {
          alert(`Failed to delete: ${err.message}`);
        }
      });

      async function deleteProductOnServer(productId) {
        const resp = await fetch('/esang_delicacies/public/api/delete_product.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ product_id: Number(productId) }) // <- key the API expects
        });
      
        const text = await resp.text();                // read raw body
        let data;
        try { data = JSON.parse(text); }
        catch { throw new Error(`Invalid JSON from server: ${text.slice(0,300)}`); }
      
        if (!resp.ok) throw new Error(data?.message || `HTTP ${resp.status}`);
        if (!data.success) throw new Error(data.message || 'Delete failed');
      
        return data;
      }
            
    // Function to create and append a new price variation input group
    function createPriceVariationInput(size = '', price = '') {
        const newGroup = document.createElement('div');
        newGroup.className = 'price-variation-group';

        const sizeSelect = document.createElement('select');
        sizeSelect.className = 'size-input form-control';
        sizeSelect.required = true;
        
        const placeholderOption = document.createElement('option');
        placeholderOption.value = "";
        placeholderOption.textContent = "Select Size";
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        sizeSelect.appendChild(placeholderOption);

        defaultSizes.forEach(s => {
            const option = document.createElement('option');
            option.value = s;
            option.textContent = s;
            if (s === size) {
                option.selected = true;
            }
            sizeSelect.appendChild(option);
        });

        const priceInput = document.createElement('input');
        priceInput.type = 'number';
        priceInput.step = '0.01';
        priceInput.className = 'price-input form-control';
        priceInput.placeholder = 'Price (₱)';
        priceInput.value = price;
        priceInput.required = true;

        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'delete-icon-btn';
        deleteButton.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-circle">
                <circle cx="12" cy="12" r="10"/>
                <path d="m15 9-6 6"/>
                <path d="m9 9 6 6"/>
            </svg>
        `;

        newGroup.appendChild(sizeSelect);
        newGroup.appendChild(priceInput);
        newGroup.appendChild(deleteButton);
        priceVariationsContainer.appendChild(newGroup);
            
        deleteButton.addEventListener('click', () => {
            newGroup.remove();
        });
    }

    function addFlavorInput() {
        const newGroup = document.createElement('div');
        newGroup.className = 'flavor-group';
        newGroup.innerHTML = `
            <input type="text" class="flavor-input form-control" placeholder="e.g. Chili Garlic" required>
            <button type="button" class="delete-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-circle">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m15 9-6 6"/>
                    <path d="m9 9 6 6"/>
                </svg>
            </button>
        `;
        flavorsContainer.appendChild(newGroup);
            
        newGroup.querySelector('.delete-icon-btn').addEventListener('click', () => {
            newGroup.remove();
        });
    }

    // --- Event Listeners and Logic ---

    // Event listener for adding a new category
    addCategoryForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const name = categoryNameInput.value.trim();
        const itemType = itemTypeSelect.value;
        
        // Prevent duplicate categories
        const isDuplicateCategory = menuData.some(cat => cat.name.toLowerCase() === name.toLowerCase());
        if (isDuplicateCategory) {
            alert('A category with this name already exists.');
            return;
        }

        if (name) {
            const newCategory = {
                id: name, // Use category name as ID
                name: name,
                items: [],
                itemType: itemType
            };
            menuData.push(newCategory);
            renderCategories();
            addCategoryForm.reset();
        }
    });

    // Event listener for showing the "Add Item" modal
    addItemButton.addEventListener('click', () => {
        if (currentCategoryId) {
            const selectedCategory = menuData.find(cat => cat.id === currentCategoryId);
            
            // Hide all forms first
            singlePriceForm.style.display = 'none';
            multiPriceForm.style.display = 'none';
            flavorsForm.style.display = 'none';
                    
            if (selectedCategory.itemType === 'multi-price') {
                multiPriceForm.style.display = 'block';
                // Clear and add the default sizes using the updated function
                priceVariationsContainer.innerHTML = '';
                createPriceVariationInput("12 inches");
            } else if (selectedCategory.itemType === 'flavors') {
                flavorsForm.style.display = 'block';
                // Reset flavors to default
                flavorsContainer.innerHTML = `
                    <div class="flavor-group">
                        <input type="text" class="flavor-input form-control" placeholder="e.g. Barbecue" required>
                        <button type="button" class="delete-icon-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-circle">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="m15 9-6 6"/>
                                <path d="m9 9 6 6"/>
                            </svg>
                        </button>
                    </div>
                `;
                flavorsContainer.querySelector('.delete-icon-btn').addEventListener('click', (e) => e.target.closest('.flavor-group').remove());
            } else { // single-price
                singlePriceForm.style.display = 'block';
            }
            addItemModal.classList.add('modal-active');
        }
    });
            
    addPriceButton.addEventListener('click', () => createPriceVariationInput());
    addFlavorButton.addEventListener('click', addFlavorInput);

    closeModalButton.addEventListener('click', () => {
        addItemModal.classList.remove('modal-active');
        singlePriceForm.reset();
        multiPriceForm.reset();
        flavorsForm.reset();
    });
    addItemModal.addEventListener('click', (e) => {
        if (e.target === addItemModal) {
            addItemModal.classList.remove('modal-active');
            singlePriceForm.reset();
            multiPriceForm.reset();
            flavorsForm.reset();
        }
    });

    // New helper function to check for duplicate item names
    function isDuplicateItem(itemName) {
        const selectedCategory = menuData.find(cat => cat.id === currentCategoryId);
        return selectedCategory.items.some(item => item.name.toLowerCase() === itemName.toLowerCase());
    }

    // Single Price Form Submission - handled above in the backend integration section

    // Multi Price Form Submission
    multiPriceForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const itemName = itemNameInput_multi.value.trim();
        
        // Check for duplicate before adding
        if (isDuplicateItem(itemName)) {
            alert('An item with this name already exists in this category.');
            return;
        }
            
        const prices = [];
        const priceInputs = document.querySelectorAll('.price-variation-group');
        let hasEmptyInput = false;

        priceInputs.forEach(group => {
            const sizeInput = group.querySelector('.size-input');
            const priceInput = group.querySelector('.price-input');
                    
            if (sizeInput.value.trim() === '' || isNaN(parseFloat(priceInput.value))) {
                hasEmptyInput = true;
            }
                    
            prices.push({ size: sizeInput.value.trim(), price: parseFloat(priceInput.value) });
        });
            
        if (itemName && !hasEmptyInput && currentCategoryId) {
            const selectedCategory = menuData.find(cat => cat.id === currentCategoryId);
            const file = itemImageInput_multi.files[0];
            const reader = new FileReader();

            if (file) {
                reader.onload = (e) => {
                    const newItem = { id: Date.now().toString(), name: itemName, prices: prices, image: e.target.result };
                    selectedCategory.items.push(newItem);
                    saveData();
                    renderMenuItems(selectedCategory.items, selectedCategory.itemType);
                    renderCategories();
                    addItemModal.classList.remove('modal-active');
                };
                reader.readAsDataURL(file);
            } else {
                const newItem = { id: Date.now().toString(), name: itemName, prices: prices };
                selectedCategory.items.push(newItem);
                saveData();
                renderMenuItems(selectedCategory.items, selectedCategory.itemType);
                renderCategories();
                addItemModal.classList.remove('modal-active');
            }
        }
    });

    // Flavors Form Submission
    flavorsForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const itemName = itemNameInput_flavors.value.trim();
        
        // Check for duplicate before adding
        if (isDuplicateItem(itemName)) {
            alert('An item with this name already exists in this category.');
            return;
        }

        const flavors = [];
        let hasEmptyInput = false;

        const flavorInputs = document.querySelectorAll('#flavorsContainer .flavor-input');
        flavorInputs.forEach(input => {
            const flavorName = input.value.trim();
            if (!flavorName) {
                hasEmptyInput = true;
            }
            flavors.push({ name: flavorName });
        });
            
        if (itemName && !hasEmptyInput && currentCategoryId) {
            const selectedCategory = menuData.find(cat => cat.id === currentCategoryId);
            const file = itemImageInput_flavors.files[0];
            const reader = new FileReader();

            if (file) {
                reader.onload = (e) => {
                    const newItem = { id: Date.now().toString(), name: itemName, flavors: flavors, image: e.target.result };
                    selectedCategory.items.push(newItem);
                    saveData();
                    renderMenuItems(selectedCategory.items, selectedCategory.itemType);
                    renderCategories();
                    addItemModal.classList.remove('modal-active');
                };
                reader.readAsDataURL(file);
            } else {
                const newItem = { id: Date.now().toString(), name: itemName, flavors: flavors };
                selectedCategory.items.push(newItem);
                saveData();
                renderMenuItems(selectedCategory.items, selectedCategory.itemType);
                renderCategories();
                addItemModal.classList.remove('modal-active');
            }
        }
    });

    // Initial render on page load
    loadData();
});