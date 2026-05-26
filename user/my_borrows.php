<?php
/**
 * Student - My Borrows
 * MySQLi version matching database.sql schema.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';
requireRole('student');

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$pageTitle = 'My Borrows';

$flash = getFlashMessage();

// Cancel a pending request
if (isset($_GET['cancel_request'])) {
    $reqId = (int)$_GET['cancel_request'];
    $stmt = $db->prepare("UPDATE borrow_requests SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param("ii", $reqId, $userId);
        $stmt->execute();
        $stmt->close();
    }
    redirectWithMessage(appUrl() . '/user/my_borrows.php', 'success', 'Request cancelled.');
}

// Active approved borrows
$stmt = $db->prepare(
    "SELECT br.*, b.title, b.cover_image, a.name AS author_name,
            DATEDIFF(br.due_date, CURDATE()) AS days_remaining,
            COALESCE(f.amount, 0) AS fine_amount,
            COALESCE(f.status, '') AS fine_status
     FROM borrow_requests br
     JOIN books b ON br.book_id = b.id
     JOIN authors a ON b.author_id = a.id
     LEFT JOIN fines f ON f.borrow_request_id = br.id AND f.status = 'unpaid'
     WHERE br.user_id = ? AND br.status = 'approved'
     ORDER BY br.due_date ASC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeBorrows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pending requests
$stmt = $db->prepare(
    "SELECT br.*, b.title, b.cover_image, a.name AS author_name
     FROM borrow_requests br
     JOIN books b ON br.book_id = b.id
     JOIN authors a ON b.author_id = a.id
     WHERE br.user_id = ? AND br.status = 'pending'
     ORDER BY br.request_date DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$pendingRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Returned / rejected / overdue history
$stmt = $db->prepare(
    "SELECT br.id AS request_id, br.status, br.request_date, br.approved_date, br.due_date, br.return_date,
            bh.borrowed_date, bh.returned_date, bh.fine_amount,
            b.title, a.name AS author_name
     FROM borrow_requests br
     JOIN books b ON br.book_id = b.id
     JOIN authors a ON b.author_id = a.id
     LEFT JOIN borrow_history bh ON bh.borrow_request_id = br.id
     WHERE br.user_id = ? AND br.status IN ('returned', 'rejected', 'overdue', 'cancelled')
     ORDER BY COALESCE(br.return_date, br.approved_date, DATE(br.request_date)) DESC
     LIMIT 30"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Unpaid fines
$stmt = $db->prepare(
    "SELECT f.*, b.title
     FROM fines f
     JOIN borrow_requests br ON f.borrow_request_id = br.id
     JOIN books b ON br.book_id = b.id
     WHERE f.user_id = ? AND f.status = 'unpaid'
     ORDER BY f.created_at DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$fines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$totalFine = array_sum(array_column($fines, 'amount'));

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-wrapper">
<div class="container-fluid">

    <div class="page-header">
        <h1><i class="bi bi-bookmark-check me-2"></i>My Borrows</h1>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($fines)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>Outstanding Fines: €<?= number_format($totalFine, 2) ?></strong><br>
                <small>You have <?= count($fines) ?> unpaid fine(s). Please visit the library to settle your account.</small>
            </div>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#activeTab">Active <span class="badge bg-primary"><?= count($activeBorrows) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pendingTab">Pending <span class="badge bg-warning text-dark"><?= count($pendingRequests) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#historyTab">History</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="activeTab">
            <?php if (empty($activeBorrows)): ?>
                <div class="text-center py-5 text-muted">No active borrows.</div>
            <?php else: ?>
                <div class="row g-3">
                <?php foreach ($activeBorrows as $borrow): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5><?= e($borrow['title']) ?></h5>
                                <p class="text-muted mb-2">by <?= e($borrow['author_name']) ?></p>
                                <p class="mb-1"><strong>Approved:</strong> <?= $borrow['approved_date'] ? date('d M Y', strtotime($borrow['approved_date'])) : '—' ?></p>
                                <p class="mb-1"><strong>Due:</strong> <?= $borrow['due_date'] ? date('d M Y', strtotime($borrow['due_date'])) : '—' ?></p>
                                <?php if ((int)$borrow['days_remaining'] < 0): ?>
                                    <span class="badge bg-danger"><?= abs((int)$borrow['days_remaining']) ?> day(s) overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= (int)$borrow['days_remaining'] ?> day(s) remaining</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="pendingTab">
            <?php if (empty($pendingRequests)): ?>
                <div class="text-center py-5 text-muted">No pending requests.</div>
            <?php else: ?>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Book</th><th>Author</th><th>Requested</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($pendingRequests as $req): ?>
                                <tr>
                                    <td><?= e($req['title']) ?></td>
                                    <td><?= e($req['author_name']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($req['request_date'])) ?></td>
                                    <td><a class="btn btn-sm btn-outline-danger" href="?cancel_request=<?= (int)$req['id'] ?>" onclick="return confirm('Cancel this request?')">Cancel</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="historyTab">
            <?php if (empty($history)): ?>
                <div class="text-center py-5 text-muted">No history yet.</div>
            <?php else: ?>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Book</th><th>Author</th><th>Status</th><th>Borrowed</th><th>Returned</th><th>Fine</th></tr></thead>
                            <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= e($row['title']) ?></td>
                                    <td><?= e($row['author_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e(ucfirst($row['status'])) ?></span></td>
                                    <td><?= !empty($row['borrowed_date']) ? date('d M Y', strtotime($row['borrowed_date'])) : (!empty($row['approved_date']) ? date('d M Y', strtotime($row['approved_date'])) : '—') ?></td>
                                    <td><?= !empty($row['returned_date']) ? date('d M Y', strtotime($row['returned_date'])) : (!empty($row['return_date']) ? date('d M Y', strtotime($row['return_date'])) : '—') ?></td>
                                    <td>€<?= number_format((float)($row['fine_amount'] ?? 0), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
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
