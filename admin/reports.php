<?php
/**
 * Admin - Reports & Activity Logs
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Reports & Logs';

// Fetch all activity logs 
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page-1) * $perPage;

$total   = $db->query("SELECT COUNT(*) as c FROM activity_logs")->fetch_assoc()['c'];
$pages   = max(1, ceil($total / $perPage));

$stmt    = $db->prepare("
    SELECT al.*, u.name as user_name, u.email as user_email
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

// Fines summary
$fines = $db->query("
    SELECT f.*, u.name as user_name, b.title as book_title, br.due_date, br.return_date
    FROM fines f
    JOIN users u ON f.user_id = u.id
    JOIN borrow_requests br ON f.borrow_request_id = br.id
    JOIN books b ON br.book_id = b.id
    ORDER BY f.created_at DESC LIMIT 20
");

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>Reports & Activity Logs</h1>
        <p>System activity, fines, and borrow history.</p>
    </div>

    <!-- FINES TABLE -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3>Fines</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Student</th><th>Book</th><th>Days Overdue</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                <?php $i=1; while ($fine = $fines->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= sanitize($fine['user_name']) ?></td>
                    <td><?= sanitize($fine['book_title']) ?></td>
                    <td><?= $fine['days_overdue'] ?> day(s)</td>
                    <td><strong>€<?= number_format($fine['amount'], 2) ?></strong></td>
                    <td><span class="badge badge-<?= $fine['status'] === 'paid' ? 'approved' : ($fine['status'] === 'waived' ? 'returned' : 'rejected') ?>"><?= strtoupper($fine['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($fine['created_at'])) ?></td>
                    <td>
                        <?php if ($fine['status'] === 'unpaid'): ?>
                        <form method="POST" action="update_fine.php" style="display:inline;">
                            <input type="hidden" name="fine_id" value="<?= $fine['id'] ?>">
                            <button name="status" value="paid" class="btn btn-sm btn-success">Mark Paid</button>
                            <button name="status" value="waived" class="btn btn-sm btn-outline">Waive</button>
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

    <!-- ACTIVITY LOG -->
    <div class="card">
        <div class="card-header"><h3>Activity Log (Total: <?= $total ?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                <?php $i=($page-1)*$perPage+1; while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= sanitize($log['user_name'] ?? 'Guest') ?></td>
                    <td><code style="font-size:.75rem;background:var(--cream-mid);padding:.15rem .4rem;border-radius:3px;"><?= sanitize($log['action']) ?></code></td>
                    <td style="font-size:.82rem;"><?= sanitize($log['description']) ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= sanitize($log['ip_address']) ?></td>
                    <td style="font-size:.78rem;"><?= date('M j Y, g:i a', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <!-- SERVER-SIDE PAGINATION -->
            <div class="pagination" style="padding:1rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>" class="page-btn">← Prev</a>
                <?php endif; ?>
                <?php for ($p=max(1,$page-2); $p<=min($pages,$page+2); $p++): ?>
                    <a href="?page=<?= $p ?>" class="page-btn <?= $p==$page?'active':'' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                    <a href="?page=<?= $page+1 ?>" class="page-btn">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
