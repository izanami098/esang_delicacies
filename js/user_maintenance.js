// Array to hold user data from database
let users = [];
let currentUserId = null;
let currentUserRole = null;
let isEditing = false;
        
// Get references to DOM elements
const userTableBody = document.getElementById('userTableBody');
const userModal = document.getElementById('userModal');
const modalTitle = document.getElementById('modalTitle');
const userForm = document.getElementById('userForm');
const confirmModal = document.getElementById('confirmModal');
const searchInput = document.getElementById('searchInput');
const roleSelect = document.getElementById('role');
const modulesContainer = document.getElementById('modulesContainer');

// Map roles to modules
const roleModulesMap = {
    'rider': ['order assignments', 'order status', 'profile'],
    'cashier': ['dashboard', 'invoices', 'profile'],
    'order manager': ['order management', 'product management', 'return order', 'order status', 'profile']
};

// Function to load users from database
async function loadUsers() {
    try {
        console.log('Loading users...');
        const response = await fetch('/esang_delicacies/public/api/get_staff_accounts.php', {
            method: 'GET',
            credentials: 'include' // Include cookies/session
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API result:', result);
        
        if (result.success) {
            users = result.data.map(user => ({
                ...user,
                modules: getModulesForRole(user.role)
            }));
            renderTable();
            console.log('Users loaded successfully:', users.length);
        } else {
            console.error('Failed to load users:', result.message);
            showNotification('Failed to load users: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showNotification('Error loading users: ' + error.message, 'error');
    }
}

// Function to render the user table
function renderTable(filter = '') {
    userTableBody.innerHTML = ''; // Clear existing rows
    const filteredUsers = users.filter(user =>
        user.username.toLowerCase().includes(filter.toLowerCase()) ||
        user.role.toLowerCase().includes(filter.toLowerCase()) ||
        user.modules.join(', ').toLowerCase().includes(filter.toLowerCase())
    );

    if (filteredUsers.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="7" class="text-center">No staff accounts found</td>
        `;
        userTableBody.appendChild(row);
        return;
    }

    filteredUsers.forEach(user => {
        const statusClass = user.status === 'active' ? 'active' : 'disabled';
        const actionButtonClass = user.status === 'active' ? 'disable' : 'enable';
        const actionButtonText = user.status === 'active' ? 'Disable' : 'Enable';
        const verifiedStatus = user.verified ? 'Verified' : 'Unverified';
        const verifiedClass = user.verified ? 'verified' : 'unverified';
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.username}</td>
            <td>${user.email || 'N/A'}</td>
            <td>${user.role}</td>
            <td>${user.modules.join(', ')}</td>
            <td class="status ${statusClass}">${user.status}</td>
            <td class="verified ${verifiedClass}">${verifiedStatus}</td>
            <td class="actions">
                <button class="action-btn edit" onclick="editUser(${user.id}, '${user.role}')" title="Edit">
                    Edit
                </button>
                <button class="action-btn ${actionButtonClass}" onclick="promptStatusChange(${user.id}, '${user.role}')" title="${actionButtonText}">
                    ${actionButtonText}
                </button>
            </td>
        `;
        userTableBody.appendChild(row);
    });
}

// Search functionality
searchInput.addEventListener('input', (e) => {
    renderTable(e.target.value);
});

// Function to get modules for a role
function getModulesForRole(role) {
    return roleModulesMap[role] || [];
}

// Function to generate modules checkboxes based on role
function generateModulesCheckboxes(role, selectedModules = []) {
    const modules = roleModulesMap[role] || [];
    modulesContainer.innerHTML = ''; // Clear previous checkboxes

    if (modules.length === 0) {
        modulesContainer.innerHTML = '<p>No modules assigned for this role.</p>';
        return;
    }

    modules.forEach(module => {
        const checkboxId = `module-${module.replace(/\s/g, '-')}`;
        const isChecked = selectedModules.includes(module);
        const checkboxHtml = `
            <div class="checkbox-group">
                <label for="${checkboxId}">
                    <input type="checkbox" id="${checkboxId}" name="modules" value="${module}" ${isChecked ? 'checked' : ''}>
                    ${module}
                </label>
            </div>
        `;
        modulesContainer.innerHTML += checkboxHtml;
    });
}

// Event listener for role change
roleSelect.addEventListener('change', (e) => {
    const selectedRole = e.target.value;
    generateModulesCheckboxes(selectedRole);
});

// Open the modal for create or add user
function openModal(action) {
    userForm.reset(); // Reset form fields
    isEditing = false;
    if (action === 'create' || action === 'add') {
        modalTitle.textContent = 'Create New User';
        // Set default role and generate checkboxes
        roleSelect.value = 'cashier';
        generateModulesCheckboxes('cashier');
    }
    userModal.style.display = 'flex'; // Use flex to center the modal
}

// Close the modal
function closeModal() {
    userModal.style.display = 'none';
}

// Handle form submission
userForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const role = roleSelect.value;
    
    // Get all checked modules
    const selectedModules = Array.from(document.querySelectorAll('input[name="modules"]:checked')).map(cb => cb.value);

    if (isEditing) {
        // Update existing user
        const user = users.find(u => u.id === currentUserId);
        if (user) {
            user.username = username;
            user.role = role;
            user.modules = selectedModules;
        }
    } else {
        // Create a new user
        const newUser = {
            id: users.length > 0 ? Math.max(...users.map(u => u.id)) + 1 : 1,
            username,
            role,
            modules: selectedModules,
            status: 'active'
        };
        users.push(newUser);
    }
    closeModal();
    renderTable(); // Re-render table with new data
});

// Function to edit a user
function editUser(id, role) {
    const user = users.find(u => u.id === id && u.role === role);
    if (user) {
        isEditing = true;
        currentUserId = id;
        currentUserRole = role;
        modalTitle.textContent = 'Edit User';
        document.getElementById('username').value = user.username;
        roleSelect.value = user.role;
        // Generate and pre-check checkboxes based on the user's modules
        generateModulesCheckboxes(user.role, user.modules);
        userModal.style.display = 'flex';
    }
}

// Show confirmation modal for status change
function promptStatusChange(id, role) {
    currentUserId = id;
    currentUserRole = role;
    const user = users.find(u => u.id === id && u.role === role);
    if (user) {
        if (user.status === 'active') {
            confirmMessage.textContent = `Are you sure you want to disable ${user.username} (${user.role})?`;
        } else {
            confirmMessage.textContent = `Are you sure you want to enable ${user.username} (${user.role})?`;
        }
        confirmModal.style.display = 'flex';
    }
}

// Handle confirmation for status change
async function confirmStatusChange(isConfirmed) {
    if (isConfirmed) {
        const user = users.find(u => u.id === currentUserId && u.role === currentUserRole);
        if (user) {
            const newStatus = user.status === 'active' ? 'inactive' : 'active';
            
            try {
                const response = await fetch('/esang_delicacies/public/api/toggle_staff_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        role: currentUserRole,
                        id: currentUserId,
                        status: newStatus
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Update local data
                    user.status = newStatus;
                    renderTable();
                    showNotification(`User ${user.username} ${newStatus === 'active' ? 'enabled' : 'disabled'} successfully`, 'success');
                } else {
                    showNotification('Failed to update user status: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error updating user status:', error);
                showNotification('Error updating user status', 'error');
            }
        }
    }
    confirmModal.style.display = 'none';
    currentUserId = null;
    currentUserRole = null;
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        max-width: 300px;
        word-wrap: break-word;
        ${type === 'success' ? 'background-color: #28a745;' : ''}
        ${type === 'error' ? 'background-color: #dc3545;' : ''}
        ${type === 'info' ? 'background-color: #17a2b8;' : ''}
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// Function to add sample staff accounts
async function addSampleStaff() {
    if (!confirm('This will add sample staff accounts to the database. Continue?')) {
        return;
    }
    
    try {
        const response = await fetch('/esang_delicacies/public/api/add_sample_staff.php');
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            // Reload users to show the new accounts
            loadUsers();
        } else {
            showNotification('Failed to add sample staff: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error adding sample staff:', error);
        showNotification('Error adding sample staff', 'error');
    }
}

// Function to debug session
async function debugSession() {
    try {
        const response = await fetch('/esang_delicacies/public/api/debug_session.php', {
            credentials: 'include'
        });
        const result = await response.json();
        console.log('Session debug:', result);
        alert('Session debug info logged to console. Check browser console for details.');
    } catch (error) {
        console.error('Debug session error:', error);
        alert('Debug session failed: ' + error.message);
    }
}

// Load users when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});