<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../includes/login.php');
    exit;
}

require_once '../config.php';

try {
    // Fetch all menu items without price column
    $stmt = $pdo->query("SELECT id, name FROM menu_items ORDER BY id ASC");
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch menu items: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Menu Items</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f9fafb;
        margin: 0;
        padding: 20px;
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
    <h1>Manage Menu Items</h1>
</header>

<!-- Back to Dashboard Button -->
<button class="back-button" onclick="window.location.href='/tourism/admin/dashboard.php'">
    ← Back to Dashboard
</button>

<!-- Menu Items Table -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Menu Item Name</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($menu_items)): ?>
            <?php foreach ($menu_items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center;">No menu items found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
