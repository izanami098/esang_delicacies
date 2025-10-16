<?php
/**
 * Rider Login Page
 * Handles rider authentication and redirects to dashboard
 */

session_start();
require_once 'includes/db.php';
require_once 'app/auth/HashBasedAuth.php';

$auth = new HashBasedAuth($pdo);

// If already logged in, redirect to dashboard
if ($auth->isRiderAuthenticated()) {
    header('Location: rider_dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $result = $auth->loginRider($email, $password);
            if ($result['success']) {
                header('Location: rider_dashboard.php');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        // Handle registration
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $vehicle_type = $_POST['vehicle_type'] ?? 'motorcycle';
        $vehicle_plate = trim($_POST['vehicle_plate'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
        
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            $registerData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => $password,
                'vehicle_type' => $vehicle_type,
                'vehicle_plate' => $vehicle_plate,
                'license_number' => $license_number
            ];
            
            $result = $auth->registerRider($registerData);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Login - Esang Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .auth-form {
            padding: 3rem;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header h2 {
            color: #333;
            font-weight: 600;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            border-color: #667eea;
            color: #667eea;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-outline-primary:hover {
            background: #667eea;
            border-color: #667eea;
        }
        .nav-tabs .nav-link {
            border: none;
            color: #667eea;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            color: #495057;
            border-bottom: 3px solid #667eea;
            background: transparent;
        }
        .brand-logo {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }
        .brand-logo:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-container">
                    <div class="auth-form">
                        <!-- Header -->
                        <div class="auth-header">
                            <a href="index.php" class="brand-logo text-decoration-none">
                                <i class="fas fa-motorcycle"></i> Esang Delicacies
                            </a>
                            <h2 class="mt-3">Rider Portal</h2>
                            <p class="text-muted">Join our delivery team and start earning</p>
                        </div>

                        <!-- Error/Success Messages -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs mb-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                    <i class="fas fa-user-plus"></i> Apply to Ride
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="authTabContent">
                            <!-- Login Tab -->
                            <div class="tab-pane fade show active" id="login" role="tabpanel">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="login">
                                    
                                    <div class="mb-3">
                                        <label for="loginEmail" class="form-label">
                                            <i class="fas fa-envelope"></i> Email Address
                                        </label>
                                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="loginPassword" class="form-label">
                                            <i class="fas fa-lock"></i> Password
                                        </label>
                                        <input type="password" class="form-control" id="loginPassword" name="password" required>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        New rider? <a href="#register" data-bs-toggle="tab">Apply to join our team</a>
                                    </small>
                                </div>
                            </div>

                            <!-- Register Tab -->
                            <div class="tab-pane fade" id="register" role="tabpanel">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="register">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="registerName" class="form-label">
                                                <i class="fas fa-user"></i> Full Name *
                                            </label>
                                            <input type="text" class="form-control" id="registerName" name="name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="registerEmail" class="form-label">
                                                <i class="fas fa-envelope"></i> Email Address *
                                            </label>
                                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="registerPhone" class="form-label">
                                                <i class="fas fa-phone"></i> Phone Number *
                                            </label>
                                            <input type="tel" class="form-control" id="registerPhone" name="phone" placeholder="09xxxxxxxxx" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="registerPassword" class="form-label">
                                                <i class="fas fa-lock"></i> Password *
                                            </label>
                                            <input type="password" class="form-control" id="registerPassword" name="password" minlength="6" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="vehicleType" class="form-label">
                                                <i class="fas fa-motorcycle"></i> Vehicle Type
                                            </label>
                                            <select class="form-control" id="vehicleType" name="vehicle_type">
                                                <option value="motorcycle">Motorcycle</option>
                                                <option value="bicycle">Bicycle</option>
                                                <option value="car">Car</option>
                                                <option value="scooter">Scooter</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="vehiclePlate" class="form-label">
                                                <i class="fas fa-id-card"></i> Plate Number
                                            </label>
                                            <input type="text" class="form-control" id="vehiclePlate" name="vehicle_plate" placeholder="ABC-1234">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="licenseNumber" class="form-label">
                                            <i class="fas fa-id-card-alt"></i> Driver's License Number
                                        </label>
                                        <input type="text" class="form-control" id="licenseNumber" name="license_number">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                            <label class="form-check-label" for="agreeTerms">
                                                I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i> Submit Application
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        Already have an account? <a href="#login" data-bs-toggle="tab">Login here</a>
                                    </small>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> Your application will be reviewed by our team. 
                                    You'll receive an email notification once approved.
                                </div>
                            </div>
                        </div>

                        <!-- Footer Links -->
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none text-muted">
                                <i class="fas fa-arrow-left"></i> Back to Main Site
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Phone number formatting
        document.getElementById('registerPhone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            e.target.value = value;
        });

        // Password strength indicator
        document.getElementById('registerPassword').addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = getPasswordStrength(password);
            // You can add visual feedback here
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            return strength;
        }

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>