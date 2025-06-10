<?php
// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Authentication & Authorization
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (!hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

// Default values
$site_name = '';
$base_currency = '';
$exchange_rate = 1.0;

// Fetch current settings from DB
$stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
$settings = $stmt->fetch();

if ($settings) {
    $site_name = $settings['site_name'];
    $base_currency = $settings['base_currency'];
    $exchange_rate = (float)$settings['exchange_rate'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $base_currency = strtoupper(trim($_POST['base_currency'] ?? ''));
    $exchange_rate = filter_input(INPUT_POST, 'exchange_rate', FILTER_VALIDATE_FLOAT);

    // Validate inputs
    if ($site_name === '' || $base_currency === '' || $exchange_rate === false || $exchange_rate <= 0) {
        $_SESSION['error'] = "Please enter a valid site name, a 3-letter currency code, and a positive exchange rate.";
    } elseif (strlen($base_currency) !== 3) {
        $_SESSION['error'] = "Base currency must be a 3-letter code (e.g., USD, KES).";
    } else {
        try {
            if ($settings) {
                $stmt = $pdo->prepare("UPDATE system_settings SET site_name = ?, base_currency = ?, exchange_rate = ? WHERE id = ?");
                $stmt->execute([$site_name, $base_currency, $exchange_rate, $settings['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO system_settings (site_name, base_currency, exchange_rate) VALUES (?, ?, ?)");
                $stmt->execute([$site_name, $base_currency, $exchange_rate]);
            }
            $_SESSION['success'] = "Settings saved successfully!";
            header("Location: system_settings.php");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error saving settings: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>System Settings | Admin Dashboard</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css" />
<style>
    /* Body & container */
    body {
        background: #f5f7fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        color: #333;
    }
    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }
    /* Sidebar */
    aside.sidebar {
        width: 240px;
        background: #1e88e5;
        color: #fff;
        padding: 25px 20px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
    }
    aside.sidebar h3 {
        margin-top: 0;
        margin-bottom: 30px;
        font-weight: 700;
        font-size: 1.4rem;
        letter-spacing: 1px;
    }
    aside.sidebar ul {
        list-style: none;
        padding-left: 0;
        margin: 0;
        flex-grow: 1;
    }
    aside.sidebar ul li {
        margin-bottom: 15px;
    }
    aside.sidebar ul li a {
        color: #cce4ff;
        text-decoration: none;
        font-weight: 500;
        padding: 8px 12px;
        border-radius: 6px;
        display: block;
        transition: background-color 0.25s ease;
    }
    aside.sidebar ul li a:hover {
        background-color: #1565c0;
        color: #fff;
    }
    aside.sidebar ul li.active a {
        background-color: #0d47a1;
        font-weight: 700;
        color: #fff;
    }
    aside.sidebar ul li:last-child a {
        margin-top: auto;
        color: #ffeb3b;
        font-weight: 600;
    }
    aside.sidebar ul li:last-child a:hover {
        color: #fff176;
        background-color: transparent;
    }
    /* Main content */
    main.content {
        flex-grow: 1;
        padding: 40px 30px;
        background: #fff;
        box-sizing: border-box;
    }
    /* Settings form container */
    .settings-form {
        background: #ffffff;
        max-width: 600px;
        margin: 0 auto;
        padding: 35px 40px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        border: 1px solid #e0e0e0;
    }
    .settings-form h2 {
        font-size: 2rem;
        margin-bottom: 30px;
        text-align: center;
        font-weight: 700;
        color: #1976d2;
        letter-spacing: 0.05em;
    }
    /* Form groups */
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 1.05rem;
        color: #555;
    }
    .form-group input {
        width: 100%;
        padding: 12px 16px;
        font-size: 1rem;
        border-radius: 8px;
        border: 1.6px solid #ccc;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
        font-family: inherit;
    }
    .form-group input:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 6px #a3c1f9;
    }
    /* Button */
    .btn-primary {
        width: 100%;
        padding: 14px 0;
        background-color: #1976d2;
        border: none;
        color: #fff;
        font-size: 1.15rem;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        box-shadow: 0 4px 8px rgba(25, 118, 210, 0.4);
    }
    .btn-primary:hover {
        background-color: #115293;
        box-shadow: 0 6px 15px rgba(17, 82, 147, 0.6);
    }
    /* Alerts */
    .alert {
        max-width: 600px;
        margin: 20px auto 30px;
        padding: 15px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        text-align: center;
        box-sizing: border-box;
        user-select: none;
    }
    .error {
        background-color: #ffebee;
        color: #c62828;
        border: 1.5px solid #ef9a9a;
        box-shadow: 0 0 8px rgba(198, 40, 40, 0.3);
    }
    .success {
        background-color: #e8f5e9;
        color: #2e7d32;
        border: 1.5px solid #a5d6a7;
        box-shadow: 0 0 8px rgba(46, 125, 50, 0.3);
    }
    /* Responsive adjustments */
    @media (max-width: 700px) {
        aside.sidebar {
            width: 180px;
            padding: 20px 15px;
        }
        main.content {
            padding: 30px 20px;
        }
        .settings-form {
            padding: 30px 25px;
            margin: 20px;
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
            <li><a href="create_staff.php">Create Staff</a></li>
            <li class="active"><a href="system_settings.php">System Settings</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="content">
        <div class="settings-form">
            <h2>System Settings</h2>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input
                        type="text"
                        id="site_name"
                        name="site_name"
                        value="<?= htmlspecialchars($site_name); ?>"
                        placeholder="Enter site name"
                        required
                        autocomplete="off"
                    />
                </div>

                <div class="form-group">
                    <label for="base_currency">Base Currency (3-letter code, e.g. KES, USD)</label>
                    <input
                        type="text"
                        id="base_currency"
                        name="base_currency"
                        value="<?= htmlspecialchars($base_currency); ?>"
                        maxlength="3"
                        placeholder="USD"
                        required
                        autocomplete="off"
                        pattern="[A-Za-z]{3}"
                        title="Three letter currency code (e.g., USD)"
                    />
                </div>

                <div class="form-group">
                    <label for="exchange_rate">Exchange Rate (to USD)</label>
                    <input
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        id="exchange_rate"
                        name="exchange_rate"
                        value="<?= htmlspecialchars($exchange_rate); ?>"
                        required
                    />
                </div>

                <button type="submit" class="btn-primary">Save Settings</button>
            </form>
        </div>
    </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
