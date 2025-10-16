<?php
session_start();
require_once 'includes/db.php';
require_once 'app/classes/ProfileHashManager.php';
require_once 'app/auth/HashBasedAuth.php';

$error_message = '';
$success_message = '';
$profileHashManager = new ProfileHashManager($pdo);
$auth = new HashBasedAuth($pdo);

// Check if user just logged out
if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
    $success_message = 'You have been successfully logged out. Your session is now secure.';
}

// If already logged in, redirect to dashboard
if ($auth->isCustomerAuthenticated()) {
    header("Location: customer_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            // Attempt to authenticate customer
            $stmt = $pdo->prepare("SELECT customerId, name, email, password, profile_hash FROM customer WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer && password_verify($password, $customer['password'])) {
                // Generate or get profile hash
                $profileHash = $customer['profile_hash'];
                if (!$profileHash) {
                    $profileHash = $profileHashManager->generateProfileHash($customer['customerId']);
                }
                
                // Create secure session using profile hash
                if ($auth->createCustomerSession($customer['customerId'], $profileHash)) {
                    // Log successful login
                    $profileHashManager->logProfileAccess($profileHash, 'login_success', [
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    header("Location: customer_dashboard.php");
                    exit();
                } else {
                    $error_message = "Session creation failed. Please try again.";
                }
            } else {
                $error_message = "Invalid email or password.";
                
                // Log failed login attempt
                if (isset($customer['profile_hash']) && $customer['profile_hash']) {
                    $profileHashManager->logProfileAccess($customer['profile_hash'], 'login_failed', [
                        'email' => $email,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'reason' => 'invalid_credentials'
                    ]);
                }
            }
        } catch (Exception $e) {
            $error_message = "Login failed. Please try again.";
            error_log("Customer login error: " . $e->getMessage());
        }
    } else {
        $error_message = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
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
        .alert {
            border-radius: 10px;
        }
        .brand-logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .security-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card position-relative">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt me-1"></i>Secure Login
                    </div>
                    
                    <div class="login-header">
                        <div class="brand-logo">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h2 class="mb-0">Esang Delicacies</h2>
                        <p class="mb-0 opacity-75">Customer Portal</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                       required autocomplete="email">
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="current-password">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Your Account
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p class="text-muted mb-2">Don't have an account?</p>
                            <a href="customer_register.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Your account is protected with advanced security
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>