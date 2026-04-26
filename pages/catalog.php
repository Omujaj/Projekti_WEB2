<?php
session_start();

require_once '../includes/data.php'; 

if (!isset($books)) {
    $books = [];
}

if (!isset($_SESSION['my_borrows'])) {
    $_SESSION['my_borrows'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $book_id = $_POST['borrow_id'];
    
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !in_array($book_id, $_SESSION['my_borrows'])) {
        $_SESSION['my_borrows'][] = $book_id;
    }
}

// FILTRAT
$filtered_books = $books; 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!empty($_GET['search']) || !empty($_GET['category']) || !empty($_GET['author']) || !empty($_GET['availability']))) {
    
    $search_query = strtolower(trim($_GET['search'] ?? ''));
    $filter_category = $_GET['category'] ?? '';
    $filter_author = $_GET['author'] ?? '';
    $filter_avail = $_GET['availability'] ?? '';

    $filtered_books = array_filter($books, function($book) use ($search_query, $filter_category, $filter_author, $filter_avail) {
        $matches = true;
        if ($search_query !== '') {
            $title_match = strpos(strtolower($book['title']), $search_query) !== false;
            $author_match = strpos(strtolower($book['author']), $search_query) !== false;
            if (!$title_match && !$author_match) $matches = false;
        }
        if ($filter_category !== '' && $book['category'] !== $filter_category) $matches = false;
        if ($filter_author !== '' && $book['author'] !== $filter_author) $matches = false;
        if ($filter_avail !== '' && $book['status'] !== $filter_avail) $matches = false;

        return $matches;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog - Library</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/catalog.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-left">
        <a href="/fund/index.php" class="logo">
            📚 <span style="color: var(--primary-yellow);">Library</span>
        </a>
        <ul class="nav-links">
            <li><a href="/fund/pages/catalog.php" class="active">Browse Books</a></li>
            <?php if(isset($_SESSION['role'])): ?>
                <?php if($_SESSION['role'] === 'user'): ?>
                    <li><a href="/fund/pages/my_borrows.php">My Borrows</a></li>
                <?php elseif($_SESSION['role'] === 'admin'): ?>
                    <li><a href="/fund/pages/reservations.php">Manage Reservations</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="nav-right">
        <?php if(isset($_SESSION['user_email']) && isset($_SESSION['role'])): ?>
            <?php $badgeClass = ($_SESSION['role'] === 'admin') ? 'admin' : 'student'; ?>
            <?php $badgeText = ($_SESSION['role'] === 'admin') ? 'ADMIN' : 'STUDENT'; ?>
            
            <span class="role-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="/fund/auth/logout.php" class="btn-logout">Logout</a>
        <?php else: ?>
            <a href="/fund/index.php" class="btn-login-nav">Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="catalog-wrapper">
    <div class="header-section">
        <h1>Book Catalog</h1>
        <p>Browse <?php echo count($filtered_books); ?> books in our library.</p>
    </div>

    <form class="filters-bar" method="GET" action="catalog.php">
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search by title, author..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </div>
        <div class="select-group">
            <select name="category" class="filter-select">
                <option value="">Category</option>
                <option value="Programming" <?php echo (isset($_GET['category']) && $_GET['category'] == 'Programming') ? 'selected' : ''; ?>>Programming</option>
                <option value="Mathematics" <?php echo (isset($_GET['category']) && $_GET['category'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                <option value="Computer Science" <?php echo (isset($_GET['category']) && $_GET['category'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                <option value="Fiction" <?php echo (isset($_GET['category']) && $_GET['category'] == 'Fiction') ? 'selected' : ''; ?>>Fiction</option>
            </select>
            <select name="author" class="filter-select">
                <option value="">Author</option>
                <option value="Robert C. Martin" <?php echo (isset($_GET['author']) && $_GET['author'] == 'Robert C. Martin') ? 'selected' : ''; ?>>Robert C. Martin</option>
                <option value="George Orwell" <?php echo (isset($_GET['author']) && $_GET['author'] == 'George Orwell') ? 'selected' : ''; ?>>George Orwell</option>
            </select>
            <select name="availability" class="filter-select">
                <option value="">Availability</option>
                <option value="available" <?php echo (isset($_GET['availability']) && $_GET['availability'] == 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="unavailable" <?php echo (isset($_GET['availability']) && $_GET['availability'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="catalog.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <div class="books-grid">
        <?php if (empty($filtered_books)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; color: var(--text-gray);">
                <h2>No books found!</h2>
                <p>Try adjusting your search or filters.</p>
            </div>
        <?php else: ?>
            <?php foreach ($filtered_books as $book): ?>
            <div class="book-card">
                <div class="card-image">
                    <span class="stock-badge" style="background: <?php echo ($book['status'] == 'available') ? '#22C55E' : '#ef4444'; ?>">
                        <?php echo ($book['status'] == 'available') ? '3 left' : '0 left'; ?>
                    </span>
                    <div class="placeholder-icon">📚</div>
                </div>
                <div class="card-content">
                    <h3><?php echo $book['title']; ?></h3>
                    <p class="author-name">by <?php echo $book['author']; ?></p>
                    <span class="cat-tag"><?php echo $book['category']; ?></span>
                    
                    <div class="card-actions">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <button type="button" class="btn-admin-edit" style="width: 100%;" onclick="openEditModal(
                                '<?php echo $book['id']; ?>', 
                                '<?php echo addslashes($book['title']); ?>', 
                                '<?php echo addslashes($book['author']); ?>', 
                                '<?php echo $book['category']; ?>', 
                                '<?php echo $book['status']; ?>', 
                                '<?php echo addslashes($book['description']); ?>'
                            )">Edit Book</button>
                        <?php else: ?>
                            <button type="button" class="btn-details" onclick="openModal(
                                '<?php echo addslashes($book['title']); ?>', 
                                '<?php echo addslashes($book['author']); ?>', 
                                '<?php echo $book['category']; ?>', 
                                '<?php echo $book['id']; ?>', 
                                '<?php echo $book['year']; ?>', 
                                '<?php echo ucfirst($book['status']); ?>', 
                                '<?php echo addslashes($book['description']); ?>'
                            )">Details</button>

                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                                <?php if (in_array($book['id'], $_SESSION['my_borrows'])): ?>
                                    <button type="button" class="btn-pending" disabled>PENDING</button>
                                <?php else: ?>
                                    <form method="POST" style="flex:1; display:flex;">
                                        <input type="hidden" name="borrow_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" class="btn-borrow">Borrow</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
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

<div id="editModal" class="modal-overlay">
    <div class="modal-box" style="max-width: 500px;">
        <button class="close-x" onclick="closeEditModal()">&times;</button>
        <div class="modal-info-side" style="padding: 30px; width: 100%;">
            <h2 style="margin-bottom: 20px; color: var(--primary); font-family: var(--font-heading);">Edit Book</h2>
            <form method="POST" action="catalog.php">
                <input type="hidden" name="edit_book_id" id="e-id">
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-gray);">TITLE</label>
                    <input type="text" name="edit_title" id="e-title" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-family: inherit;" required>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-gray);">AUTHOR</label>
                    <input type="text" name="edit_author" id="e-author" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-family: inherit;" required>
                </div>
                <div style="margin-bottom: 15px; display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="font-size: 12px; font-weight: bold; color: var(--text-gray);">CATEGORY</label>
                        <select name="edit_category" id="e-category" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-family: inherit;">
                            <option value="Programming">Programming</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Fiction">Fiction</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 12px; font-weight: bold; color: var(--text-gray);">STATUS</label>
                        <select name="edit_status" id="e-status" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-family: inherit;">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="font-size: 12px; font-weight: bold; color: var(--text-gray);">DESCRIPTION</label>
                    <textarea name="edit_description" id="e-desc" rows="3" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; resize: vertical; font-family: inherit;"></textarea>
                </div>
                <button type="button" onclick="alert('In a real app, this would save to the database!'); closeEditModal();" style="width: 100%; background: #3b82f6; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>

<script src="../assets/js/catalog.js"></script>

</body>
</html>