<?php
session_start();
include 'includes/header.php';
?>
<div class="hero-section">
    <?php include 'includes/navbar.php'; ?>
    
    <main class="hero-content">
        <div class="hero-center-icon">📚</div>
        <h1 class="hero-title">Library Management<br>System</h1>
        <p class="hero-subtitle">
            A complete digital library platform. Browse thousands of books, manage borrows<br>
            and returns, track reservations, and more — all in one place.
        </p>
        <div class="hero-actions">
            <?php if(isset($_SESSION['user_email'])): ?>
                <a href="pages/catalog.php" class="btn btn-primary btn-large">Browse Catalog</a>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="pages/reservations.php" class="btn btn-outline-light btn-large">Manage Reservations</a>
                <?php else: ?>
                    <a href="pages/my_borrows.php" class="btn btn-outline-light btn-large">My Borrows</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="auth/register.php" class="btn btn-primary btn-large">Get Started</a>
                <a href="auth/login.php" class="btn btn-outline-light btn-large">Sign In</a>
            <?php endif; ?>
        </div>
    </main>
</div>

<section class="stats-section">
    <div class="stat-item">
        <h2>12</h2>
        <p>Books in Catalog</p>
    </div>
    <div class="stat-item">
        <h2>5</h2>
        <p>Registered Users</p>
    </div>
    <div class="stat-item">
        <h2>0</h2>
        <p>Books Borrowed</p>
    </div>
</section>

<section class="services-section">
    <div class="services-header">
        <h2>Library Services</h2>
        <p>Manage books, borrowing, and reservations through a simple and organized digital library system.</p>
    </div>

    <div class="services-grid">
        <div class="service-card">
            <div class="card-icon">👤</div>
            <h3>Member Accounts</h3>
            <p>Students and staff can create accounts to access the library catalog, borrow books, and manage their activity.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">📚</div>
            <h3>Book Catalog</h3>
            <p>Browse the complete library catalog including book titles, authors, categories, and availability.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">📕</div>
            <h3>Borrow Books</h3>
            <p>Users can request to borrow books online and pick them up at the library once approved.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">📥</div>
            <h3>Return Management</h3>
            <p>Librarians can manage book returns, update availability, and track borrowed items.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">📌</div>
            <h3>Book Reservations</h3>
            <p>If a book is currently borrowed, users can reserve it and receive it when it becomes available.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">⏰</div>
            <h3>Due Dates</h3>
            <p>Each borrowed book includes a due date so users know when it should be returned.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">📊</div>
            <h3>Library Dashboard</h3>
            <p>Administrators can monitor books, members, and borrowing activity from a central dashboard.</p>
        </div>
        <div class="service-card">
            <div class="card-icon">🔍</div>
            <h3>Search Books</h3>
            <p>Quickly search the library catalog by title, author, or category.</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>