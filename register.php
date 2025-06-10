<?php
// register.php
require_once __DIR__ . '/includes/db.php'; // Adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    $valid_roles = ['tourist', 'admin', 'personnel'];

    if (empty($username) || empty($email) || empty($passwordRaw) || empty($role)) {
        $error = "Please fill all fields.";
    } elseif (!in_array($role, $valid_roles)) {
        $error = "Invalid role selected.";
    } else {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "Email already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $password, $role])) {
                    $success = "Registration successful! Redirecting to login...";
                    header("Refresh: 3; url=login.php");
                    exit;
                } else {
                    $error = "Error: " . $stmt->errorInfo()[2];
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin: 12px 0 6px;
            text-align: left;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 15px;
        }

        button {
            background-color: #00c6ff;
            border: none;
            padding: 12px;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #0078a0;
        }

        .message {
            margin-bottom: 15px;
            color: red;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .login-link {
            margin-top: 20px;
            font-size: 14px;
        }

        .login-link a {
            color: #0078a0;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>

        <?php if (!empty($error)): ?>
            <div class="message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="role">Role</label>
            <select name="role" id="role" required>
                <option value="">-- Select Role --</option>
                <option value="tourist">Tourist</option>
                <option value="admin">Admin</option>
                <option value="personnel">Personnel</option>
            </select>

            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
