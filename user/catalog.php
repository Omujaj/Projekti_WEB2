<?php
/**
 * User - Book Catalog
 * 
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireLogin();

$db = getDB();
$pageTitle = 'Book Catalog';
$message = $error = '';

// Handle borrow/reserve 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($action === 'borrow') {
        // Check if user already has an active borrow/pending request for this book
        $stmt = $db->prepare("SELECT id FROM borrow_requests WHERE user_id=? AND book_id=? AND status IN ('pending','approved')");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'You already have an active request or borrow for this book.';
        } else {
            $stmt->close();
            $stmt = $db->prepare("INSERT INTO borrow_requests (user_id, book_id, status) VALUES (?,?,'pending')");
            $stmt->bind_param("ii", $user_id, $book_id);
            if ($stmt->execute()) {
                $bookTitle = $db->query("SELECT title FROM books WHERE id=$book_id")->fetch_assoc()['title'];
                logActivity('borrow_requested', "Borrow request for book '$bookTitle' by user #$user_id");
                $message = 'Borrow request submitted! Awaiting librarian approval.';
            } else { $error = 'Failed to submit request.'; }
        }
        $stmt->close();
    }

    if ($action === 'reserve') {
        // Check if already reserved
        $stmt = $db->prepare("SELECT id FROM reservations WHERE user_id=? AND book_id=? AND status='active'");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'You already have an active reservation for this book.';
        } else {
            $stmt->close();
            $pos_result = $db->query("SELECT COUNT(*)+1 as pos FROM reservations WHERE book_id=$book_id AND status='active'");
            $queue_pos  = $pos_result->fetch_assoc()['pos'];
            $expires    = date('Y-m-d', strtotime('+30 days'));
            $stmt = $db->prepare("INSERT INTO reservations (user_id, book_id, queue_position, expires_at) VALUES (?,?,?,?)");
            $stmt->bind_param("iiis", $user_id, $book_id, $queue_pos, $expires);
            if ($stmt->execute()) {
                $bookTitle = $db->query("SELECT title FROM books WHERE id=$book_id")->fetch_assoc()['title'];
                logActivity('book_reserved', "Reservation for book '$bookTitle' by user #$user_id (queue #$queue_pos)");
                $message = "Reserved! You are #$queue_pos in the queue.";
            } else { $error = 'Reservation failed.'; }
        }
        $stmt->close();
    }
}

// Filters
$search      = trim($_GET['search'] ?? '');
$category_id = (int)($_GET['category'] ?? 0);
$author_id   = (int)($_GET['author'] ?? 0);
$avail       = $_GET['avail'] ?? '';

$where = ['1=1'];
$params = [];
$types  = '';

if ($search) {
    $where[] = "(b.title LIKE ? OR a.name LIKE ? OR b.isbn LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($category_id) { $where[] = "b.category_id = ?"; $params[] = $category_id; $types .= 'i'; }
if ($author_id)   { $where[] = "b.author_id = ?";   $params[] = $author_id;   $types .= 'i'; }
if ($avail === '1') { $where[] = "b.available_copies > 0"; }
if ($avail === '0') { $where[] = "b.available_copies = 0"; }

$whereStr = implode(' AND ', $where);

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page-1) * $perPage;

$countSql = "SELECT COUNT(*) as c FROM books b JOIN authors a ON b.author_id=a.id JOIN categories c ON b.category_id=c.id WHERE $whereStr";
if ($params) {
    $stmt = $db->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
} else { $total = $db->query($countSql)->fetch_assoc()['c']; }
$totalPages = max(1, ceil($total / $perPage));

$sql = "SELECT b.*, a.name as author_name, c.name as category_name FROM books b JOIN authors a ON b.author_id=a.id JOIN categories c ON b.category_id=c.id WHERE $whereStr ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset";
if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $books = $stmt->get_result();
    $stmt->close();
} else { $books = $db->query($sql); }

$categories = $db->query("SELECT * FROM categories ORDER BY name");
$authors    = $db->query("SELECT * FROM authors ORDER BY name");

// Get user's existing requests
$user_id  = $_SESSION['user_id'];
$myBorrows = [];
$r = $db->prepare("SELECT book_id, status FROM borrow_requests WHERE user_id=? AND status IN ('pending','approved')");
$r->bind_param("i", $user_id); $r->execute();
$res = $r->get_result();
while ($row = $res->fetch_assoc()) $myBorrows[$row['book_id']] = $row['status'];
$r->close();

$myReservations = [];
$r = $db->prepare("SELECT book_id FROM reservations WHERE user_id=? AND status='active'");
$r->bind_param("i", $user_id); $r->execute();
$res = $r->get_result();
while ($row = $res->fetch_assoc()) $myReservations[] = $row['book_id'];
$r->close();

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>Book Catalog</h1>
        <p>Browse <?= $total ?> books in our library.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

    <!-- FILTERS -->
    <form method="GET" action="">
        <div class="catalog-controls">
            <div class="search-box" style="flex:2;">
                <input type="text" name="search" id="catalogSearch" placeholder="Search by title, author, ISBN…"
                       value="<?= sanitize($search) ?>">
            </div>
            <select name="category" class="filter-select" style="padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:6px;font-family:var(--font-body);">
                <option value="">All Categories</option>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_id==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="author" class="filter-select" style="padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:6px;font-family:var(--font-body);">
                <option value="">All Authors</option>
                <?php while ($aut = $authors->fetch_assoc()): ?>
                <option value="<?= $aut['id'] ?>" <?= $author_id==$aut['id']?'selected':'' ?>><?= sanitize($aut['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <select name="avail" style="padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:6px;font-family:var(--font-body);">
                <option value="">All Availability</option>
                <option value="1" <?= $avail==='1'?'selected':'' ?>>Available</option>
                <option value="0" <?= $avail==='0'?'selected':'' ?>>Unavailable</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="catalog.php" class="btn btn-outline">Reset</a>
        </div>
    </form>

    <!-- BOOKS GRID -->
    <div class="books-grid" id="booksGrid">
    <?php if ($books->num_rows === 0): ?>
        <div class="empty-state" style="grid-column:1/-1;">
            <span class="empty-icon">📭</span>
            <h3>No books found</h3>
            <p>Try adjusting your filters or search term.</p>
        </div>
    <?php else: while ($book = $books->fetch_assoc()):
        $hasBorrow    = isset($myBorrows[$book['id']]);
        $hasReserv    = in_array($book['id'], $myReservations);
        $isAvailable  = $book['available_copies'] > 0;
    ?>
    <div class="book-card" data-search="<?= strtolower(sanitize($book['title'].' '.$book['author_name'].' '.$book['category_name'])) ?>">
        <div class="book-cover">
            <?php if ($book['cover_image'] !== 'no-cover.png'): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= sanitize($book['cover_image']) ?>" alt="Cover">
            <?php else: ?>📚<?php endif; ?>
            <span class="availability-tag <?= $isAvailable ? 'available' : 'unavailable' ?>">
                <?= $isAvailable ? $book['available_copies'].' left' : 'Unavailable' ?>
            </span>
        </div>
        <div class="book-info">
            <div class="book-title"><?= sanitize($book['title']) ?></div>
            <div class="book-author">by <?= sanitize($book['author_name']) ?></div>
            <span class="book-category"><?= sanitize($book['category_name']) ?></span>
            <div class="book-actions">
                <button class="btn btn-outline btn-sm" onclick="openBookDetail(<?= htmlspecialchars(json_encode($book)) ?>)">Details</button>
                <?php if ($hasBorrow): ?>
                    <span class="btn btn-sm" style="background:var(--cream-mid);color:var(--text-muted);cursor:default;">
                        <?= strtoupper($myBorrows[$book['id']]) ?>
                    </span>
                <?php elseif ($isAvailable): ?>
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button name="action" value="borrow" class="btn btn-amber btn-sm" style="width:100%;">Borrow</button>
                    </form>
                <?php elseif ($hasReserv): ?>
                    <span class="btn btn-sm" style="background:var(--cream-mid);color:var(--text-muted);cursor:default;">Reserved</span>
                <?php else: ?>
                    <form method="POST" style="flex:1;">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <button name="action" value="reserve" class="btn btn-outline btn-sm" style="width:100%;">Reserve</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination" style="margin-top:2rem;">
        <?php
        $qp = $_GET; unset($qp['page']);
        $qs = http_build_query($qp);
        ?>
        <?php if ($page > 1): ?><a href="?<?= $qs ?>