<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_role = $_POST['user_role'];

    // To do: Add validation + save user in database here

    // Redirect customer to OTP page
    if ($user_role === "customer") {
        header("Location: ../auth/OTP.php");
        exit();
    } else {
        // For other roles, redirect to login or dashboard
        header("Location: ../auth/LogIn.php");
        exit();
    }
}
?>
