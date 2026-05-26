<?php
/**
 * Librarian - Borrow Requests
 * View and approve/reject borrow requests from students.
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole(['admin','librarian']);

$db = getDB();
$pageTitle = 'Borrow Requests';
$message = $error = '';

// AJAX approve/reject is handled in ../admin/ajax_update_request.php

// Fetch requests
$filter = $_GET['filter'] ?? 'pending';
$allowed = ['pending','approved','rejected','returned','overdue','all'];
if (!in_array($filter, $allowed)) $filter = 'pending';

$query = "
    SELECT br.*, u.name as user_name, u.email as user_email, b.title as book_title,
           a.name as author_name, lib.name as librarian_name
    FROM borrow_requests br
    JOIN users u ON br.user_id = u.id
    JOIN books b ON br.book_id = b.id
    JOIN authors a ON b.author_id = a.id
    LEFT JOIN users lib ON br.approved_by = lib.id
";
if ($filter !== 'all') $query .= " WHERE br.status = '$filter'";
$query .= " ORDER BY br.request_date DESC";
$requests = $db->query($query);

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>Borrow Requests</h1>
        <p>Review and manage student borrow requests.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

    <div id="ajaxMessage"></div>

    <!-- Filter  -->
    <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <?php foreach (['pending','approved','rejected','returned','overdue','all'] as $f): ?>
        <a href="?filter=<?= $f ?>" class="btn btn-sm <?= $filter===$f ? 'btn-primary' : 'btn-outline' ?>">
            <?= ucfirst($f) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><?= ucfirst($filter) ?> Requests (<span id="requestCount"><?= $requests->num_rows ?></span>)</h3>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Student</th><th>Book</th><th>Requested</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="borrowsTableBody">
                <?php if ($requests->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">No requests found.</td></tr>
                <?php else: $i=1; while ($req = $requests->fetch_assoc()): ?>
                <tr data-row-id="<?= $req['id'] ?>">
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= sanitize($req['user_name']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= sanitize($req['user_email']) ?></small>
                    </td>
                    <td>
                        <strong><?= sanitize($req['book_title']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= sanitize($req['author_name']) ?></small>
                    </td>
                    <td><?= date('M j, Y', strtotime($req['request_date'])) ?></td>
                    <td>
                        <?php if ($req['due_date']): ?>
                            <?php $overdue = strtotime($req['due_date']) < time() && $req['status']==='approved'; ?>
                            <span style="color:<?= $overdue ? 'var(--danger)' : 'inherit' ?>">
                                <?= date('M j, Y', strtotime($req['due_date'])) ?>
                                <?= $overdue ? '⚠️' : '' ?>
                            </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $req['status'] ?>"><?= strtoupper($req['status']) ?></span></td>
                    <td>
                        <div class="table-actions">
                        <?php if ($req['status'] === 'pending'): ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-success ajax-request-btn"
                                data-id="<?= $req['id'] ?>"
                                data-action="approve">
                                Approve
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-danger ajax-request-btn"
                                data-id="<?= $req['id'] ?>"
                                data-action="reject">
                                Reject
                            </button>
                        <?php elseif ($req['status'] === 'approved'): ?>
                            <a href="../librarian/returns.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-primary">Process Return</a>
                        <?php else: ?>—<?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
            </div>
            <div id="borrowsTableBodyPagination" class="pagination" style="padding:1rem;"></div>
        </div>
</div>
</div>

<script>
function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('.ajax-request-btn');

    if (!button) {
        return;
    }

    const requestId = button.dataset.id;
    const action = button.dataset.action;

    if (action === 'reject' && !confirm('Reject this request?')) {
        return;
    }

    button.disabled = true;

    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('action', action);

    fetch('../admin/ajax_update_request.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const messageBox = document.getElementById('ajaxMessage');

        if (data.success) {
            messageBox.innerHTML = `
                <div class="alert alert-success">
                    ${escapeHtml(data.message)}
                </div>
            `;

            const row = document.querySelector(`tr[data-row-id="${requestId}"]`);
            if (row) {
                row.remove();
            }

            const countElement = document.getElementById('requestCount');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent, 10) || 0;
                countElement.textContent = Math.max(currentCount - 1, 0);
            }

            const tbody = document.getElementById('borrowsTableBody');
            if (tbody && tbody.querySelectorAll('tr[data-row-id]').length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);">
                            No requests found.
                        </td>
                    </tr>
                `;
            }
        } else {
            messageBox.innerHTML = `
                <div class="alert alert-error">
                    ${escapeHtml(data.message)}
                </div>
            `;
            button.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('ajaxMessage').innerHTML = `
            <div class="alert alert-error">
                AJAX error occurred. Check Console.
            </div>
        `;
        console.error(error);
        button.disabled = false;
    });
});
</script>

<?php
$inlineScript = "const BASE_URL = '" . BASE_URL . "';";
require_once __DIR__ . '/../includes/footer.php';
?>