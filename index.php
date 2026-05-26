<?php

require_once 'config/database.php';
require_once 'config/auth_helper.php';


if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin')      header('Location: admin/dashboard.php');
    elseif ($role === 'librarian') header('Location: librarian/borrow_requests.php');
    else                        header('Location: user/catalog.php');
    exit();
}

$db = getDB();
$totalBooks   = $db->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
$totalUsers   = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalBorrows = $db->query("SELECT COUNT(*) as c FROM borrow_history")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library —  Library Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, var(--navy-light) 100%);
            color: white; text-align: center; padding: 6rem 2rem 5rem;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-content { position: relative; max-width: 700px; margin: 0 auto; }
        .hero h1 { font-size: clamp(2.5rem, 6vw, 4.5rem); color: var(--amber); margin-bottom: 1rem; }
        .hero p { font-size: 1.15rem; color: rgba(255,255,255,.75); margin-bottom: 2rem; line-height: 1.8; }
        .hero-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .hero-btns .btn { padding: .85rem 2rem; font-size: 1rem; }

        .landing-stats { display: flex; justify-content: center; gap: 4rem; padding: 3rem 2rem; background: var(--cream-mid); flex-wrap: wrap; }
        .lstat { text-align: center; }
        .lstat h3 { font-size: 2.5rem; color: var(--navy); margin: 0; }
        .lstat p  { color: var(--text-muted); font-size: .9rem; }

        .features { max-width: 1100px; margin: 0 auto; padding: 4rem 2rem; text-align: center; }
        .features h2 { margin-bottom: .75rem; }
        .features > p { color: var(--text-muted); margin-bottom: 3rem; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; text-align: left; }
        .feature-card { background: white; padding: 1.75rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); transition: transform .2s, box-shadow .2s; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .feature-icon { font-size: 2rem; margin-bottom: .75rem; }
        .feature-card h4 { margin-bottom: .4rem; }
        .feature-card p { color: var(--text-muted); font-size: .875rem; margin: 0; }

        .landing-nav { background: var(--navy); display: flex; align-items: center; justify-content: space-between; padding: 1rem 2rem; }
        .landing-nav .nav-brand { font-family: var(--font-display); color: var(--amber); font-size: 1.4rem; font-weight: 700; }
        .landing-nav-links { display: flex; gap: 1rem; }
    </style>
</head>
<body>
    
    <nav class="landing-nav">
        <span class="nav-brand">📚 Library</span>
        <div class="landing-nav-links">
            <a href="auth/login.php" class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,.4);">Login</a>
            <a href="auth/register.php" class="btn btn-amber">Register</a>
        </div>
    </nav>

 
    <section class="hero">
        <div class="hero-content">
            <div style="font-size:5rem;margin-bottom:1rem;">📚</div>
            <h1>Library Management System</h1>
            <p>A complete digital library platform. Browse thousands of books, manage borrows and returns, track reservations, and more — all in one place.</p>
            <div class="hero-btns">
                <a href="auth/register.php" class="btn btn-amber">Get Started</a>
                <a href="auth/login.php"    class="btn btn-outline" style="color:white;border-color:rgba(255,255,255,.5);">Sign In</a>
            </div>
        </div>
    </section>


    <div class="landing-stats">
        <div class="lstat"><h3><?= $totalBooks ?></h3><p>Books in Catalog</p></div>
        <div class="lstat"><h3><?= $totalUsers ?></h3><p>Registered Users</p></div>
        <div class="lstat"><h3><?= $totalBorrows ?></h3><p>Books Borrowed</p></div>
    </div>

  
    <section class="features">
    <h2>Library Services</h2>
    <p>Manage books, borrowing, and reservations through a simple and organized digital library system.</p>

    <div class="features-grid">

        <div class="feature-card">
            <div class="feature-icon">👤</div>
            <h4>Member Accounts</h4>
            <p>Students and staff can create accounts to access the library catalog, borrow books, and manage their activity.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📚</div>
            <h4>Book Catalog</h4>
            <p>Browse the complete library catalog including book titles, authors, categories, and availability.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📕</div>
            <h4>Borrow Books</h4>
            <p>Users can request to borrow books online and pick them up at the library once approved.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📥</div>
            <h4>Return Management</h4>
            <p>Librarians can manage book returns, update availability, and track borrowed items.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📌</div>
            <h4>Book Reservations</h4>
            <p>If a book is currently borrowed, users can reserve it and receive it when it becomes available.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h4>Due Dates</h4>
            <p>Each borrowed book includes a due date so users know when it should be returned.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h4>Library Dashboard</h4>
            <p>Administrators can monitor books, members, and borrowing activity from a central dashboard.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔎</div>
            <h4>Search Books</h4>
            <p>Quickly search the library catalog by title, author, or category.</p>
        </div>

    </div>
</section>

    <footer class="footer">
        <div class="footer-inner">
            <p>© <?= date('Y') ?> Library —  Library Management System</p>
        </div>
    </footer>
</body>
</html>
