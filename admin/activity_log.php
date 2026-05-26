<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

requireRole('admin');

$db = getDB();
$pageTitle = 'Activity Log';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;
$filter  = trim($_GET['action'] ?? '');

$where = $filter !== '' ? "WHERE al.action = ?" : "";


if ($filter !== '') {
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM activity_logs al $where");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $totalRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $result = $db->query("SELECT COUNT(*) AS total FROM activity_logs al");
    $totalRow = $result ? $result->fetch_assoc() : ['total' => 0];
}

$totalCount = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalCount / $perPage));


if ($filter !== '') {
    $stmt = $db->prepare(
        "SELECT al.*, u.name AS full_name, u.email AS username
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         $where
         ORDER BY al.created_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bind_param("sii", $filter, $perPage, $offset);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $stmt = $db->prepare(
        "SELECT al.*, u.name AS full_name, u.email AS username
         FROM activity_logs al
         LEFT JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


$actions = [];
$result = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $actions[] = $row['action'];
    }
}

$actionColors = [
    'user_login'       => 'success',
    'user_logout'      => 'secondary',
    'book_borrowed'    => 'primary',
    'book_returned'    => 'info',
    'book_added'       => 'warning',
    'book_updated'     => 'warning',
    'book_deleted'     => 'danger',
    'user_registered'  => 'primary',
    'login_failed'     => 'danger',
    'fine_paid'        => 'success',
    'request_rejected' => 'danger',
    'contact_form'     => 'info',
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
<div class="container-fluid">
    
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1><i class="bi bi-clock-history me-2"></i>Activity Log</h1>
            <div class="text-muted small"><?= number_format($totalCount) ?> entries</div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <small class="text-muted fw-600 me-1">Filter:</small>

                <a href="activity_log.php"
                   class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    All
                </a>

                <?php foreach ($actions as $action): ?>
                    <a href="?action=<?= urlencode($action) ?>" 
                       class="btn btn-sm <?= $filter === $action ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= e(str_replace('_', ' ', $action)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No activity found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $i => $log): ?>
                        <?php $color = $actionColors[$log['action']] ?? 'secondary'; ?>

                        <tr>
                            <td class="text-muted"><?= $offset + $i + 1 ?></td>

                            <td>
                                <small>
                                    <?= date('d M Y', strtotime($log['created_at'])) ?><br>
                                    <?= date('H:i:s', strtotime($log['created_at'])) ?>
                                </small>
                            </td>

                            <td>
                                <?php if (!empty($log['full_name'])): ?>
                                    <div class="fw-600 small"><?= e($log['full_name']) ?></div>
                                    <small class="text-muted"><?= e($log['username'] ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted small">System</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge bg-<?= e($color) ?> text-capitalize">
                                    <?= e(str_replace('_', ' ', $log['action'])) ?>
                                </span>
                            </td>

                            <td>
                                <small><?= e($log['description'] ?: '—') ?></small>
                            </td>

                            <td>
                                <code class="small"><?= e($log['ip_address'] ?? '') ?></code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
                    <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&action=<?= urlencode($filter) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

</div>
</div>

<?php
$inlineScript = "const BASE_URL = '" . BASE_URL . "';";
require_once __DIR__ . '/../includes/footer.php';
?>