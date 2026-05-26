<?php
/**
 * Admin - Manage Books
 * Add, edit, delete books and manage authors/categories.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

requireRole('admin');

$db = getDB();
$pageTitle = 'Manage Books';
$message = '';
$error = '';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitize')) {
    function sanitize($value): string {
        return e($value);
    }
}

/**
 * Handle POST actions
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /**
     * Add / Edit Book
     */
    if ($action === 'add_book' || $action === 'edit_book') {
        $title        = trim($_POST['title'] ?? '');
        $author_id    = (int)($_POST['author_id'] ?? 0);
        $category_id  = (int)($_POST['category_id'] ?? 0);
        $isbn         = trim($_POST['isbn'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $year         = (int)($_POST['year'] ?? 0);
        $total_copies = max(1, (int)($_POST['total_copies'] ?? 1));
        $book_id      = (int)($_POST['book_id'] ?? 0);

        if ($title === '' || $author_id <= 0 || $category_id <= 0) {
            $error = 'Book title, author and category are required.';
        }

        $cover_image = $_POST['existing_cover'] ?? 'no-cover.png';

        /**
         * Cover image upload
         */
        if ($error === '' && !empty($_FILES['cover_image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed, true)) {
                $error = 'Invalid image format. Allowed: jpg, jpeg, png, gif, webp.';
            } elseif ($_FILES['cover_image']['size'] > 2 * 1024 * 1024) {
                $error = 'Image must be under 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $filename = 'book_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                    $cover_image = $filename;
                } else {
                    $error = 'Image upload failed. Check uploads/ folder permissions.';
                }
            }
        }

        if ($error === '') {
            if ($action === 'add_book') {
                $stmt = $db->prepare(
                    "INSERT INTO books 
                     (author_id, category_id, title, isbn, description, year, cover_image, total_copies, available_copies)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $error = 'Database prepare failed: ' . $db->error;
                } else {
                    $stmt->bind_param(
                        "iisssisii",
                        $author_id,
                        $category_id,
                        $title,
                        $isbn,
                        $description,
                        $year,
                        $cover_image,
                        $total_copies,
                        $total_copies
                    );

                    if ($stmt->execute()) {
                        $newId = $stmt->insert_id;

                        for ($i = 1; $i <= $total_copies; $i++) {
                            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $title), 0, 3));
                            if ($prefix === '') {
                                $prefix = 'BK';
                            }

                            $copy_num = $prefix . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

                            $cs = $db->prepare("INSERT INTO book_copies (book_id, copy_number) VALUES (?, ?)");
                            if ($cs) {
                                $cs->bind_param("is", $newId, $copy_num);
                                $cs->execute();
                                $cs->close();
                            }
                        }

                        if (function_exists('logActivity')) {
                            logActivity((int)$_SESSION['user_id'], 'book_added', "Book added: '$title'");
                        }

                        $message = 'Book added successfully!';
                    } else {
                        $error = 'Failed to add book: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            } else {
                $stmt = $db->prepare(
                    "UPDATE books 
                     SET author_id = ?,
                         category_id = ?,
                         title = ?,
                         isbn = ?,
                         description = ?,
                         year = ?,
                         cover_image = ?,
                         total_copies = ?
                     WHERE id = ?"
                );

                if (!$stmt) {
                    $error = 'Database prepare failed: ' . $db->error;
                } else {
                    $stmt->bind_param(
                        "iisssisii",
                        $author_id,
                        $category_id,
                        $title,
                        $isbn,
                        $description,
                        $year,
                        $cover_image,
                        $total_copies,
                        $book_id
                    );

                    if ($stmt->execute()) {
                        if (function_exists('logActivity')) {
                            logActivity((int)$_SESSION['user_id'], 'book_edited', "Book edited: '$title'");
                        }

                        $message = 'Book updated successfully!';
                    } else {
                        $error = 'Failed to update book: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    }

    /**
     * Delete Book
     */
    if ($action === 'delete_book') {
        $book_id = (int)($_POST['book_id'] ?? 0);

        $stmt = $db->prepare("SELECT title FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $bookTitle = $stmt->get_result()->fetch_assoc()['title'] ?? 'Unknown';
        $stmt->close();

        $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);

        if ($stmt->execute()) {
            if (function_exists('logActivity')) {
                logActivity((int)$_SESSION['user_id'], 'book_deleted', "Book deleted: '$bookTitle'");
            }

            $message = 'Book deleted.';
        } else {
            $error = 'Cannot delete: book may have active borrow records.';
        }

        $stmt->close();
    }

    /**
     * Add Author
     */
    if ($action === 'add_author') {
        $name = trim($_POST['author_name'] ?? '');
        $bio = trim($_POST['author_bio'] ?? '');

        if ($name === '') {
            $error = 'Author name is required.';
        } else {
            $stmt = $db->prepare("INSERT INTO authors (name, bio) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $bio);

            if ($stmt->execute()) {
                $message = 'Author added!';
            } else {
                $error = 'Failed to add author.';
            }

            $stmt->close();
        }
    }

    /**
     * Add Category
     */
    if ($action === 'add_category') {
        $name = trim($_POST['cat_name'] ?? '');
        $desc = trim($_POST['cat_desc'] ?? '');

        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $desc);

            if ($stmt->execute()) {
                $message = 'Category added!';
            } else {
                $error = 'Failed to add category.';
            }

            $stmt->close();
        }
    }
}

/**
 * Fetch data
 */
$books = $db->query(
    "SELECT b.*, a.name AS author_name, c.name AS category_name
     FROM books b
     JOIN authors a ON b.author_id = a.id
     JOIN categories c ON b.category_id = c.id
     ORDER BY b.created_at DESC"
);

$authors = $db->query("SELECT * FROM authors ORDER BY name");
$categories = $db->query("SELECT * FROM categories ORDER BY name");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="page-header">
        <h1>Manage Books</h1>
        <p>Add, edit, and delete books from the library catalog.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= sanitize($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div style="display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <button class="btn btn-amber" onclick="openModal('addBookModal')">+ Add Book</button>
        <button class="btn btn-outline" onclick="openModal('addAuthorModal')">+ Add Author</button>
        <button class="btn btn-outline" onclick="openModal('addCategoryModal')">+ Add Category</button>
    </div>

    <div class="catalog-controls" style="margin-bottom:1rem;">
        <div class="search-box" style="max-width:360px;">
            <input type="text" id="bookSearch" placeholder="Search books by title, author, ISBN…">
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>All Books (<?= $books ? $books->num_rows : 0 ?>)</h3>
        </div>

        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
                <table id="booksTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>ISBN</th>
                            <th>Year</th>
                            <th>Copies</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="booksTableBody">
                    <?php $i = 1; ?>
                    <?php if ($books): ?>
                        <?php while ($book = $books->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>

                                <td>
                                    <?php if (!empty($book['cover_image']) && $book['cover_image'] !== 'no-cover.png'): ?>
                                        <img src="<?= BASE_URL ?>/uploads/<?= sanitize($book['cover_image']) ?>"
                                             style="width:40px;height:55px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                        <div style="width:40px;height:55px;background:var(--navy-mid);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">📚</div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?= sanitize($book['title']) ?></strong></td>
                                <td><?= sanitize($book['author_name']) ?></td>
                                <td><span class="badge badge-approved"><?= sanitize($book['category_name']) ?></span></td>
                                <td style="font-size:.78rem;"><?= sanitize($book['isbn']) ?></td>

                                <td>
                                    <?= (int)$book['year'] > 0 ? (int)$book['year'] : 'BC ' . abs((int)$book['year']) ?>
                                </td>

                                <td><?= (int)$book['total_copies'] ?></td>

                                <td>
                                    <span class="badge <?= (int)$book['available_copies'] > 0 ? 'badge-approved' : 'badge-rejected' ?>">
                                        <?= (int)$book['available_copies'] ?>
                                    </span>
                                </td>