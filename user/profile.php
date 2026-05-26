<?php
/**
 * User Profile Page
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$pageTitle = 'My Profile';

$message = '';
$messageType = '';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Update profile
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $newPass   = $_POST['new_password'] ?? '';
    $currPass  = $_POST['current_password'] ?? '';

    if ($name === '' || $email === '') {
        $message = 'Name and email are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $messageType = 'danger';
    } else {
        $stmt = $db->prepare(
            "UPDATE users 
             SET name = ?, email = ?, phone = ?, address = ?
             WHERE id = ?"
        );

        if ($stmt) {
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $userId);
            $stmt->execute();
            $stmt->close();

            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
        }

        if ($newPass !== '') {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $currentHash = $row['password'] ?? '';

            if (!password_verify($currPass, $currentHash)) {
                $message = 'Current password is incorrect.';
                $messageType = 'danger';
            } elseif (strlen($newPass) < 8) {
                $message = 'New password must be at least 8 characters.';
                $messageType = 'danger';
            } else {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);

                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $newHash, $userId);
                $stmt->execute();
                $stmt->close();

                $message .= ' Password updated.';
            }
        }

        if ($messageType !== 'danger') {
            $message = 'Profile updated successfully!' . $message;
            $messageType = 'success';
        }
    }
}

/**
 * Current user
 */
$user = getCurrentUser();

if (!$user) {
    header('Location: ' . appUrl() . '/auth/logout.php');
    exit();
}

$userName = $user['name'] ?? 'User';
$userEmail = $user['email'] ?? '';

/**
 * Stats
 */
$stmt = $db->prepare("SELECT COUNT(*) AS total FROM borrow_history WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalBorrows = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM borrow_requests WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeBorrows = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM fines WHERE user_id = ? AND status = 'unpaid'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalFines = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
<div class="container" style="max-width:900px">

    <div class="page-header">
        <h1><i class="bi bi-person-circle me-2"></i>My Profile</h1>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= e($messageType) ?> alert-dismissible fade show">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card text-center p-3 mb-3">
                <div class="mx-auto mb-3" style="width:80px;height:80px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;font-family:var(--font-display)">
                    <?= e(strtoupper(substr($userName, 0, 1))) ?>
                </div>

                <h5 class="mb-0"><?= e($userName) ?></h5>
                <p class="text-muted"><?= e($userEmail) ?></p>

                <span class="badge <?= ($user['role_name'] ?? '') === 'admin' ? 'bg-danger' : (($user['role_name'] ?? '') === 'librarian' ? 'bg-warning text-dark' : 'bg-primary') ?> mb-2">
                    <?= e(ucfirst($user['role_name'] ?? 'student')) ?>
                </span>

                <?php if (!empty($user['student_id'])): ?>
                    <div class="text-muted small">Student ID: <?= e($user['student_id']) ?></div>
                <?php endif; ?>

                <?php if (!empty($user['created_at'])): ?>
                    <div class="text-muted small">
                        Member since: <?= date('M Y', strtotime($user['created_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart me-1"></i>My Stats
                </div>

                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between">
                            <span>Total Borrows</span>
                            <strong><?= $totalBorrows ?></strong>
                        </div>

                        <div class="list-group-item d-flex justify-content-between">
                            <span>Active Borrows</span>
                            <strong class="text-primary"><?= $activeBorrows ?></strong>
                        </div>

                        <div class="list-group-item d-flex justify-content-between">
                            <span>Unpaid Fines</span>
                            <strong class="<?= $totalFines > 0 ? 'text-danger' : 'text-success' ?>">
                                €<?= number_format($totalFines, 2) ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil-square me-1"></i>Edit Profile
                </div>

                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       required
                                       value="<?= e($userName) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       required
                                       value="<?= e($userEmail) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       value="<?= e($user['phone'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="text-muted">
                            Change Password <small>(leave blank to keep current)</small>
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password"
                                       id="password"
                                       name="new_password"
                                       class="form-control"
                                       minlength="8">
                                <div id="passwordStrength" class="form-text"></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>

</div>
</div>

<?php
$inlineScript = "const BASE_URL = '" . appUrl() . "';";
require_once __DIR__ . '/../includes/footer.php';
?>