<?php
require_once '../config/database.php';
require_once '../config/auth_helper.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../user/catalog.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one uppercase letter and one number.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $stmt->close();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            // Default role is student (role_id is 3)
            $stmt = $db->prepare("INSERT INTO users (role_id, name, email, password, phone) VALUES (3, ?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $phone);
            if ($stmt->execute()) {
                logActivity('user_registered', "New user registered: $email");
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — LibraryViona</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <div class="brand-icon">📚</div>
            <h1 class="brand-name">LibraryViona</h1>
            <p class="brand-tagline">Create your account</p>
        </div>
        <div class="auth-card">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Join the library community</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" novalidate>
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" placeholder="John Smith"
                           value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" placeholder="your@email.com"
                           value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="+1 555 000 0000"
                           value="<?= sanitize($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Min. 8 chars, 1 uppercase, 1 number" required>
                    <div class="password-strength" id="strengthBar"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Create Account</button>
            </form>
            <p class="auth-link">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
