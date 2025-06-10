<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if user role is driver
if ($_SESSION['role'] !== 'driver') {
    // Optionally, redirect to their correct dashboard or an error page
    header('Location: ../unauthorized.php'); // or dashboard.php, etc.
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Driver Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f4f8;
            margin: 0;
            padding: 0;
        }
        header {
            background: #2196f3;
            color: white;
            padding: 15px 20px;
            text-align: center;
        }
        main {
            padding: 20px;
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .logout-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            margin-top: -35px;
            margin-right: 20px;
            font-weight: bold;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>

<header>
    <h1>Driver Dashboard</h1>
    <a href="../logout.php" class="logout-btn">Logout</a>
</header>

<main>
    <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>
    <p>This is your driver dashboard. Here you can manage your tasks, view your schedule, and update your status.</p>

    <!-- Example content -->
    <section>
        <h3>Your Tasks</h3>
        <ul>
            <li>Task 1: Pick up tourist from airport</li>
            <li>Task 2: Transport tourists to hotel</li>
            <li>Task 3: Vehicle maintenance check</li>
        </ul>
    </section>

    <section>
        <h3>Status</h3>
        <p>Current Status: <strong>Available</strong></p>
        <button>Update Status</button>
    </section>
</main>

</body>
</html>
