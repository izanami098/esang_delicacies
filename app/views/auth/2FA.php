<?php 
require_once("session.php");
require_once("database_config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require('PHPMailer/Exception.php');
require('PHPMailer/SMTP.php');
require('PHPMailer/PHPMailer.php');
$mail = new PHPMailer(true);

$message = "";
$error = 0;

$resetcode = substr(str_shuffle(str_repeat($x='0123456789', ceil(6/strlen($x)) )),1,6);
if (empty($_SESSION['otp_sent'])) {
    sendEmail($_SESSION['email'], $resetcode, $con, $mail);
    $_SESSION['otp_sent'] = true;
}

function sendEmail($email, $resetcode, $con, $mail) {
    $email = $_SESSION['email'];
    $role  = $_SESSION['role'];
    $queryVar = '';

    switch ($role) {
        case 'admin':
            $queryVar = 'admin';
            break;
        case 'cashier':
            $queryVar = 'cashier';
            break;
        case 'rider':
            $queryVar = 'rider';
            break;
        case 'order_manager':
            $queryVar = 'order_manager';
            break;
        case 'customer':
            $queryVar = 'customer';
            break;
        
        default:
            break;
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM {$queryVar} WHERE email = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $row = $result->fetch_assoc();
                $sendTO = $row['email'];
                try {
                    $mail->isSMTP();                                           
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'ecarenet.ph@gmail.com';
                    $mail->Password   = 'yqlbnhwjwffljesz';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->setFrom('ecarenet.ph@gmail.com', 'Esang Delicacies - No Reply');
                    $mail->addAddress($sendTO);
                    $mail->isHTML(true);
                    $mail->Subject = '2FA Code';
                    $mail->Body = "
                        <p>Good day!</p>
                        <p>Your 2FA code is: <strong style='color:#007bff;'>$resetcode</strong></p>
                        <p>Please do not share this code with anyone.</p>";
                    if ($mail->send()) {
                        $update = mysqli_query($con, "UPDATE {$queryVar} SET otp = '$resetcode' WHERE email = '$email'");
                        if ($update) {
                            $_SESSION['otp'] = $_SESSION['email'];
                        }
                    }
                } catch (Exception $e) {}
            }
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['ver'])) {
    $code  = trim($_POST['otp']);
    $email = strtolower(trim($_SESSION['email']));
    $role  = strtolower($_SESSION['role']); 

    $map = [
        'admin'         => ['table' => 'ADMIN',         'emailCol' => 'email'],
        'cashier'       => ['table' => 'CASHIER',       'emailCol' => 'email'],
        'rider'         => ['table' => 'RIDER',         'emailCol' => 'email'],
        'order_manager' => ['table' => 'ORDER_MANAGER', 'emailCol' => 'email'],
        'customer'      => ['table' => 'CUSTOMER',      'emailCol' => 'email']
    ];
    $role = strtolower($_SESSION['role']);
    $table = $map[$role]['table'];
    $emailCol = $map[$role]['emailCol'];

    // Debug values
    echo "Debug => Email: '$email', Code: '$code', Table: $table<br>";

    // Manual check
    $debug = mysqli_query($con, "SELECT * FROM {$table} WHERE email='$email' AND otp='$code'");
    echo "Manual rows found: " . mysqli_num_rows($debug) . "<br>";

    // Prepared statement
    $stmt = mysqli_prepare($con, "SELECT * FROM {$table} WHERE {$emailCol} = ? AND otp = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $code);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $user = mysqli_fetch_assoc($result);
                if ($user) {
                    echo "✅ User found<br>";
                    $update = mysqli_query($con, "UPDATE {$table} SET verified = 1 WHERE email = '$email'");
                    if ($update && mysqli_affected_rows($con) > 0) {
                        $_SESSION["success"] = 1;
                        $_SESSION["message"] = "Verification complete you may now login to you account.";
                        header("Location: /esang_delicacies/app/views/auth/LogIn.php");
                        exit;
                    } else {
                        $_SESSION["message"] = "Code correct but update failed (check column verified).";
                    }
                } else {
                    echo "❌ No user matched prepared statement<br>";
                    $_SESSION["message"] = "Invalid Code";
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Esang Delicacies - 2FA</title>
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

    <!-- Left: 2FA Form -->
    <div class="col-lg-6 d-flex align-items-center justify-content-center bg-white p-4">
      <div class="w-100" style="max-width: 450px;">
        <h1 class="text-center mb-4 fw-bold text-danger">Account Verification</h1>
        <h6 class="text-center mb-4">Enter the 2FA code we sent to your email: <strong><?php echo $_SESSION['email'] ?></strong></h6>

        <?php if ($error==1): ?>
          <div class="alert alert-success text-center"><?php echo $_SESSION["message"] ?></div>
        <?php elseif($error==2): ?>
          <div class="alert alert-danger text-center"><?php echo $_SESSION["message"] ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="row g-3">
          <div class="col-12">
            <div class="form-group">
                <input type="text" class="form-control form-control-lg" name="otp" id="exampleInputEmail1" placeholder="2FA Code" required>
            </div>
          </div>
          <div class="col-12 d-grid mt-3">
            <input type="submit" name="ver" class="btn btn-warning fw-bold py-2" value="Verify Account">
          </div>
          <p class="text-center mt-3">Can't access this email? <a href="SignUp.php" class="text-danger fw-bold">Sign Up</a> again.</p>
        </form>
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
</body>
</html>
