<?php
/**
 * Admin - Manage Users
 * 
 */
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole('admin');

$db = getDB();
$pageTitle = 'Manage Users';
$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != ?");
        $myId = $_SESSION['user_id'];
        $stmt->bind_param("ii", $user_id, $myId);
        $stmt->execute(); $stmt->close();
        $message = 'User status updated.';
    }
    if ($action === 'change_role') {
        $role_id = (int)($_POST['role_id'] ?? 3);
        $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ? AND id != ?");
        $myId = $_SESSION['user_id'];
        $stmt->bind_param("iii", $role_id, $user_id, $myId);
        $stmt->execute(); $stmt->close();
        $message = 'User role updated.';
    }
    if ($action === 'delete_user') {
        $myId = $_SESSION['user_id'];
        if ($user_id === $myId) { $error = 'You cannot delete your own account.'; }
        else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute(); $stmt->close();
            $message = 'User deleted.';
        }
    }
}

$users = $db->query("
    SELECT u.*, r.name as role_name,
    (SELECT COUNT(*) FROM borrow_requests br WHERE br.user_id=u.id AND br.status='approved') as active_borrows
    FROM users u JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
");
$roles = $db->query("SELECT * FROM roles ORDER BY id");

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>
<div class="page-wrapper">
    <div class="page-header">
        <h1>Manage Users</h1>
        <p>View and manage all registered users.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= sanitize($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

    <div class="catalog-controls" style="margin-bottom:1rem;">
        <div class="search-box" style="max-width:360px;">
            <input type="text" id="userSearch" placeholder="Search users by name, email…">
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>All Users (<?= $users->num_rows ?>)</h3></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Active Borrows</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody id="usersTableBody">
                <?php $i=1; while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= sanitize($user['name']) ?></strong></td>
                    <td><?= sanitize($user['email']) ?></td>
                    <td><?= sanitize($user['phone'] ?? '—') ?></td>
                    <td><span class="user-badge user-badge-<?= $user['role_name'] ?>"><?= strtoupper($user['role_name']) ?></span></td>
                    <td><?= $user['active_borrows'] ?></td>
                    <td><span class="badge <?= $user['is_active'] ? 'badge-approved' : 'badge-rejected' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <!-- Change role -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role_id" onchange="this.form.submit()" style="padding:.25rem .5rem;border:1.5px solid var(--border);border-radius:4px;font-size:.78rem;">
                                    <?php $roles->data_seek(0); while($r=$roles->fetch_assoc()): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id']==$user['role_id']?'selected':'' ?>><?= ucfirst($r['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </form>
                            <!-- Toggle status -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                    <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
            <div id="usersTableBodyPagination" class="pagination" style="padding:1rem;"></div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>