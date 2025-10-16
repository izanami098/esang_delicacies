<?php
session_start();
require_once 'includes/db.php';
require_once 'app/classes/ProfileHashManager.php';
require_once 'app/auth/HashBasedAuth.php';

// STRICT: Check if customer is authenticated via profile hash
$auth = new HashBasedAuth($pdo);
if (!$auth->isCustomerAuthenticated()) {
    header("Location: customer_login.php");
    exit();
}

// Get authenticated customer data - ONLY for this specific profile
$customerData = $auth->getAuthenticatedCustomer();
$profileHashManager = new ProfileHashManager($pdo);

// Extract customer information
$customerId = $customerData['customer_id'];
$profileHash = $customerData['profile_hash'];

// Log profile access for security audit
$profileHashManager->logProfileAccess($profileHash, 'profile_accessed', [
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
                $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
                
                if (!$name || !$email) {
                    throw new Exception('Name and email are required.');
                }
                
                // Check if email is already taken by another customer
                $emailCheckStmt = $pdo->prepare("SELECT customerId FROM customer WHERE email = ? AND customerId != ?");
                $emailCheckStmt->execute([$email, $customerId]);
                if ($emailCheckStmt->fetch()) {
                    throw new Exception('Email address is already in use by another account.');
                }
                
                // Update customer profile - ONLY for this customer
                $updateStmt = $pdo->prepare("
                    UPDATE customer 
                    SET name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() 
                    WHERE customerId = ?
                ");
                $success = $updateStmt->execute([$name, $email, $phone, $address, $customerId]);
                
                if ($success && $updateStmt->rowCount() > 0) {
                    // Log profile update
                    $profileHashManager->logProfileAccess($profileHash, 'profile_updated', [
                        'fields_updated' => ['name', 'email', 'phone', 'address'],
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                    
                    // Refresh customer data
                    $customerData = $auth->getAuthenticatedCustomer();
                } else {
                    throw new Exception('Failed to update profile. No changes were made.');
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (!$currentPassword || !$newPassword || !$confirmPassword) {
                    throw new Exception('All password fields are required.');
                }
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New password and confirmation do not match.');
                }
                
                if (strlen($newPassword) < 6) {
                    throw new Exception('New password must be at least 6 characters long.');
                }
                
                // Verify current password
                $passwordStmt = $pdo->prepare("SELECT password FROM customer WHERE customerId = ?");
                $passwordStmt->execute([$customerId]);
                $currentHashedPassword = $passwordStmt->fetchColumn();
                
                if (!password_verify($currentPassword, $currentHashedPassword)) {
                    throw new Exception('Current password is incorrect.');
                }
                
                // Update password - ONLY for this customer
                $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordStmt = $pdo->prepare("UPDATE customer SET password = ?, updated_at = NOW() WHERE customerId = ?");
                $success = $updatePasswordStmt->execute([$newHashedPassword, $customerId]);
                
                if ($success && $updatePasswordStmt->rowCount() > 0) {
                    // Log password change
                    $profileHashManager->logProfileAccess($profileHash, 'password_changed', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                } else {
                    throw new Exception('Failed to update password.');
                }
                break;
                
            default:
                throw new Exception('Invalid action.');
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get fresh customer data
try {
    $customerStmt = $pdo->prepare("
        SELECT customerId, name, email, phone, address, created_at, updated_at, profile_hash 
        FROM customer 
        WHERE customerId = ?
    ");
    $customerStmt->execute([$customerId]);
    $customerProfile = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customerProfile) {
        throw new Exception('Customer profile not found.');
    }
} catch (Exception $e) {
    header("Location: customer_logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .profile-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
        }
        .nav-tabs {
            border-bottom: none;
            background: #f8f9fa;
            padding: 1rem;
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 10px;
            margin-right: 0.5rem;
            color: #6c757d;
        }
        .nav-tabs .nav-link.active {
            background: #667eea;
            color: white;
        }
        .tab-content {
            padding: 2rem;
        }
        .security-info {
            background: #e8f5e8;
            border: 1px solid #d4edda;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6c5ce7 100%);
        }
        .profile-stats {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .stat-item {
            text-align: center;
            color: rgba(255,255,255,0.9);
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="customer_dashboard.php">
                <i class="fas fa-utensils me-2"></i>Esang Delicacies
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="customer_dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
                <a class="nav-link" href="customer_logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <div class="card profile-card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="mb-1"><?php echo htmlspecialchars($customerProfile['name']); ?></h2>
                <p class="mb-0 opacity-75"><?php echo htmlspecialchars($customerProfile['email']); ?></p>
                
                <div class="profile-stats">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo substr($profileHash, 0, 6); ?></span>
                                <span class="stat-label">Profile ID</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo date('M Y', strtotime($customerProfile['created_at'])); ?></span>
                                <span class="stat-label">Member Since</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-item">
                                <span class="stat-number">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <span class="stat-label">Secure Account</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> m-3" role="alert">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Security Info -->
            <div class="m-3">
                <div class="security-info">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                <i class="fas fa-shield-alt text-success me-2"></i>Account Security Status
                            </h6>
                            <small class="text-muted">
                                Your profile is protected with unique hash-based security. 
                                Only you can access and modify your account data.
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success">
                                <i class="fas fa-check me-1"></i>Protected
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-info" type="button">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#change-password" type="button">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Profile Information Tab -->
                <div class="tab-pane fade show active" id="profile-info" role="tabpanel">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-2"></i>Full Name
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($customerProfile['name']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($customerProfile['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Phone Number
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($customerProfile['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="profile_hash" class="form-label">
                                    <i class="fas fa-fingerprint me-2"></i>Profile Hash (Read-Only)
                                </label>
                                <input type="text" class="form-control" value="<?php echo substr($profileHash, 0, 16); ?>..." readonly>
                                <div class="form-text">Your unique security identifier</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Address
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($customerProfile['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="change-password" role="tabpanel">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-key me-2"></i>Current Password
                            </label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>New Password
                            </label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   minlength="6" required>
                            <div class="form-text">Password must be at least 6 characters long</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Confirm New Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-shield-alt me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Password Security Tips</h6>
                            <ul class="mb-0">
                                <li>Use a mix of uppercase and lowercase letters</li>
                                <li>Include numbers and special characters</li>
                                <li>Avoid using personal information</li>
                                <li>Make it at least 8 characters long</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>