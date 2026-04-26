<?php
session_start();


if (!isset($_SESSION['user_email']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/data.php'; 


if (!isset($_SESSION['my_borrows'])) {
    $_SESSION['my_borrows'] = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $key = array_search($book_id, $_SESSION['my_borrows']);
    
    if ($key !== false) {
       
        unset($_SESSION['my_borrows'][$key]);
        $_SESSION['my_borrows'] = array_values($_SESSION['my_borrows']);
    }
}


$borrowed_books = [];
foreach ($books as $book) {
    if (in_array($book['id'], $_SESSION['my_borrows'])) {
        $borrowed_books[] = $book;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/catalog.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 12px; border: 1px dashed #cbd5e0;}
        .empty-state h2 { font-family: var(--font-heading); color: var(--text-dark); margin-bottom: 10px; font-size: 2rem;}
        .empty-state p { color: var(--text-gray); margin-bottom: 30px;}
        
        /* Stilizimi per butonat e Adminit */
        .admin-actions { display: flex; gap: 10px; width: 100%; }
        .btn-approve { background: #22c55e; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1; font-size: 13px; transition: 0.2s;}
        .btn-approve:hover { background: #16a34a;}
        
        .btn-reject { background: #ef4444; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; flex: 1; font-size: 13px; transition: 0.2s;}
        .btn-reject:hover { background: #dc2626;}
        
        .status-admin-badge { position: absolute; top: 10px; right: 10px; background: #3b82f6; color: white; padding: 3px 8px; font-size: 11px; border-radius: 4px; font-weight: bold;}
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="/fund/index.php" class="logo">
            📚 <span style="color: var(--primary-yellow);">Library</span>
        </a>
        <ul class="nav-links">
            <li><a href="/fund/pages/catalog.php">Browse Books</a></li>
            <li><a href="/fund/pages/reservations.php" class="active">Manage Reservations</a></li>
        </ul>
    </div>

    <div class="nav-right">
        <span class="role-badge admin">ADMIN</span>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="/fund/auth/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="catalog-wrapper">
    <div class="header-section">
        <h1>Manage Reservations</h1>
        <p>There are <?php echo count($borrowed_books); ?> pending requests from students.</p>
    </div>

    <?php if (empty($borrowed_books)): ?>
        <div class="empty-state">
            <h2>No pending reservations</h2>
            <p>All student requests have been handled. Good job!</p>
        </div>
    <?php else: ?>
        <div class="books-grid">
            <?php foreach ($borrowed_books as $book): ?>
            <div class="book-card">
                <div class="card-image">
                    <span class="status-admin-badge">NEEDS ACTION</span>
                    <div class="placeholder-icon">📚</div>
                </div>
                <div class="card-content">
                    <h3><?php echo $book['title']; ?></h3>
                    <p class="author-name">by <?php echo $book['author']; ?></p>
                    <span class="cat-tag"><?php echo $book['category']; ?></span>
                    
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">Requested by: <strong>Student</strong></p>
                    
                    <div class="card-actions" style="margin-top: 15px;">
                        <form method="POST" class="admin-actions">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>

</body>
</html>