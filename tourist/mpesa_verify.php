<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

redirectIfNotLoggedIn();

$message = '';

// Handle M-Pesa code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mpesa_code = trim($_POST['mpesa_code']);

    if (empty($mpesa_code)) {
        $message = "Please enter the M-Pesa transaction code.";
    } else {
        // Find latest pending payment for this user
        try {
            $stmt = $pdo->prepare("
                SELECT p.id, p.booking_id
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                WHERE b.user_id = ? AND p.status = 'Pending'
                ORDER BY p.id DESC LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                // Update payment record with mpesa code and mark as Completed (or Verified)
                $update = $pdo->prepare("UPDATE payments SET mpesa_code = ?, status = 'Completed' WHERE id = ?");
                $update->execute([$mpesa_code, $payment['id']]);

                $message = "Payment verified successfully! Thank you.";
            } else {
                $message = "No pending payment found to update.";
            }
        } catch (PDOException $e) {
            $message = "Error updating payment: " . $e->getMessage();
        }
    }
} else {
    $message = "Invalid request.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>M-Pesa Code Verification | Tourism System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f0f4f8;
        padding: 2rem;
    }
    .container {
        max-width: 450px;
        margin: 0 auto;
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    .message {
        margin-bottom: 1.5rem;
        font-weight: 600;
        padding: 1rem;
        border-radius: 5px;
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
    a.btn {
        margin-top: 1rem;
    }
</style>
</head>
<body>

<div class="container">
    <h1>M-Pesa Payment Verification</h1>

    <div class="message <?= (strpos($message, 'successfully') !== false) ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
    </div>

    <a href="tourist_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
</div>

</body>
</html>
