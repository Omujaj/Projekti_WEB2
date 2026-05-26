<?php
/**
 * Librarian - Process Book Returns
 * Mark books as returned and calculate fines for overdue books.
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole(['admin','librarian']);

$db = getDB();
$pageTitle = 'Process Returns';
$message = $error = '';

// Pre-fill from query string 
$prefillId = (int)($_GET['request_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);

    // Fetch borrow request
    $stmt = $db->prepare("SELECT br.*, b.title, b.id as book_id FROM borrow_requests br JOIN books b ON br.book_id=b.id WHERE br.id=? AND br.status='approved'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) {
        $error = 'Borrow request not found or already returned.';
    } else {
        $returnDate = date('Y-m-d');
        $dueDate    = $req['due_date'];
        $daysOverdue = 0;
        $fineAmount  = 0.00;

        // Calculate overdue days and fine
        if ($dueDate && strtotime($returnDate) > strtotime($dueDate)) {
            $daysOverdue = (int)((strtotime($returnDate) - strtotime($dueDate)) / 86400);
            $fineAmount  = $daysOverdue * FINE_PER_DAY;
        }

        // Update borrow request to returned
        $stmt = $db->prepare("UPDATE borrow_requests SET status='returned', return_date=CURDATE() WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute(); $stmt->close();

        // Increase available copies when returned
        $stmt = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id=?");
        $stmt->bind_param("i", $req['book_id']);
        $stmt->execute(); $stmt->close();

        // Update borrow history if it was created during approval; otherwise insert it
        $stmt = $db->prepare("UPDATE borrow_history SET returned_date = ?, fine_amount = ? WHERE borrow_request_id = ?");
        $stmt->bind_param("sdi", $returnDate, $fineAmount, $request_id);
        $stmt->execute();
        $updatedRows = $stmt->affected_rows;
        $stmt->close();

        if ($updatedRows === 0) {
            $stmt = $db->prepare("INSERT INTO borrow_history (borrow_request_id, user_id, book_id, borrowed_date, due_date, returned_date, fine_amount) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("iiisssd", $request_id, $req['user_id'], $req['book_id'], $req['approved_date'], $dueDate, $returnDate, $fineAmount);
            $stmt->execute(); $stmt->close();
        }

        // Create fine record if applicable
        if ($fineAmount > 0) {
            $stmt = $db->prepare("INSERT INTO fines (borrow_request_id, user_id, amount, days_overdue) VALUES (?,?,?,?)");
            $stmt->bind_param("iidi", $request_id, $req['user_id'], $fineAmount, $daysOverdue);
            $stmt->execute(); $stmt->close();
            $message = "Book returned. <strong>Fine: €$fineAmount</strong> ($daysOverdue days overdue).";
        } else {
            $message = "Book '{$req['title']}' returned successfully. No fine.";
        }

        // Check for reservations - notify next in queue
        $stmt = $db->prepare("SELECT r.*, u.name FROM reservations r JOIN users u ON r.user_id=u.id WHERE r.book_id=? AND r.status='active' ORDER BY r.queue_position ASC LIMIT 1");
        $stmt->bind_param("i", $req['book_id']);
        $stmt->execute();
        $nextRes = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($nextRes) {
            // Mark reservation as fulfilled
            $stmt = $db->prepare("UPDATE reservations SET status='fulfilled' WHERE id=?");
            $stmt->bind_param("i", $nextRes['id']);
            $stmt->execute(); $stmt->close();
            $message .= " User <strong>{$nextRes['name']}</strong> had a reservation and has been notified.";
        }

        logActivity('book_returned', "Book '{$req['title']}' returned. Fine: €$fineAmount");
    }
}

// Fetch all currently approved borrows
$activeBorrows = $db->query("
    SELECT br.*, u.name as user_name, b.title as book_title,
           DATEDIFF(CURDATE(), br.due_date) as days_overdue_calc
    FROM borrow_requests br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    WHERE br.status = 'approved'
    ORDER BY br.due_date ASC
");

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>Process Returns</h1>
        <p>Mark borrowed books as returned and calculate any fines.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

    <!-- Quick return form -->
    <div class="card" style="margin-bottom:1.5rem;max-width:480px;">
        <div class="card-header"><h3>Quick Return by Request ID</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Borrow Request ID</label>
                    <input type="number" name="request_id" value="<?= $prefillId ?: '' ?>" required placeholder="e.g. 5" min="1">
                </div>
                <button type="submit" class="btn btn-primary">Process Return</button>
            </form>
        </div>
    </div>

    <!-- Active borrows  -->
    <div class="card">
        <div class="card-header"><h3>Active Borrows (<?= $activeBorrows->num_rows ?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>ID</th><th>Student</th><th>Book</th><th>Due Date</th><th>Overdue</th><th>Est. Fine</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php if ($activeBorrows->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No active borrows.</td></tr>
                <?php else: while ($b = $activeBorrows->fetch_assoc()): ?>
                <?php
                    $overdue = $b['days_overdue_calc'] > 0;
                    $fine    = $overdue ? $b['days_overdue_calc'] * FINE_PER_DAY : 0;
                ?>
                <tr style="<?= $overdue ? 'background:#fff7ed;' : '' ?>">
                    <td><?= $b['id'] ?></td>
                    <td><?= sanitize($b['user_name']) ?></td>
                    <td><?= sanitize($b['book_title']) ?></td>
                    <td><?= date('M j, Y', strtotime($b['due_date'])) ?></td>
                    <td><?= $overdue ? '<span style="color:var(--danger);font-weight:700;">'.abs($b['days_overdue_calc']).' day(s)</span>' : '<span style="color:var(--success);">On time</span>' ?></td>
                    <td><?= $fine > 0 ? '<strong style="color:var(--danger);">€'.number_format($fine,2).'</strong>' : '€0.00' ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Process return for this book?');">
                            <input type="hidden" name="request_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">Return</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
