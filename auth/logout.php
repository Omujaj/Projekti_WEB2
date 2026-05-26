<?php
require_once '../config/database.php';
require_once '../config/auth_helper.php';

if (isset($_SESSION['user_id'])) {
    logActivity('user_logout', "User '{$_SESSION['user_name']}' logged out.");
}

// Destroy all session data
$_SESSION = [];
session_destroy();

// Redirect to login page
header('Location: ../auth/login.php?logged_out=1');
exit();
