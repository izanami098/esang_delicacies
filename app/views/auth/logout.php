<?php
require_once __DIR__ . '/../_bootstrap.php';

session_unset();
session_destroy();

// Redirect to the main Index.php in the public directory
header('Location: /esang_delicacies/public/Index.php');
exit;
?>
