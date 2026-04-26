<?php
session_start();

require_once '../includes/error_handler.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $namePattern = "/^[a-zA-Z\s]{3,}$/";
    
    $emailPattern = "/^[a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/";
    
    $phonePattern = "/^\+?[0-9\s\-]{7,15}$/";


    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    $error = "All fields marked with an asterisk () are required!";
} 
    elseif (!preg_match($namePattern, $name)) {
    $error = "Name must contain only letters (min. 3 characters).";
}   elseif (!preg_match($emailPattern, $email)) {
    $error = "Invalid email format!";
}   elseif (!empty($phone) && !preg_match($phonePattern, $phone)) {
    $error = "Invalid phone number format! (e.g., +383 44 123 456)";
}   elseif (strlen($password) < 6) {
    $error = "Password must be at least 6 characters long.";
}   elseif ($password !== $confirm_password) {
    $error = "Passwords do not match!";
}   else {
    
    $success = "Account created successfully! You can now log in.";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

    <div class="auth-header">
        <span class="logo-icon">📚</span> 
        <h1>Library</h1>
        <p>Create your account</p>
    </div>

    <div class="auth-container">
        <h2>Create Account</h2>
        <p class="auth-subtitle">Join the library community</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="name">Full Name <span class="required-star"></span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="John Smith" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="required-star"></span></label>
                <input type="text" name="email" id="email" class="form-control" placeholder="lori@admin.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" class="form-control" placeholder="+1 555 000 0000" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password <span class="required-star"></span></label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="required-star">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password">
            </div>
            
            <button type="submit" class="btn-dark" style="width: 100%;">Create Account</button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="login.php">Sign in</a>
            <br><br>
            <a href="../index.php" style="color: var(--text-gray); font-weight: normal; font-size: 0.85rem;">← Back to Home</a>
        </div>
    </div>

</body>
</html> 