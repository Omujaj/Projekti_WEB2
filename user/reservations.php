<?php
/**
 * User - My Reservations
 * View and cancel book reservations.
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireLogin();

$db = getDB();
$pageTitle = 'My Reservations';
$user_id   = $_SESSION['user_id'];
$message   = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $res_id = (int)($_POST['reservation_id'] ?? 0);
    $stmt   = $db->prepare("UPDATE reservations SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $res_id, $user_id);
    $stmt->execute(); $stmt->close();
    logActivity('reservation_cancelled', "User #$user_id cancelled reservation #$res_id");
    $message = 'Reservation cancelled.';
}

$stmt = $db->prepare("
    SELECT r.*, b.title, b.cover_image, b.available_copies, a.name as author_name
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    JOIN authors a ON b.author_id = a.id
    WHERE r.user_id = ?
    ORDER BY r.reserved_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations = $stmt->get_result();
$stmt->close();

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>My Reservations</h1>
        <p>Books you've reserved while they are unavailable.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>

    <?php if ($reservations->num_rows === 0): ?>
        <div class="empty-state">
            <span class="empty-icon">📌</span>
            <h3>No reservations</h3>
            <p>You haven't reserved any books yet. <a href="catalog.php">Browse the catalog</a> to reserve unavailable books.</p>
        </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>Book</th><th>Author</th><th>Queue Position</th><th>Status</th><th>Reserved</th><th>Expires</th><th>Action</th></tr></thead>
                <tbody>
                <?php while ($res = $reservations->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= sanitize($res['title']) ?></strong>
                        <?php if ($res['available_copies'] > 0): ?>
                            <span style="display:block;font-size:.75rem;color:var(--success);">Now Available!</span>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($res['author_name']) ?></td>
                    <td style="text-align:center;">
                        <span class="stat-icon" style="font-size:1.4rem;">#<?= $res['queue_position'] ?></span>
                    </td>
                    <td><span class="badge badge-<?= $res['status'] === 'active' ? 'approved' : ($res['status']==='fulfilled'?'returned':'rejected') ?>"><?= strtoupper($res['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($res['reserved_at'])) ?></td>
                    <td><?= $res['expires_at'] ? date('M j, Y', strtotime($res['expires_at'])) : '—' ?></td>
                    <td>
                        <?php if ($res['status'] === 'active'): ?>
                        <form method="POST" onsubmit="return confirm('Cancel this reservation?');">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
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
