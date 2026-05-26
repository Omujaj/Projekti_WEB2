<?php
require_once '../config/database.php';
require_once '../config/auth_helper.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fine_id = (int)($_POST['fine_id'] ?? 0);
    $status  = in_array($_POST['status'], ['paid','waived']) ? $_POST['status'] : null;
    if ($fine_id && $status) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE fines SET status = ?, paid_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $fine_id);
        $stmt->execute(); $stmt->close();
        logActivity('fine_updated', "Fine #$fine_id marked as $status.");
    }
}
header('Location: reports.php');
exit();
