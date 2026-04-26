<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$project_root = str_replace('\\', '/', dirname(__DIR__));
$base_url = str_replace($doc_root, '', $project_root);
?>

<nav class="navbar">
    <div class="nav-left">
        <a href="<?php echo $base_url; ?>/index.php" class="logo" style="text-decoration: none;">
            📚 <span style="color: #ffb703;">Library</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="<?php echo $base_url; ?>/pages/catalog.php" class="active">Browse Books</a></li>
            
            <?php if(isset($_SESSION['role'])): ?>
                <?php if($_SESSION['role'] === 'user' || $_SESSION['role'] === 'student'): ?>
                    <li><a href="<?php echo $base_url; ?>/pages/my_borrows.php">My Borrows</a></li>
                <?php elseif($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?php echo $base_url; ?>/pages/reservations.php">Reservations</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="nav-right">
        <?php if(isset($_SESSION['user_email'])): ?>
            <?php $badgeClass = ($_SESSION['role'] === 'admin') ? 'admin' : 'student'; ?>
            <?php $badgeText = ($_SESSION['role'] === 'admin') ? 'ADMIN' : 'STUDENT'; ?>
            
            <span class="role-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            
            <a href="<?php echo $base_url; ?>/auth/logout.php" class="btn-logout">Logout</a>
        <?php else: ?>
            <a href="<?php echo $base_url; ?>/auth/login.php" style="color: white; text-decoration: none; font-family: 'DM Sans', sans-serif; font-weight: 500;">Login</a>
            <a href="<?php echo $base_url; ?>/auth/register.php" style="background-color: #ffb703; color: #0B192C; padding: 6px 18px; border-radius: 4px; text-decoration: none; font-family: 'DM Sans', sans-serif; font-weight: 700; margin-left: 15px;">Register</a>
        <?php endif; ?>
    </div>
</nav>