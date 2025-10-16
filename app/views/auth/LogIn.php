<?php
require_once("session.php");
require_once __DIR__ . '/../../../config/environment.php';
require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/../../classes/ProfileHashManager.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_role = $_POST['login_role'] ?? 'customer';
    $login_input = trim($_POST['login_input'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login_input !== '' && $password !== '') {
        // Hardcoded admin login for demonstration
        if ($login_role === 'admin' && $login_input === 'capstoneesang@gmail.com' && $password === 'october29_mary') {
            $_SESSION['role'] = 'ADMIN';
            $_SESSION['adminId'] = 1;
            header('Location: ../../views/admin/admin_dashboard.php');
            exit;
        }
        
        // Handle rider authentication with existing RIDER table
        if ($login_role === 'rider') {
            $mysqli = db();
            
            // Check if RIDER table exists and use it directly
            $oldTableCheck = $mysqli->query("SHOW TABLES LIKE 'RIDER'");
            if ($oldTableCheck && $oldTableCheck->num_rows > 0) {
                $stmt = $mysqli->prepare("SELECT empId, name, email, verified, status FROM RIDER WHERE email = ? AND password = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('ss', $login_input, $password);
                    if ($stmt->execute()) {
                        $stmt->bind_result($riderId, $name, $email, $verified, $status);
                        if ($stmt->fetch()) {
                            $stmt->close();
                            if ($verified == 1) {
                                // Set session variables for rider
                                $_SESSION['role'] = 'RIDER';
                                $_SESSION['riderId'] = (int)$riderId;
                                $_SESSION['rider_id'] = (int)$riderId; // For compatibility
                                $_SESSION['email'] = $email;
                                $_SESSION['user_name'] = $name;
                                $_SESSION['user_type'] = 'rider';
                                
                                // Update rider status to active if status column exists
                                if ($status !== null) {
                                    $updateStmt = $mysqli->prepare("UPDATE RIDER SET status = 'active' WHERE empId = ?");
                                    if ($updateStmt) {
                                        $updateStmt->bind_param('i', $riderId);
                                        $updateStmt->execute();
                                        $updateStmt->close();
                                    }
                                }
                                
                                header('Location: ../rider/order_assignments.php');
                                exit;
                            } else {
                                $_SESSION["success"] = 2;
                                $_SESSION["message"] = "Account verification failed - please contact admin";
                                header("Refresh:0");
                                exit;
                            }
                        } else {
                            $stmt->close();
                            $error = 'Invalid credentials for rider.';
                        }
                    } else {
                        $stmt->close();
                        $error = 'Database query failed.';
                    }
                } else {
                    $error = 'Failed to prepare database statement.';
                }
            } else {
                $error = 'Rider system not available - RIDER table not found.';
            }
        }
        
        // Handle customer authentication with ProfileHashManager system
        elseif ($login_role === 'customer') {
            // Check if profile_hash column exists
            $hasProfileHashColumn = false;
            $mysqli = db();
            $checkResult = $mysqli->query("SHOW COLUMNS FROM customer LIKE 'profile_hash'");
            if ($checkResult && $checkResult->num_rows > 0) {
                $hasProfileHashColumn = true;
            }
            
            if ($hasProfileHashColumn) {
                // Use profile hash system if column exists
                $stmt = $mysqli->prepare("SELECT customerId, email, verified, profile_hash FROM customer WHERE email = ? AND password = ? LIMIT 1");
                $stmt->bind_param('ss', $login_input, $password);
                if ($stmt->execute()) {
                    $stmt->bind_result($foundId, $email, $verified, $profile_hash);
                    if ($stmt->fetch()) {
                        $stmt->close();
                        
                        if ($verified == 1) {
                            // Ensure customer has a profile hash
                            if (empty($profile_hash)) {
                                $profileHashManager = ProfileHashManager::getInstance();
                                $profile_hash = $profileHashManager->assignProfileHashToNewCustomer($foundId);
                            }
                            
                            // Create user session using ProfileHashManager
                            $profileHashManager = ProfileHashManager::getInstance();
                            $sessionData = $profileHashManager->createUserSession($profile_hash, 'CUSTOMER');
                            
                            // Set session variables for compatibility
                            $_SESSION['role'] = 'CUSTOMER';
                            $_SESSION['customerId'] = (int)$foundId;
                            $_SESSION['email'] = $email;
                            $_SESSION['profile_hash'] = $profile_hash;
                            $_SESSION['session_id'] = $sessionData['session_id'];
                            
                            // Set user name from customer data
                            $customerData = $profileHashManager->getCustomerByHash($profile_hash);
                            if ($customerData) {
                                $fullName = trim($customerData['first_name'] . ' ' . $customerData['last_name']);
                                $_SESSION['user_name'] = !empty($fullName) && $fullName !== ' ' ? $fullName : ($customerData['name'] ?? 'Customer');
                            }
                            
                            // Redirect to customer dashboard (use root level)
                            header('Location: ../../../customer_dashboard.php');
                            exit;
                        } else {
                            $_SESSION["success"] = 2;
                            $_SESSION["message"] = "Account verification failed";
                            header("Refresh:0");
                            exit;
                        }
                    }
                }
                $stmt->close();
                $error = 'Invalid credentials for customer.';
            } else {
                // Fallback to regular authentication if profile_hash column doesn't exist
                $stmt = $mysqli->prepare("SELECT customerId, email, verified FROM customer WHERE email = ? AND password = ? LIMIT 1");
                $stmt->bind_param('ss', $login_input, $password);
                if ($stmt->execute()) {
                    $stmt->bind_result($foundId, $email, $verified);
                    if ($stmt->fetch()) {
                        $stmt->close();
                        
                        if ($verified == 1) {
                            // Set basic session variables
                            $_SESSION['role'] = 'CUSTOMER';
                            $_SESSION['customerId'] = (int)$foundId;
                            $_SESSION['email'] = $email;
                            
                            // Get and set user name
                            try {
                                $nameStmt = $mysqli->prepare("SELECT first_name, last_name, name FROM customer WHERE customerId = ?");
                                $nameStmt->bind_param('i', $foundId);
                                $nameStmt->execute();
                                $nameResult = $nameStmt->get_result();
                                if ($nameRow = $nameResult->fetch_assoc()) {
                                    $fullName = trim($nameRow['first_name'] . ' ' . $nameRow['last_name']);
                                    $_SESSION['user_name'] = !empty($fullName) && $fullName !== ' ' ? $fullName : ($nameRow['name'] ?? 'Customer');
                                }
                                $nameStmt->close();
                            } catch (Exception $e) {
                                $_SESSION['user_name'] = 'Customer';
                            }
                            
                            // Show message about database setup needed
                            $_SESSION["success"] = 1;
                            $_SESSION["message"] = "Login successful! Please run the database setup to enable profile hash features.";
                            header('Location: ../../../customer_dashboard.php');
                            exit;
                        } else {
                            $_SESSION["success"] = 2;
                            $_SESSION["message"] = "Account verification failed";
                            header("Refresh:0");
                            exit;
                        }
                    }
                }
                $stmt->close();
                $error = 'Invalid credentials for customer.';
            }
        }

        // Handle other roles (admin, cashier, order_manager) with old system
        $mysqli = db();
        $roles = [
            'admin' => ['table' => 'ADMIN', 'id' => 'empId', 'field' => 'name', 'redirect' => '../../views/admin/admin_dashboard.php', 'sessionKey' => 'adminId'],
            'cashier' => ['table' => 'CASHIER', 'id' => 'empId', 'field' => 'name', 'redirect' => '../../views/cashier/cashier_walk_in.php', 'sessionKey' => 'cashierId'],
            'order_manager' => ['table' => 'ORDER_MANAGER', 'id' => 'empId', 'field' => 'name', 'redirect' => '../../views/order_manager/order_management.php', 'sessionKey' => 'orderManagerId'],
        ];

        // Only process other roles here (not customer or rider)
        if (isset($roles[$login_role])) {
            $role = $roles[$login_role];
            // Regular authentication for other roles (admin, cashier, order_manager)
            $stmt = $mysqli->prepare("SELECT {$role['id']}, email, verified FROM {$role['table']} WHERE {$role['field']} = ? AND password = ? LIMIT 1");
            $stmt->bind_param('ss', $login_input, $password);
            if ($stmt->execute()) {
                $stmt->bind_result($foundId, $email, $verified);
                if ($stmt->fetch()) {
                    $_SESSION['role'] = strtoupper($login_role);
                    $_SESSION[$role['sessionKey']] = (int)$foundId;
                    $_SESSION['email'] = $email;
                    $stmt->close();
                    if ($verified == 1) {
                        header('Location: ' . $role['redirect']);
                    } else {
                        $_SESSION["success"] = 2;
                        $_SESSION["message"] = "Account verification failed";
                        header("Refresh:0");
                    }
                    exit;
                }
            }
        }
        
        $error = 'Invalid credentials for selected role.';
    } else {
        $error = 'Please enter all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Log In</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #fffdf9; }
    .btn-warning { background-color: #ffcc00; border: none; }
    .btn-warning:hover { background-color: #e6b800; }
    .text-danger { color: #d32f2f !important; }
  </style>
</head>
<body>

<div class="container-fluid">
  <div class="row min-vh-100">

    <!-- Left: Login Form -->
    <div class="col-lg-6 d-flex align-items-center justify-content-center bg-white p-4">
      <div class="w-100" style="max-width: 400px;">
        <h1 class="text-center mb-4 fw-bold text-danger">Log In</h1>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php 

          $flash = $_SESSION['flash'] ?? null;
          if ($flash) {
              $type = $flash['type'] === 'success' ? 'success' : 'danger';
              $msg  = $flash['msg'] ?? '';

              if ($msg !== '') {
                  echo '<div class="alert alert-' . $type . ' text-center">'
                    . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')
                    . '</div>';
              }

              unset($_SESSION['flash']);
          }
        ?>
        
        <?php 
        $success = $_SESSION['success'] ?? null;   // 1 = success, 2 = error, null = nothing
        $message = $_SESSION['message'] ?? '';
        
        if ($success === 1): ?>
          <div class="alert alert-success text-center"><?= htmlspecialchars($message) ?></div>
        <?php elseif ($success === 2): ?>
          <div class="alert alert-danger text-center"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php
        unset($_SESSION['success'], $_SESSION['message']);
        ?>
        <form method="POST" action="" class="row g-3">
          <div class="col-12">
            <label for="login_role" class="form-label fw-bold">Select Your Role</label>
            <select name="login_role" id="login_role" class="form-select" required aria-label="Select your login role">
              <option value="customer">Customer (Email)</option>
              <option value="rider">Rider (Email)</option>
              <option value="cashier">Cashier (Name)</option>
              <option value="admin">Admin (Name)</option>
              <option value="order_manager">Order Manager (Name)</option>
            </select>
          </div>
          <div class="col-12">
            <input type="text" name="login_input" id="login_input" class="form-control" placeholder="Email or Name" required>
          </div>
          <div class="col-12">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
          <div class="col-12 d-grid">
            <button type="submit" class="btn btn-warning fw-bold py-2">Log In</button>
          </div>
        </form>

        <p class="text-center mt-3">
          No account? <a href="../auth/SignUp.php" class="text-danger fw-bold">Sign Up</a>
        </p>
      </div>
    </div>

    <!-- Right: Image -->
    <div class="col-lg-6 d-none d-lg-block p-0">
        <img src="../VImages/Food Poster.png" 
            alt="Esang Delicacies" 
            class="w-100 p-1" 
            style="height: 100vh; object-fit: center; object-position: center;">
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Dynamic placeholder update
document.getElementById('login_role').addEventListener('change', function() {
  var input = document.getElementById('login_input');
  if (this.value === 'customer' || this.value === 'rider') {
    input.placeholder = 'Email';
  } else {
    input.placeholder = 'Name';
  }
});
</script>
</body>
</html>
