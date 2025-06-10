<?php
// back_to_dashboard.php
session_start();

// Optional: verify admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../includes/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Back to Admin Dashboard</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f0f4f8;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .container {
        background: white;
        padding: 40px 60px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
    }
    button {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 14px 28px;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #2563eb;
    }
</style>
</head>
<body>

<div class="container">
    <h1>Return to Admin Dashboard</h1>
    <button onclick="window.location.href='/tourism/admin/dashboard.php'">Go Back to Dashboard</button>
</div>

</body>
</html>
