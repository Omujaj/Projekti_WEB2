<?php
/**
 * Book Detail Page
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

requireLogin();

$db = getDB();
$bookId = (int)($_GET['id'] ?? 0);

if ($bookId <= 0) {
    header('Location: ' . appUrl() . '/user/catalog.php');
    exit();
}

/**
 * Fetch book with author and category
 */
$stmt = $db->prepare(
    "SELECT 
        b.*,
        a.name AS author_name,
        a.bio AS author_bio,
        c.name AS category_name
     FROM books b
     JOIN authors a ON b.author_id = a.id
     JOIN categories c ON b.category_id = c.id
     WHERE b.id = ?
     LIMIT 1"
);

if (!$stmt) {
    redirectWithMessage(appUrl() . '/user/catalog.php', 'error', 'Database error.');
}

$stmt->bind_param("i", $bookId);
$stmt->execute();

$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    redirectWithMessage(appUrl() . '/user/catalog.php', 'error', 'Book not found.');
}

/**
 * Check student book status
 */
$alreadyBorrowed = false;
$alreadyRequested = false;
$alreadyReserved = false;

if (hasRole('student')) {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        "SELECT id 
         FROM borrow_requests 
         WHERE user_id = ? 
           AND book_id = ? 
           AND status = 'approved' 
         LIMIT 1"
    );
    $stmt->bind_param("ii", $userId, $bookId);
    $stmt->execute();
    $alreadyBorrowed = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare(
        "SELECT id 
         FROM borrow_requests 
         WHERE user_id = ? 
           AND book_id = ? 
           AND status = 'pending' 
         LIMIT 1"
    );
    $stmt->bind_param("ii", $userId, $bookId);
    $stmt->execute();
    $alreadyRequested = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $db->prepare(
        "SELECT id 
         FROM reservations 
         WHERE user_id = ? 
           AND book_id = ? 
           AND status = 'active' 
         LIMIT 1"
    );
    $stmt->bind_param("ii", $userId, $bookId);
    $stmt->execute();
    $alreadyReserved = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/**
 * Total borrows count
 */
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM borrow_history WHERE book_id = ?");
$stmt->bind_param("i", $bookId);
$stmt->execute();

$countRow = $stmt->get_result()->fetch_assoc();
$totalBorrows = (int)($countRow['total'] ?? 0);

$stmt->close();

$pageTitle = $book['title'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
<div class="container">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= appUrl() ?>/user/catalog.php">Catalog</a>
            </li>
            <li class="breadcrumb-item active">
                <?= e(mb_strimwidth($book['title'], 0, 40, '…')) ?>
            </li>
        </ol>
    </nav>

    <div class="row g-4">

        <!-- Cover Image Column -->
       <div class="book-cover-detail mb-3" style="max-width:200px;margin:0 auto">
        <?php if (!empty($book['cover_image']) && defined('UPLOAD_DIR') && file_exists(UPLOAD_DIR . $book['cover_image'])): ?>
        <img src="<?= UPLOAD_URL . e($book['cover_image']) ?>"
             alt="<?= e($book['title']) ?>"
           style="width:100%;border-radius:8px;box-shadow:0 8px 24px ▯rgba(0,0,0,0.2)">
        <?php else: ?>
        <div style="width:180px;height:240px;background:linear-gradient(135deg,#1a3a5c,#2563a8);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 8px 24px rgba(0,0,0,0.2)">
            <i class="bi bi-book text-white" style="font-size:4rem"></i>
        </div>
        <?php endif; ?>
        </div>

            <!-- Quick Stats -->
            <div class="card text-start">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <small class="text-muted">Total Copies</small>
                        <small class="fw-600"><?= (int)$book['total_copies'] ?></small>
                    </div>

                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <small class="text-muted">Available</small>
                        <small class="fw-600 <?= (int)$book['available_copies'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= (int)$book['available_copies'] ?>
                        </small>
                    </div>

                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <small class="text-muted">Total Borrows</small>
                        <small class="fw-600"><?= $totalBorrows ?></small>
                    </div>

                    <?php if (!empty($book['pages'])): ?>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <small class="text-muted">Pages</small>
                            <small class="fw-600"><?= (int)$book['pages'] ?></small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($book['language'])): ?>
                        <div class="d-flex justify-content-between py-1">
                            <small class="text-muted">Language</small>
                            <small class="fw-600"><?= e($book['language']) ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Book Info -->
        <div class="col-md-9">

            <h1 class="h2 mb-1"><?= e($book['title']) ?></h1>
            <p class="text-muted fs-5 mb-2">
                by <strong><?= e($book['author_name']) ?></strong>
            </p>

            <!-- Badges -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-secondary fs-6 fw-normal">
                    <?= e($book['category_name']) ?>
                </span>

                <?php if (!empty($book['year'])): ?>
                    <span class="badge bg-light text-dark border">
                        <?= e($book['year']) ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($book['publisher'])): ?>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-building me-1"></i><?= e($book['publisher']) ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($book['isbn'])): ?>
                    <span class="badge bg-light text-dark border">
                        <i class="bi bi-upc me-1"></i><?= e($book['isbn']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Availability status -->
            <div class="alert <?= (int)$book['available_copies'] > 0 ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center gap-2 py-2">
                <i class="bi bi-<?= (int)$book['available_copies'] > 0 ? 'check-circle' : 'clock' ?> fs-5"></i>

                <div>
                    <?php if ((int)$book['available_copies'] > 0): ?>
                        <strong>
                            <?= (int)$book['available_copies'] ?>
                            cop<?= (int)$book['available_copies'] === 1 ? 'y' : 'ies' ?> available
                        </strong>
                        — ready to borrow
                    <?php else: ?>
                        <strong>Not available</strong>
                        — all copies are currently borrowed. You can reserve it.
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if (hasRole('student')): ?>
                <div class="d-flex flex-wrap gap-2 mb-4">

                    <?php if ($alreadyBorrowed): ?>
                        <div class="alert alert-info py-2 mb-0">
                            <i class="bi bi-bookmark-check me-2"></i>
                            You currently have this book borrowed.
                        </div>

                    <?php elseif ($alreadyRequested): ?>
                        <div class="alert alert-warning py-2 mb-0">
                            <i class="bi bi-clock me-2"></i>
                            Your borrow request is pending approval.
                        </div>

                    <?php elseif ($alreadyReserved): ?>
                        <div class="alert alert-info py-2 mb-0">
                            <i class="bi bi-bookmark me-2"></i>
                            You have an active reservation for this book.
                            <a href="<?= appUrl() ?>/user/reservations.php" class="alert-link">
                                View reservations
                            </a>
                        </div>

                    <?php else: ?>
                        <?php if ((int)$book['available_copies'] > 0): ?>
                            <a href="<?= appUrl() ?>/user/borrow.php?book=<?= $bookId ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-bookmark-plus me-2"></i>
                                Request to Borrow
                            </a>
                        <?php else: ?>
                            <a href="<?= appUrl() ?>/user/reservations.php?book=<?= $bookId ?>" class="btn btn-warning btn-lg">
                                <i class="bi bi-clock me-2"></i>
                                Reserve Book
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <!-- Description -->
            <?php if (!empty($book['description'])): ?>
                <div class="mb-4">
                    <h5>About this Book</h5>
                    <p class="text-muted"><?= nl2br(e($book['description'])) ?></p>
                </div>
            <?php endif; ?>

            <!-- Author Bio -->
            <?php if (!empty($book['author_bio'])): ?>
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6>
                            <i class="bi bi-person me-2"></i>
                            About the Author
                        </h6>

                        <p class="mb-0 text-muted">
                            <?= e($book['author_bio']) ?>
                        </p>

                        <?php if (!empty($book['nationality'])): ?>
                            <small class="text-muted mt-1 d-block">
                                <i class="bi bi-flag me-1"></i>
                                <?= e($book['nationality']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>
</div>

<?php
$inlineScript = "const BASE_URL = '" . appUrl() . "';";
require_once __DIR__ . '/../includes/footer.php';
?>