<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../includes/login.php');
    exit;
}

require_once '../config.php';

// Handle status update if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['new_status'];

    // Validate status input (allow only specific statuses)
    $valid_statuses = ['Approved', 'Rejected', 'Completed'];
    if (in_array($new_status, $valid_statuses, true)) {
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $booking_id,
            ]);
            $message = "Booking #$booking_id updated to '$new_status'.";
        } catch (PDOException $e) {
            $error = "Failed to update booking status: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status selected.";
    }
}

// Fetch all bookings
try {
    $stmt = $pdo->query("
        SELECT b.id, b.tour_name, b.date, b.status, c.name AS customer_name
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        ORDER BY b.date DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch bookings: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Bookings</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f9fafb;
        margin: 0; padding: 20px;
    }
    header {
        background-color: #1e293b;
        color: white;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 6px;
    }
    h1 {
        margin: 0;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 12px 15px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    th {
        background-color: #3b82f6;
        color: white;
    }
    tr:hover {
        background-color: #f0f9ff;
    }
    form {
        margin: 0;
    }
    button.status-btn {
        margin-right: 5px;
        padding: 6px 12px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        color: white;
        transition: background-color 0.3s ease;
    }
    button.approve { background-color: #22c55e; }
    button.approve:hover { background-color: #16a34a; }
    button.reject { background-color: #ef4444; }
    button.reject:hover { background-color: #b91c1c; }
    button.complete { background-color: #3b82f6; }
    button.complete:hover { background-color: #2563eb; }
    .message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: 600;
    }
    .success { background-color: #bbf7d0; color: #166534; }
    .error { background-color: #fecaca; color: #991b1b; }
    .back-button {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease;
        margin-bottom: 20px;
        display: inline-block;
    }
    .back-button:hover {
        background-color: #2563eb;
    }
</style>
</head>
<body>

<header>
    <h1>Manage Bookings</h1>
</header>

<button class="back-button" onclick="window.location.href='/tourism/admin/dashboard.php'">
    ← Back to Dashboard
</button>

<?php if (!empty($message)): ?>
    <div class="message success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>Booking ID</th>
            <th>Tour Name</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['id']) ?></td>
                    <td><?= htmlspecialchars($booking['tour_name']) ?></td>
                    <td><?= htmlspecialchars($booking['date']) ?></td>
                    <td><?= htmlspecialchars($booking['customer_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($booking['status']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
                            <button type="submit" name="new_status" value="Approved" class="status-btn approve">Approve</button>
                            <button type="submit" name="new_status" value="Rejected" class="status-btn reject">Reject</button>
                            <button type="submit" name="new_status" value="Completed" class="status-btn complete">Complete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No bookings found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
