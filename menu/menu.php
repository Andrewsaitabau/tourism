<?php
// menu.php - Stylish Admin Navigation Menu

// Get the current script filename for active link highlight
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Menu</title>
    <style>
        body {
            background-color: #f3f4f6;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-menu {
            background-color: #1e293b; /* dark blue-gray */
            padding: 12px 30px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            border-radius: 8px;
            max-width: 900px;
            margin: 20px auto;
        }
        .admin-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        .admin-menu ul li a {
            color: #cbd5e1;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 600;
            font-size: 1rem;
            transition: 
                background-color 0.3s ease,
                color 0.3s ease,
                box-shadow 0.3s ease;
            box-shadow: inset 0 0 0 0 transparent;
        }
        .admin-menu ul li a:hover,
        .admin-menu ul li a.active {
            background-color: #3b82f6; /* bright blue */
            color: #fff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<nav class="admin-menu" aria-label="Admin Navigation">
    <ul>
        <li><a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
        <li><a href="manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : '' ?>">Manage Users</a></li>
        <li><a href="menu_items.php" class="<?= $current_page === 'menu_items.php' ? 'active' : '' ?>">Menu Items</a></li>
        <li><a href="bookings.php" class="<?= $current_page === 'bookings.php' ? 'active' : '' ?>">Bookings</a></li>
        <li><a href="assign_tasks.php" class="<?= $current_page === 'assign_tasks.php' ? 'active' : '' ?>">Assign Tasks</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</nav>

</body>
</html>
