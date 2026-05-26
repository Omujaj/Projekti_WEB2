<?php
/**
 * Contact Form - Email Sending
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_helper.php';

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $body === '') {
        $message = 'All fields are required.';
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
        $messageType = 'danger';
    } else {
        $to = 'admin@library.com';
        $mailSubject = 'LibraryViona Contact: ' . $subject;

        $mailBody = "Name: $name\n";
        $mailBody .= "Email: $email\n\n";
        $mailBody .= "Message:\n$body\n";

        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";

        $sent = @mail($to, $mailSubject, $mailBody, $headers);

        if ($sent) {
            $message = 'Message sent successfully.';
            $messageType = 'success';
        } else {
            $message = 'Message was processed. On XAMPP, real email sending may require mail server configuration.';
            $messageType = 'warning';
        }

        if (function_exists('logActivity') && isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'contact_form', 'User submitted contact form.');
        }
    }
}

$pageTitle = 'Contact';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="container" style="max-width:800px">

        <div class="page-header">
            <h1>Contact Library</h1>
            <p class="text-muted">Send a message to the library administration.</p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= e($messageType) ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">

                    <div class="form-group mb-3">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Send Message
                    </button>

                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>