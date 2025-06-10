<?php
session_start();

// Adjust these paths as needed depending on your project structure
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

// Simple isAdmin function based on session
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect if not admin
if (!isAdmin()) {
    header('Location: /unauthorized.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = filter_var($_POST['to'], FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);

    if (!$to) {
        $message = "Invalid email address.";
    } elseif (empty($subject)) {
        $message = "Subject cannot be empty.";
    } elseif (empty($body)) {
        $message = "Message body cannot be empty.";
    } else {
        // Send email
        $headers = "From: noreply@yourdomain.com\r\n" .
                   "Reply-To: noreply@yourdomain.com\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($to, $subject, $body, $headers)) {
            $message = "Email sent successfully to $to";
        } else {
            $message = "Failed to send email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Send Email - Admin | Tourism System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body {
        padding: 2rem;
        background-color: #f0f4f8;
    }
    .container {
        max-width: 600px;
        margin: auto;
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    .message {
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 5px;
        font-weight: 600;
    }
    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">Send Email to Tourist or Staff</h2>

    <?php if ($message): ?>
        <div class="message <?= (strpos($message, 'successfully') !== false) ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="to" class="form-label">Recipient Email</label>
            <input type="email" id="to" name="to" class="form-control" placeholder="example@example.com" required />
        </div>
        <div class="mb-3">
            <label for="subject" class="form-label">Subject</label>
            <input type="text" id="subject" name="subject" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="body" class="form-label">Message</label>
            <textarea id="body" name="body" class="form-control" rows="6" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Email</button>
    </form>

    <a href="/admin/dashboard.php" class="btn btn-secondary w-100 mt-3">Back to Dashboard</a>
</div>
</body>
</html>
