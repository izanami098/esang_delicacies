<?php
/**
 * Example Order Manager Login
 * 
 * This is an example of how to set up a user session with order_manager role.
 * You should integrate this with your existing authentication system.
 */

session_start();
require_once 'app/config/database.php';

// Example: Setting up an order manager session
// In your actual login system, you would verify credentials from database

function loginOrderManager($userId, $userName, $userEmail) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['user_role'] = 'order_manager';  // This is the key role
    $_SESSION['login_time'] = time();
    
    // Log the login for security
    error_log("Order Manager logged in: $userName (ID: $userId) at " . date('Y-m-d H:i:s'));
    
    return true;
}

// Example usage - you would replace this with your actual login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // In a real system, you'd verify these credentials against your database
    // and check if the user has order_manager role
    
    try {
        $db = Database::getConnection();
        
        // Example query - adjust to match your user table structure
        $stmt = $db->prepare("
            SELECT user_id, name, email, role 
            FROM users 
            WHERE username = ? AND password = ? AND role = 'order_manager' AND status = 'active'
        ");
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]); // Use proper password hashing
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            loginOrderManager($user['user_id'], $user['name'], $user['email']);
            header('Location: admin_order_management.php');
            exit();
        } else {
            $error = "Invalid credentials or insufficient permissions. Order Manager role required.";
        }
        
    } catch (Exception $e) {
        error_log("Order Manager login error: " . $e->getMessage());
        $error = "Login system error. Please try again.";
    }
}

// Quick demo setup (REMOVE THIS IN PRODUCTION)
if (isset($_GET['demo']) && $_GET['demo'] === 'setup') {
    loginOrderManager(1, 'Demo Order Manager', 'manager@esang.com');
    header('Location: admin_order_management.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Manager Login - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .demo-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-clipboard-list fa-2x mb-2"></i>
            <h3>Order Manager Login</h3>
            <p class="mb-0 opacity-75">Access Order Management System</p>
        </div>
        
        <div class="p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Demo Setup (REMOVE IN PRODUCTION) -->
            <div class="demo-alert">
                <strong>Demo Setup:</strong> 
                <a href="?demo=setup" class="btn btn-sm btn-warning ms-2">
                    Quick Demo Login
                </a>
                <small class="d-block mt-1 text-muted">
                    This sets up a demo order manager session. Remove this in production.
                </small>
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-1"></i>Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-1"></i>Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Login as Order Manager
                </button>
            </form>
            
            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Order Manager role required for access
                </small>
            </div>
        </div>
    </div>
</body>
</html>