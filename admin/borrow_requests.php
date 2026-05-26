<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

requireRole(['admin', 'librarian']);
$db = getDB();
$pageTitle = 'Borrow Requests';
$message = '';
$messageType = '';

/**
 * Approve borrow request
 */
if (isset($_GET['approve'])) {
    $reqId = (int)$_GET['approve'];

    $stmt = $db->prepare(
        "SELECT br.*, b.available_copies, b.title
         FROM borrow_requests br
         JOIN books b ON br.book_id = b.id
         WHERE br.id = ? AND br.status = 'pending'
         LIMIT 1"
    );

    $stmt->bind_param("i", $reqId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($req && (int)$req['available_copies'] > 0) {
        $db->begin_transaction();

        try {
            $userId = (int)$_SESSION['user_id'];
            $bookId = (int)$req['book_id'];
            $studentId = (int)$req['user_id'];

            $borrowDays = defined('BORROW_DAYS') ? BORROW_DAYS : 14;
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+' . $borrowDays . ' days'));

            /**
             * 1. Update request
             */
            $stmt = $db->prepare(
                "UPDATE borrow_requests
                 SET status = 'approved',
                     approved_by = ?,
                     approved_date = CURDATE(),
                     due_date = ?
                 WHERE id = ?"
            );

            $stmt->bind_param("isi", $userId, $dueDate, $reqId);
            $stmt->execute();
            $stmt->close();

            /**
             * 2. Insert borrow history
             */
            $stmt = $db->prepare(
                "INSERT INTO borrow_history
                 (borrow_request_id, user_id, book_id, borrowed_date, due_date)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param("iiiss", $reqId, $studentId, $bookId, $borrowDate, $dueDate);
            $stmt->execute();
            $stmt->close();

            /**
             * 3. Decrease available copies
             */
            $stmt = $db->prepare(
                "UPDATE books
                 SET available_copies = available_copies - 1
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $stmt->close();

            $db->commit();

            if (function_exists('logActivity')) {
                logActivity($userId, 'book_borrowed', 'Approved borrow for book: ' . $req['title']);
            }

            $message = 'Request approved. Book issued for ' . $borrowDays . ' days. Due: ' . date('d M Y', strtotime($dueDate));
            $messageType = 'success';

        } catch (Exception $e) {
            $db->rollback();

            $message = 'Error processing request: ' . $e->getMessage();
            $messageType = 'danger';
        }

    } elseif ($req && (int)$req['available_copies'] <= 0) {
        $message = 'Cannot approve — no copies available.';
        $messageType = 'warning';
    } else {
        $message = 'Request not found or already processed.';
        $messageType = 'warning';
    }
}

/**
 * Reject borrow request
 */
if (isset($_GET['reject'])) {
    $reqId = (int)$_GET['reject'];
    $userId = (int)$_SESSION['user_id'];

    $stmt = $db->prepare(
        "UPDATE borrow_requests
         SET status = 'rejected',
             approved_by = ?,
             approved_date = CURDATE()
         WHERE id = ? AND status = 'pending'"
    );

    $stmt->bind_param("ii", $userId, $reqId);
    $stmt->execute();
    $stmt->close();

    if (function_exists('logActivity')) {
        logActivity($userId, 'request_rejected', "Borrow request ID $reqId rejected");
    }

    $message = 'Request rejected.';
    $messageType = 'info';
}

/**
 * Filter
 */
$statusFilter = $_GET['status'] ?? 'pending';

$validStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'all'];

if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'pending';
}

/**
 * Get borrow requests
 */
if ($statusFilter !== 'all') {
 $stmt = $db->prepare(
    "SELECT br.*, 
            u.name AS full_name,
            u.email AS username,
            b.title, 
            b.available_copies,
            a.name AS author_name,
            approver.name AS approved_by_name
     FROM borrow_requests br
     JOIN users u ON br.user_id = u.id
     JOIN books b ON br.book_id = b.id
     JOIN authors a ON b.author_id = a.id
     LEFT JOIN users approver ON br.approved_by = approver.id
     WHERE br.status = ?
     ORDER BY br.request_date DESC
     LIMIT 100"
);


    $stmt->bind_param("s", $statusFilter);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} else {
   $result = $db->query(
    "SELECT br.*, 
            u.name AS full_name,
            u.email AS username,
            b.title, 
            b.available_copies,
            a.name AS author_name,
            approver.name AS approved_by_name
     FROM borrow_requests br
     JOIN users u ON br.user_id = u.id
     JOIN books b ON br.book_id = b.id
     JOIN authors a ON b.author_id = a.id
     LEFT JOIN users approver ON br.approved_by = approver.id
     ORDER BY br.request_date DESC
     LIMIT 100"
);
    

    $requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Pending count
 */
$pendingCount = 0;
$countResult = $db->query("SELECT COUNT(*) AS total FROM borrow_requests WHERE status = 'pending'");

if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    $pendingCount = (int)($countRow['total'] ?? 0);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
<div class="container-fluid">

    <div class="page-header">
        <h1><i class="bi bi-inbox me-2"></i>Borrow Requests</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3">
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $val => $label): ?>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === $val ? 'active' : '' ?>" href="?status=<?= urlencode($val) ?>">
                    <?= e($label) ?>

                    <?php if ($val === 'pending' && $pendingCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Available</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Processed By</th>

                        <?php if ($statusFilter === 'pending'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No requests found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $i => $req): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <div class="fw-600"><?= e($req['full_name']) ?></div>
                                <small class="text-muted"><?= e($req['username']) ?></small>

                                <?php if (!empty($req['student_id'])): ?>
                                    <br>
                                    <small class="text-muted"><?= e($req['student_id']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="fw-600"><?= e($req['title']) ?></div>
                                <small class="text-muted"><?= e($req['author_name']) ?></small>
                            </td>

                            <td class="text-center">
                                <span class="badge-status <?= (int)$req['available_copies'] > 0 ? 'status-available' : 'status-borrowed' ?>">
                                    <?= (int)$req['available_copies'] ?> left
                                </span>
                            </td>

                            <td>
                                <small><?= date('d M Y H:i', strtotime($req['request_date'])) ?></small>
                            </td>

                            <td>
                                <span class="badge-status status-<?= e($req['status']) ?>">
                                    <?= e(ucfirst($req['status'])) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($req['approved_by_name'])): ?>
                                    <small>
                                        <?= e($req['approved_by_name']) ?><br>
                                        <?= !empty($req['approved_date']) ? date('d M Y', strtotime($req['approved_date'])) : '' ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                           <?php if ($statusFilter === 'pending'): ?>
                                <td>
                                <div class="d-flex gap-1">
                                <button type="button"
                                class="btn btn-success btn-sm ajax-request-btn"
                                data-id="<?= (int)$req['id'] ?>"
                                data-action="approve">
                                <i class="bi bi-check-lg me-1"></i>Approve
                                </button>

                                <button type="button"
                                class="btn btn-danger btn-sm ajax-request-btn"
                                data-id="<?= (int)$req['id'] ?>"
                                data-action="reject">
                                <i class="bi bi-x-lg me-1"></i>Reject
                                </button>
                                </div>
                                </td>
                        <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
<script>
document.querySelectorAll('.ajax-request-btn').forEach(button => {
    button.addEventListener('click', function () {
        const requestId = this.dataset.id;
        const action = this.dataset.action;

        if (!confirm('Are you sure you want to ' + action + ' this request?')) {
            return;
        }

        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', action);

        fetch('ajax_update_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);

            if (data.success) {
                const row = this.closest('tr');
                if (row) {
                    row.remove();
                }
            }
        })
        .catch(error => {
            console.error(error);
            alert('AJAX error occurred.');
        });
    });
});
</script>

<?php
$inlineScript = "const BASE_URL = '" . BASE_URL . "';";
require_once __DIR__ . '/../includes/footer.php';
?>