<?php
/**
 * AJAX Live Search Endpoint
 * Called by the live search in catalog and navbar.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

$query = trim($_GET['q'] ?? '');

if (mb_strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$db = getDB();
$q = '%' . $query . '%';

$stmt = $db->prepare(
    "SELECT b.id, b.title, a.name AS author, c.name AS category, b.available_copies
     FROM books b
     JOIN authors a ON b.author_id = a.id
     JOIN categories c ON b.category_id = c.id
     WHERE b.title LIKE ? OR a.name LIKE ? OR b.isbn LIKE ?
     ORDER BY b.title
     LIMIT 8"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([]);
    exit();
}

$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($results);
