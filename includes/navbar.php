<?php
/**
 * Navigation Bar
 * 
 */
$currentUser = getCurrentUser();
$role = $_SESSION['user_role'] ?? '';
?>
<nav class="navbar">
    <div class="nav-container">
    

        <a href="../index.php" class="nav-brand">
            <span class="brand-icon-sm">📚</span> Library
        </a>
        <button class="nav-toggle" onclick="toggleNav()" aria-label="Toggle navigation">☰</button>
        <ul class="nav-links" id="navLinks">
            <?php if ($role === 'admin'): ?>
                <li><a href="../admin/dashboard.php">Dashboard</a></li>
                <li><a href="../admin/manage_books.php">Books</a></li>
                <li><a href="../admin/manage_users.php">Users</a></li>
                <li><a href="../admin/reports.php">Reports</a></li>
            <?php elseif ($role === 'librarian'): ?>
                <li><a href="../librarian/borrow_requests.php">Borrow Requests</a></li>
                <li><a href="../librarian/returns.php">Returns</a></li>
                <li><a href="../user/catalog.php">Catalog</a></li>
            <?php else: ?>
                <li><a href="../user/catalog.php">Browse Books</a></li>
                <li><a href="../user/borrow.php">My Borrows</a></li>
                <li><a href="../user/reservations.php">Reservations</a></li>\
                <li><a href="../user/contact.php">Contact</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-user">
            <span class="user-badge user-badge-<?= $role ?>">
                <?= strtoupper($role) ?>
            </span>
            <span class="user-name"><?= sanitize($currentUser['name'] ?? 'User') ?></span>
            <a href="../auth/logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
    </div>
</nav>