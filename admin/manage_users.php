<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Only allow admin access
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    if (!empty($userId)) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success'] = "User deleted successfully.";
        header("Location: manage_users.php");
        exit();
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Users | Tourism System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css" />
    <style>
        body {
            background: #f9fafb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            color: #333;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        aside.sidebar {
            width: 220px;
            background: #1e293b;
            color: #f1f5f9;
            padding: 30px 20px;
            box-shadow: 2px 0 6px rgba(0,0,0,0.1);
        }
        aside.sidebar h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            font-size: 1.3rem;
        }
        aside.sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        aside.sidebar ul li {
            margin-bottom: 15px;
        }
        aside.sidebar ul li a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 600;
            display: block;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        aside.sidebar ul li.active a,
        aside.sidebar ul li a:hover {
            background: #3b82f6;
            color: white;
        }
        main.content {
            flex-grow: 1;
            padding: 30px 40px;
            background: white;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.05);
            border-radius: 8px;
            margin: 20px;
        }
        main.content h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: 700;
            color: #1e293b;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
            font-size: 0.95rem;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        thead th {
            background: #e2e8f0;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #334155;
            border-radius: 8px 8px 0 0;
            user-select: none;
        }
        tbody tr {
            background: #f8fafc;
            transition: background-color 0.3s ease;
            border-radius: 6px;
        }
        tbody tr:hover {
            background-color: #e0e7ff;
        }
        tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            color: #475569;
        }
        tbody td.actions {
            width: 130px;
        }
        .btn-danger {
            background-color: #ef4444;
            color: white;
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        .success {
            background-color: #dcfce7;
            color: #166534;
            padding: 12px 20px;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 600;
            box-shadow: 0 0 10px #bbf7d0aa;
        }
        em {
            color: #94a3b8;
            font-style: normal;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            aside.sidebar {
                width: 100%;
                padding: 15px 20px;
            }
            main.content {
                margin: 10px 15px;
                padding: 20px;
            }
            table, thead th, tbody td {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="dashboard-container">
        <aside class="sidebar">
            <h3>Admin Dashboard</h3>
            <ul>
                <li><a href="dashboard.php">Overview</a></li>
                <li class="active"><a href="manage_users.php">Manage Users</a></li>
                <li><a href="create_staff.php">Create Staff</a></li>
                <li><a href="system_settings.php">System Settings</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Manage Users</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst($user['role']) ?></td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td class="actions">
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn-danger">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <em>Protected</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
