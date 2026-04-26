<?php
session_start();

if (!isset($_SESSION['user_email']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/data.php'; 


if (!isset($_SESSION['my_borrows'])) {
    $_SESSION['my_borrows'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_id'])) {
    $return_id = $_POST['return_id'];
    $key = array_search($return_id, $_SESSION['my_borrows']);
    
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
    <title>My Borrows - Library</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/catalog.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 12px; border: 1px dashed #cbd5e0;}
        .empty-state h2 { font-family: var(--font-heading); color: var(--text-dark); margin-bottom: 10px; font-size: 2rem;}
        .empty-state p { color: var(--text-gray); margin-bottom: 30px;}
        .btn-browse { background: var(--primary-yellow); color: var(--primary); padding: 12px 30px; border-radius: 8px; font-weight: bold; transition: 0.2s;}
        .btn-browse:hover { opacity: 0.9; }
        
        .btn-return { background: #ef4444; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; font-size: 13px; transition: 0.2s;}
        .btn-return:hover { background: #dc2626;}
        
        .status-pending-badge { position: absolute; top: 10px; right: 10px; background: #f59e0b; color: white; padding: 3px 8px; font-size: 11px; border-radius: 4px; font-weight: bold;}
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
            <li><a href="/fund/pages/my_borrows.php" class="active">My Borrows</a></li>
        </ul>
    </div>

    <div class="nav-right">
        <span class="role-badge student">STUDENT</span>
        <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        <a href="/fund/auth/logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="catalog-wrapper">
    <div class="header-section">
        <h1>My Borrowed Books</h1>
        <p>You have <?php echo count($borrowed_books); ?> book(s) pending for approval.</p>
    </div>

    <?php if (empty($borrowed_books)): ?>
        <div class="empty-state">
            <h2>No books borrowed yet</h2>
            <p>Looks like you haven't requested any books from the library.</p>
            <a href="catalog.php" class="btn-browse">Browse Catalog</a>
        </div>
    <?php else: ?>
        <div class="books-grid">
            <?php foreach ($borrowed_books as $book): ?>
            <div class="book-card">
                <div class="card-image">
                    <span class="status-pending-badge">PENDING APPROVAL</span>
                    <div class="placeholder-icon">📚</div>
                </div>
                <div class="card-content">
                    <h3><?php echo $book['title']; ?></h3>
                    <p class="author-name">by <?php echo $book['author']; ?></p>
                    <span class="cat-tag"><?php echo $book['category']; ?></span>
                    
                    <div class="card-actions">
                        <button type="button" class="btn-details" 
                            onclick="openModal(
                                '<?php echo addslashes($book['title']); ?>', 
                                '<?php echo addslashes($book['author']); ?>', 
                                '<?php echo $book['category']; ?>', 
                                '<?php echo $book['id']; ?>', 
                                '<?php echo $book['year']; ?>', 
                                '<?php echo ucfirst($book['status']); ?>', 
                                '<?php echo addslashes($book['description']); ?>'
                            )">Details</button>

                        <form method="POST" style="flex:1; display:flex;">
                            <input type="hidden" name="return_id" value="<?php echo $book['id']; ?>">
                            <button type="submit" class="btn-return">Cancel Request</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="bookModal" class="modal-overlay">
    <div class="modal-box">
        <button class="close-x" onclick="closeModal()">&times;</button>
        <div class="modal-layout">
            <div class="modal-img-side">📚</div>
            <div class="modal-info-side">
                <h2 id="m-title"></h2>
                <div class="info-group"><span>AUTHOR</span><p id="m-author"></p></div>
                <div class="info-group"><span>CATEGORY</span><p id="m-category"></p></div>
                <div class="info-group"><span>BOOK ID</span><p id="m-isbn"></p></div>
                <div class="info-group"><span>YEAR</span><p id="m-year"></p></div>
                <div class="info-group"><span>STATUS</span><p id="m-avail"></p></div>
                <div class="info-group"><span>DESCRIPTION</span><p id="m-desc"></p></div>
                <button class="btn-modal-close" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script src="../assets/js/catalog.js"></script>

</body>
</html>