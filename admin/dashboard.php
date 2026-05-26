<?php
/**
 * Admin Dashboard
 * 
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Admin Dashboard';

// ---- Fetch statistics ----
$stats = [];

$result = $db->query("SELECT COUNT(*) as c FROM books"); $stats['books'] = $result->fetch_assoc()['c'];
$result = $db->query("SELECT COUNT(*) as c FROM users WHERE role_id = 3"); $stats['students'] = $result->fetch_assoc()['c'];
$result = $db->query("SELECT COUNT(*) as c FROM borrow_requests WHERE status = 'approved'"); $stats['borrowed'] = $result->fetch_assoc()['c'];
$result = $db->query("SELECT COUNT(*) as c FROM borrow_requests WHERE status = 'pending'"); $stats['pending'] = $result->fetch_assoc()['c'];
$result = $db->query("SELECT COUNT(*) as c FROM borrow_requests WHERE status = 'overdue'"); $stats['overdue'] = $result->fetch_assoc()['c'];
$result = $db->query("SELECT COUNT(*) as c FROM fines WHERE status = 'unpaid'"); $stats['fines'] = $result->fetch_assoc()['c'];

// ---- Most borrowed books ----
$topBooks = $db->query("
    SELECT b.title, a.name as author, COUNT(br.id) as borrow_count
    FROM borrow_requests br
    JOIN books b ON br.book_id = b.id
    JOIN authors a ON b.author_id = a.id
    GROUP BY b.id ORDER BY borrow_count DESC LIMIT 5
");

// ---- Recent activity ----
$recentLogs = $db->query("
    SELECT al.*, u.name as user_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC LIMIT 10
");

// ---- Chart data: borrows per month  ----
$borrowsByMonth = $db->query("
    SELECT 
        DATE_FORMAT(request_date, '%b') as month,
        DATE_FORMAT(request_date, '%Y-%m') as month_order,
        COUNT(*) as cnt
    FROM borrow_requests
    WHERE request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_order, month
    ORDER BY month_order ASC
");
$chartLabels = []; $chartData = [];
while ($row = $borrowsByMonth->fetch_assoc()) {
    $chartLabels[] = $row['month'];
    $chartData[]   = $row['cnt'];
}

// ---- Category distribution ----
$catDist = $db->query("
    SELECT c.name, COUNT(b.id) as cnt
    FROM categories c LEFT JOIN books b ON c.id = b.category_id
    GROUP BY c.id ORDER BY cnt DESC LIMIT 6
");
$catLabels = []; $catData = [];
while ($row = $catDist->fetch_assoc()) {
    $catLabels[] = $row['name'];
    $catData[]   = $row['cnt'];
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="page-wrapper">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= sanitize($_SESSION['user_name']) ?>! Here's an overview of the library.</p>
    </div>

    <!-- ---- STAT CARDS ---- -->
    <div class="stats-grid">
        <div class="stat-card stat-blue">
            <div class="stat-icon">📚</div>
            <div class="stat-info"><h3><?= $stats['books'] ?></h3><p>Total Books</p></div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-icon">👥</div>
            <div class="stat-info"><h3><?= $stats['students'] ?></h3><p>Registered Students</p></div>
        </div>
        <div class="stat-card stat-amber">
            <div class="stat-icon">📖</div>
            <div class="stat-info"><h3><?= $stats['borrowed'] ?></h3><p>Currently Borrowed</p></div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-icon">⏳</div>
            <div class="stat-info"><h3><?= $stats['pending'] ?></h3><p>Pending Requests</p></div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info"><h3><?= $stats['overdue'] ?></h3><p>Overdue Books</p></div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-icon">💰</div>
            <div class="stat-info"><h3><?= $stats['fines'] ?></h3><p>Unpaid Fines</p></div>
        </div>
    </div>

    <!-- ---- CHARTS ---- -->
    <div class="charts-grid">
        <div class="card">
            <div class="card-header"><h3>Borrow Activity (Last 6 Months)</h3></div>
            <div class="card-body"><canvas id="borrowChart" height="200"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Books by Category</h3></div>
            <div class="card-body"><canvas id="categoryChart" height="200"></canvas></div>
        </div>
    </div>

    <!-- ---- BOTTOM SECTION ---- -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1.5rem;">

        <!-- Most Borrowed Books -->
        <div class="card">
            <div class="card-header"><h3>Most Borrowed Books</h3></div>
            <div class="card-body">
                <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Count</th></tr></thead>
                    <tbody>
                    <?php $rank=1; while($row = $topBooks->fetch_assoc()): ?>
                        <tr>
                            <td><?= $rank++ ?></td>
                            <td><?= sanitize($row['title']) ?></td>
                            <td><?= sanitize($row['author']) ?></td>
                            <td><span class="badge badge-approved"><?= $row['borrow_count'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity Log -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Activity</h3>
                <a href="reports.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body" style="padding:1rem 1.5rem;">
                <ul class="log-list">
                <?php while($log = $recentLogs->fetch_assoc()):
                    $icons = ['user_login'=>'🔑','user_logout'=>'🚪','book_borrowed'=>'📖',
                              'book_returned'=>'✅','book_added'=>'➕','user_registered'=>'👤',
                              'login_failed'=>'❌','borrow_approved'=>'✔️','book_reserved'=>'📌'];
                    $icon = $icons[$log['action']] ?? '📋';
                ?>
                <li class="log-item">
                    <span class="log-icon"><?= $icon ?></span>
                    <div class="log-text">
                        <strong><?= sanitize($log['user_name'] ?? 'System') ?></strong>
                        <?= sanitize($log['description']) ?>
                        <span class="log-time"><?= date('M j, g:i a', strtotime($log['created_at'])) ?></span>
                    </div>
                </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>

    </div>
</div>

<!-- ---- CHART ---- -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const borrowCtx = document.getElementById('borrowChart').getContext('2d');
new Chart(borrowCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Borrow Requests',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: '#0f1e3699',
            borderColor: '#0f1e36',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{stepSize:1}}} }
});

const catCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(catCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode($catData) ?>,
            backgroundColor: ['#0f1e36','#e8a020','#22c55e','#3b82f6','#8b5cf6','#ef4444'],
            borderWidth: 2,
        }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}} }
});
</script>

<?php require_once '../includes/footer.php'; ?>
