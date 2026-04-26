<?php
session_start();
require_once '../includes/error_handler.php';
require_once '../includes/data.php';
require_once '../classes/User.php'; 

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $emailPattern = "/^[a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/";
    
    if (!preg_match($emailPattern, $email)) {
        $error = "Invalid email format!";
    } elseif (empty($password)) {
        $error = "Please enter your password!";
    } else {
        if (array_key_exists($email, $users) && $users[$email]['password'] === $password) {
            
            if ($users[$email]['role'] === 'admin') {
                $loggedInUser = new Admin($users[$email]['name'], $email);
            } else {
                $loggedInUser = new Student($users[$email]['name'], $email);
            }
            
            $_SESSION['user_email'] = $loggedInUser->getEmail();
            $_SESSION['role'] = $loggedInUser->getRole();
            $_SESSION['name'] = $loggedInUser->getName();
            
            header("Location: ../pages/catalog.php");
            exit();
        } else {
            $error = "Incorrect email or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library </title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="auth-header">
        <span class="logo-icon">📚</span> 
        <h1>Library</h1>
        <p>Library Management System</p>
    </div>

    <div class="auth-container">
        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Sign in to your account</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="text" name="email" id="email" class="form-control" placeholder="lorida@student.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-dark" style="width: 100%;">Sign In</button>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Register here</a>
            <br><br>
            <a href="../index.php" style="color: var(--text-gray); font-weight: normal; font-size: 0.85rem;">← Back to Home</a>
        </div>
    </div>

</body>
</html>