CREATE DATABASE IF NOT EXISTS library_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_system;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL DEFAULT 3,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    description TEXT,
    year INT,
    cover_image VARCHAR(255) DEFAULT 'no-cover.png',
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TABLE book_copies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    copy_number VARCHAR(50),
    condition_status ENUM('good','fair','damaged') DEFAULT 'good',
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE borrow_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('pending','approved','rejected','returned','overdue') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_date DATE,
    due_date DATE,
    return_date DATE,
    notes TEXT,
    approved_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE borrow_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrow_request_id INT NOT NULL,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    borrowed_date DATE,
    due_date DATE,
    returned_date DATE,
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('active','fulfilled','cancelled','expired') DEFAULT 'active',
    queue_position INT,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

CREATE TABLE fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    borrow_request_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    days_overdue INT NOT NULL,
    status ENUM('unpaid','paid','waived') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (borrow_request_id) REFERENCES borrow_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- SEED DATA
-- ============================================================
INSERT INTO roles (name, description) VALUES
('admin','Full system access'),
('librarian','Manage borrows and returns'),
('student','Browse and borrow books');

INSERT INTO categories (name, description) VALUES
('Computer Science','Programming, algorithms, databases'),
('Mathematics','Algebra, calculus, statistics'),
('Physics','Classical and modern physics'),
('Literature','Novels, poetry, classics'),
('History','World history and civilizations'),
('Economics','Micro and macroeconomics'),
('Psychology','Human behavior and mental health'),
('Philosophy','Ethics, logic, metaphysics');

INSERT INTO authors (name, bio) VALUES
('Robert C. Martin','Known as Uncle Bob, software engineer and author.'),
('Donald Knuth','Professor emeritus at Stanford.'),
('George Orwell','English novelist and essayist.'),
('Yuval Noah Harari','Israeli historian and professor.'),
('Stephen Hawking','Theoretical physicist and cosmologist.'),
('Plato','Ancient Greek philosopher.'),
('Adam Smith','Scottish economist.'),
('Carl Jung','Swiss psychiatrist.');

INSERT INTO books (author_id, category_id, title, isbn, description, year, total_copies, available_copies) VALUES
(1,1,'Clean Code','978-0132350884','A handbook of agile software craftsmanship.',2008,3,3),
(1,1,'The Clean Coder','978-0137081073','A code of conduct for professional programmers.',2011,2,2),
(2,1,'The Art of Computer Programming Vol.1','978-0201896831','Fundamental algorithms.',1997,2,2),
(3,4,'Nineteen Eighty-Four','978-0451524935','Dystopian social science fiction novel.',1949,4,4),
(3,4,'Animal Farm','978-0451526342','An allegorical novella.',1945,3,3),
(4,5,'Sapiens','978-0062316097','A brief history of humankind.',2011,3,3),
(4,5,'Homo Deus','978-0062464316','A brief history of tomorrow.',2015,2,2),
(5,3,'A Brief History of Time','978-0553380163','From the Big Bang to Black Holes.',1988,4,4),
(6,7,'The Republic','978-0140455113','A Socratic dialogue on justice.',-380,3,3),
(7,6,'The Wealth of Nations','978-0140432084','An inquiry into the nature and causes.',1776,2,2),
(8,7,'Man and His Symbols','978-0385052214','The last work of Carl Jung.',1964,3,3),
(2,2,'Concrete Mathematics','978-0201558029','A foundation for computer science.',1994,2,2);

-- Seed demo users (password for all: Admin@1234 - stored as bcrypt)
INSERT INTO users (role_id, name, email, password, phone) VALUES
(1,'System Admin','admin@library.com','$2y$12$NSCVc7RP.GaPZJwn.YiJBuyqMV4lWP47go3bEdD1GfP3fSmmZ5tFW','555-0001'),
(2,'Jane Librarian','librarian@library.com','$2y$12$NSCVc7RP.GaPZJwn.YiJBuyqMV4lWP47go3bEdD1GfP3fSmmZ5tFW','555-0002'),
(3,'John Student','student@library.com','$2y$12$NSCVc7RP.GaPZJwn.YiJBuyqMV4lWP47go3bEdD1GfP3fSmmZ5tFW','555-0003');
-- Default password for ALL seed accounts: Admin@1234
