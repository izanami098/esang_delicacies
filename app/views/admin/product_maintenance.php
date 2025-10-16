<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="icon" type="image/x-icon" href="../VImages/favicon.jpg">
    <link rel="stylesheet" href="../VCSS/Common_CSS/sidebar.css">
    <link rel="stylesheet" href="../VCSS/Admin_CSS/product_maintenance.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidenav" id="mySidenav">
        <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">&times;</a>
        <div class="profile">
            <div class="profile-pic">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-name"><span>Admin</span></div>
        </div>
        <a href="admin_dashboard.html" class="sidenav-item"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="#" class="sidenav-item"><i class="fas fa-clipboard-list"></i> Order status</a>
        <div class="dropdown">
            <a href="#" class="sidenav-item dropdown-btn"><i class="fas fa-box"></i> Product Maintenance <i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
                <a href="product_maintenance.php">&bull; add item</a>
                <a href="manage_items.php">&bull; manage item</a>
                <a href="#">&bull; coupons</a>
            </div>
        </div>
        <a href="#" class="sidenav-item"><i class="fas fa-users"></i> User Maintenance</a>
        <a href="admin_performance.html" class="sidenav-item"><i class="fas fa-file-alt"></i> Analytics & Sales and Transaction History</a>
        <a href="#" class="sidenav-item" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>

    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Log Out</h2>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button id="cancelLogout" class="button cancel">Cancel</button>
                <button id="confirmLogout" class="button logout">Log Out</button>
            </div>
        </div>
    </div>

    <div class="main-container">
        <span class="openbtn" onclick="openNav()">&#9776;</span>
        <div class="content-layout">

            <div class="panel">
                <h2>Menu Categories</h2>

                <form id="addCategoryForm" class="form-flex">
                    <div class="input-group">
                        <input type="text" id="categoryNameInput" placeholder="Enter new category name" class="form-control" required>
                        <button type="submit" class="btn btn-primary">
                            Add
                        </button>
                    </div>
                    <div class="select-group">
                        <label for="itemTypeSelect" class="form-label">Item Type:</label>
                        <select id="itemTypeSelect" class="form-control">
                            <option value="single-price">Single Price</option>
                            <option value="multi-price">Multiple Prices (e.g. sizes)</option>
                            <option value="flavors">Flavors</option>
                        </select>
                    </div>
                </form>

                <div id="categoryList" class="list-container">
                    </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h2 id="menuItemsTitle">Menu Items</h2>
                    <button id="addItemButton" class="btn btn-success" style="display: none;">
                        Add Item
                    </button>
                </div>

                <div id="menuItemList" class="list-container">
                    <p class="item-prompt" id="selectCategoryPrompt">Select a category to view its items.</p>
                    </div>
            </div>

        </div>
    </div>

    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Menu Item</h3>
                <button id="closeModalButton" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            <form id="singlePriceForm" style="display: none;">
                <div class="form-group">
                    <label for="itemNameInput_single" class="form-label">Item Name</label>
                    <input type="text" id="itemNameInput_single" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="itemPriceInput_single" class="form-label">Price (₱)</label>
                    <input type="number" id="itemPriceInput_single" step="0.01" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="itemImageInput_single" class="form-label">Item Picture</label>
                    <input type="file" id="itemImageInput_single" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Add Item
                </button>
            </form>

            <form id="multiPriceForm" style="display: none;">
                <div class="form-group">
                    <label for="itemNameInput_multi" class="form-label">Item Name</label>
                    <input type="text" id="itemNameInput_multi" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price Variations (Size and Price)</label>
                    <div id="priceVariationsContainer">
                        <div class="price-variation-group">
                            <select class="size-input form-control" required>
                                <option value="" disabled selected>Select Size</option>
                                <option value="12 inches">12 inches</option>
                                <option value="14 inches">14 inches</option>
                                <option value="16 inches">16 inches</option>
                                <option value="18 inches">18 inches</option>
                            </select>
                            <input type="number" step="0.01" class="price-input form-control" placeholder="Price (₱)" required>
                            <button type="button" class="delete-icon-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-circle">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="m15 9-6 6"/>
                                    <path d="m9 9 6 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="addPriceButton" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        Add Size
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="itemImageInput_multi" class="form-label">Item Picture</label>
                    <input type="file" id="itemImageInput_multi" class="form-control" accept="image/*">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Add Item
                </button>
            </form>

            <form id="flavorsForm" style="display: none;">
                <div class="form-group">
                    <label for="itemNameInput_flavors" class="form-label">Item Name</label>
                    <input type="text" id="itemNameInput_flavors" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="itemImageInput_flavors" class="form-label">Item Picture</label>
                    <input type="file" id="itemImageInput_flavors" class="form-control" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Flavors</label>
                    <div id="flavorsContainer">
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
                    </div>
                    <button type="button" id="addFlavorButton" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        Add Flavor
                    </button>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Add Item
                </button>
            </form>
        </div>
    </div>
    <script src="../VJavaScript/sidebar.js"></script>
    <script src="../VJavaScript/product_maintenance.js"></script>
</body>
</html>