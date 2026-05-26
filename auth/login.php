<?php
require_once '../config/database.php';
require_once '../config/auth_helper.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') header('Location: ../admin/dashboard.php');
    elseif ($role === 'librarian') header('Location: ../librarian/borrow_requests.php');
    else header('Location: ../user/catalog.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $db = getDB();
        // Use prepared statement to prevent SQL injection
        $stmt = $db->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.is_active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role_name'];
            $_SESSION['user_email'] = $user['email'];

            logActivity('user_login', "User '{$user['name']}' logged in.");

            // Redirect based on role
            if ($user['role_name'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } elseif ($user['role_name'] === 'librarian') {
                header('Location: ../librarian/borrow_requests.php');
            } else {
                header('Location: ../user/catalog.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
            // Log failed attempt
            logActivity('login_failed', "Failed login attempt for email: $email");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LibraryViona</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="brand-icon">📚</div>
            <h1 class="brand-name">LibraryViona</h1>
            <p class="brand-tagline">Library Management System</p>
        </div>

        <div class="auth-card">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your account</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registration successful! You can now log in.</div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Sign In</button>
            </form>

            <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>

         
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
