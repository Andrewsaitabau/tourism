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

$allowedRoles = ['manager', 'driver', 'chef'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

        if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
            throw new Exception("All fields are required");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        if (!in_array($role, $allowedRoles)) {
            throw new Exception("Invalid role selected");
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            throw new Exception("Username or email already exists");
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $role]);

        $_SESSION['success'] = "Staff account created successfully!";
        header("Location: create_staff.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: create_staff.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Create Staff | Tourism System</title>
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
            max-width: 700px;
        }
        main.content h2 {
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: 700;
            color: #1e293b;
            font-size: 1.8rem;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .alert.error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert.success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        form.create-user-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1 1 45%;
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #334155;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 5px #3b82f6aa;
        }
        .form-actions {
            margin-top: 10px;
        }
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            font-weight: 700;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
            }
            .form-group {
                flex: 1 1 100%;
            }
            main.content {
                margin: 10px 15px;
                padding: 20px;
                max-width: 100%;
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
                <li><a href="manage_users.php">Manage Users</a></li>
                <li class="active"><a href="create_staff.php">Create Staff</a></li>
                <li><a href="system_settings.php">System Settings</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="content">
            <h2>Create New Staff Account</h2>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="create-user-form" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autocomplete="username" />
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" />
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" />
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1 1 100%;">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="manager">Manager</option>
                            <option value="driver">Driver</option>
                            <option value="chef">Chef</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_user" class="btn-primary">Create Account</button>
                </div>
            </form>
        </main>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
