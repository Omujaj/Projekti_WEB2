<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  
define('DB_NAME', 'library_system');  // databaza ekzistuese
define('FINE_PER_DAY', 1.00);
define('BORROW_DAYS', 14);
define('BASE_URL', 'http://localhost/ProjektiWeb2');
define('APP_URL', BASE_URL);
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die('<div style="background:#fee;padding:20px;border:2px solid red;font-family:sans-serif;">
                <h2>Database Connection Failed</h2>
                <p>' . htmlspecialchars($conn->connect_error) . '</p>
                <p>Please ensure MySQL is running in XAMPP and the database is imported.</p>
            </div>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}