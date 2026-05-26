<?php
/**
 * User - My Borrows
 * 
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireLogin();

$db = getDB();
$pageTitle = 'My Borrows';
$user_id   = $_SESSION['user_id'];

// Active borrows
$active = $db->prepare("
    SELECT br.*, b.title, b.cover_image, a.name as author_name,
           DATEDIFF(br.due_date, CURDATE()) as days_left
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    JOIN authors a ON b.author_id = a.id
    WHERE br.user_id = ? AND br.status IN ('pending','approved','overdue')
    ORDER BY br.request_date DESC
");
$active->bind_param("i", $user_id);
$active->execute();
$activeBorrows = $active->get_result();
$active->close();

// Borrow history
$history = $db->prepare("
    SELECT bh.*, b.title, a.name as author_name
    FROM borrow_history bh
    JOIN books b ON bh.book_id = b.id
    JOIN authors a ON b.author_id = a.id
    WHERE bh.user_id = ?
    ORDER BY bh.created_at DESC
");
$history->bind_param("i", $user_id);
$history->execute();
$borrowHistory = $history->get_result();
$history->close();

// Fines
$fineStmt = $db->prepare("
    SELECT f.*, b.title as book_title
    FROM fines f
    JOIN borrow_requests br ON f.borrow_request_id = br.id
    JOIN books b ON br.book_id = b.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$fineStmt->bind_param("i", $user_id);
$fineStmt->execute();
$fines = $fineStmt->get_result();
$fineStmt->close();

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>My Borrows</h1>
        <p>Track your active borrows, history, and any fines.</p>
    </div>

    <!-- ACTIVE BORROWS -->
    <h2 style="margin-bottom:1rem;">Active & Pending</h2>
    <?php if ($activeBorrows->num_rows === 0): ?>
        <div class="empty-state" style="padding:2rem;"><span class="empty-icon">📭</span><h3>No active borrows</h3><p><a href="catalog.php">Browse the catalog</a> to borrow a book.</p></div>
    <?php else: ?>
    <div class="books-grid" style="margin-bottom:2rem;">
    <?php while ($b = $activeBorrows->fetch_assoc()):
        $overdue = $b['days_left'] !== null && $b['days_left'] < 0 && $b['status']==='approved';
    ?>
    <div class="book-card" style="<?= $overdue ? 'border:2px solid var(--danger);' : '' ?>">
        <div class="book-cover">
            <?php if ($b['cover_image'] !== 'no-cover.png'): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= sanitize($b['cover_image']) ?>" alt="Cover">
            <?php else: ?>📚<?php endif; ?>
            <span class="availability-tag <?= $b['status']==='approved' ? 'available' : 'unavailable' ?>">
                <?= strtoupper($b['status']) ?>
            </span>
        </div>
        <div class="book-info">
            <div class="book-title"><?= sanitize($b['title']) ?></div>
            <div class="book-author">by <?= sanitize($b['author_name']) ?></div>
            <?php if ($b['due_date']): ?>
                <p style="font-size:.78rem;margin-top:.5rem;color:<?= $overdue ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                    <?= $overdue
                        ? '⚠️ Overdue by '.abs($b['days_left']).' day(s)'
                        : '📅 Due: '.date('M j, Y', strtotime($b['due_date'])).' ('.$b['days_left'].' days left)' ?>
                </p>
            <?php else: ?>
                <p style="font-size:.78rem;color:var(--text-muted);margin-top:.5rem;">⏳ Awaiting approval</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- FINES -->
    <?php if ($fines->num_rows > 0): ?>
    <h2 style="margin-bottom:1rem;">My Fines</h2>
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><th>Days Overdue</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while ($fine = $fines->fetch_assoc()): ?>
                <tr>
                    <td><?= sanitize($fine['book_title']) ?></td>
                    <td><?= $fine['days_overdue'] ?></td>
                    <td><strong style="color:var(--danger);">€<?= number_format($fine['amount'],2) ?></strong></td>
                    <td><span class="badge badge-<?= $fine['status']==='paid'?'approved':($fine['status']==='waived'?'returned':'rejected') ?>"><?= strtoupper($fine['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($fine['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BORROW HISTORY -->
    <h2 style="margin-bottom:1rem;">Borrow History</h2>
    <?php if ($borrowHistory->num_rows === 0): ?>
        <p style="color:var(--text-muted);">No borrow history yet.</p>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><th>Author</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Fine</th></tr></thead>
                <tbody>
                <?php while ($h = $borrowHistory->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= sanitize($h['title']) ?></strong></td>
                    <td><?= sanitize($h['author_name']) ?></td>
                    <td><?= $h['borrowed_date'] ? date('M j, Y', strtotime($h['borrowed_date'])) : '—' ?></td>
                    <td><?= $h['due_date'] ? date('M j, Y', strtotime($h['due_date'])) : '—' ?></td>
                    <td><?= $h['returned_date'] ? date('M j, Y', strtotime($h['returned_date'])) : '—' ?></td>
                    <td><?= $h['fine_amount'] > 0 ? '<span style="color:var(--danger);">€'.number_format($h['fine_amount'],2).'</span>' : '€0.00' ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>