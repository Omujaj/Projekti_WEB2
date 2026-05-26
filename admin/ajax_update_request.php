<?php
/**
 * AJAX Update Borrow Request
 * Approve / Reject without page refresh
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

requireRole(['admin', 'librarian']);

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

$requestId = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
    exit();
}

$librarianId = (int)$_SESSION['user_id'];

if ($action === 'reject') {
    $stmt = $db->prepare(
        "UPDATE borrow_requests
         SET status = 'rejected',
             approved_by = ?,
             approved_date = CURDATE()
         WHERE id = ? AND status = 'pending'"
    );

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database prepare failed.'
        ]);
        exit();
    }

    $stmt->bind_param("ii", $librarianId, $requestId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if (function_exists('logActivity')) {
            logActivity($librarianId, 'request_rejected', "Borrow request ID $requestId rejected via AJAX.");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Request rejected successfully.',
            'status' => 'rejected'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found or already processed.'
        ]);
    }

    $stmt->close();
    exit();
}

if ($action === 'approve') {
    $stmt = $db->prepare(
        "SELECT br.*, b.available_copies, b.title
         FROM borrow_requests br
         JOIN books b ON br.book_id = b.id
         WHERE br.id = ? AND br.status = 'pending'
         LIMIT 1"
    );

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Database prepare failed.'
        ]);
        exit();
    }

    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found or already processed.'
        ]);
        exit();
    }

    if ((int)$request['available_copies'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No copies available for this book.'
        ]);
        exit();
    }

    try {
        $db->begin_transaction();

        $studentId = (int)$request['user_id'];
        $bookId = (int)$request['book_id'];

        $borrowDays = defined('BORROW_DAYS') ? (int)BORROW_DAYS : 14;
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+' . $borrowDays . ' days'));

        $stmt = $db->prepare(
            "UPDATE borrow_requests
             SET status = 'approved',
                 approved_by = ?,
                 approved_date = CURDATE(),
                 due_date = ?
             WHERE id = ? AND status = 'pending'"
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare request update.');
        }

        $stmt->bind_param("isi", $librarianId, $dueDate, $requestId);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            "INSERT INTO borrow_history
             (borrow_request_id, user_id, book_id, borrowed_date, due_date)
             VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare borrow history insert.');
        }

        $stmt->bind_param("iiiss", $requestId, $studentId, $bookId, $borrowDate, $dueDate);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare(
            "UPDATE books
             SET available_copies = available_copies - 1
             WHERE id = ?"
        );

        if (!$stmt) {
            throw new Exception('Failed to prepare book update.');
        }

        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $stmt->close();

        $db->commit();

        if (function_exists('logActivity')) {
            logActivity($librarianId, 'book_borrowed', 'Approved borrow request for book: ' . $request['title']);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Request approved successfully.',
            'status' => 'approved'
        ]);
        exit();

    } catch (Exception $e) {
        $db->rollback();

        echo json_encode([
            'success' => false,
            'message' => 'Error approving request: ' . $e->getMessage()
        ]);
        exit();
    }
}