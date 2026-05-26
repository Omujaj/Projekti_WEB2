<?php
/**
 * OpenLibrary External API
 * Fetch book data by ISBN
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

requireRole('admin');

header('Content-Type: application/json; charset=utf-8');

$isbn = trim($_GET['isbn'] ?? '');

if ($isbn === '') {
    echo json_encode([
        'success' => false,
        'message' => 'ISBN is required.'
    ]);
    exit();
}

$isbn = preg_replace('/[^0-9Xx]/', '', $isbn);

if (strlen($isbn) < 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid ISBN.'
    ]);
    exit();
}

$url = 'https://openlibrary.org/isbn/' . urlencode($isbn) . '.json';

$response = @file_get_contents($url);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Could not connect to OpenLibrary API.'
    ]);
    exit();
}

$data = json_decode($response, true);

if (!$data || isset($data['error'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Book not found in OpenLibrary.'
    ]);
    exit();
}

$title = $data['title'] ?? '';
$publishDate = $data['publish_date'] ?? '';
$pages = $data['number_of_pages'] ?? '';
$publishers = '';

if (!empty($data['publishers']) && is_array($data['publishers'])) {
    $publisherNames = [];

    foreach ($data['publishers'] as $publisher) {
        if (is_array($publisher) && isset($publisher['name'])) {
            $publisherNames[] = $publisher['name'];
        } elseif (is_string($publisher)) {
            $publisherNames[] = $publisher;
        }
    }

    $publishers = implode(', ', $publisherNames);
}

$year = '';

if ($publishDate && preg_match('/\d{4}/', $publishDate, $matches)) {
    $year = $matches[0];
}

echo json_encode([
    'success' => true,
    'isbn' => $isbn,
    'title' => $title,
    'publish_date' => $publishDate,
    'year' => $year,
    'pages' => $pages,
    'publisher' => $publishers
]);
exit();