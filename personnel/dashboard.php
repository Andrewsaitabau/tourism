<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
$role = getUserRole();
if (!in_array($role, ['driver', 'chef', 'manager'])) {
    header('Location: ../index.php');
    exit();
}

// Get personnel details
$stmt = $pdo->prepare("SELECT p.* FROM personnel p JOIN users u ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$person = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Dashboard | Tourism System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <aside class="sidebar">
            <h3><?= ucfirst($role); ?> Dashboard</h3>
            <ul>
                <li class="active"><a href="dashboard.php">Overview</a></li>
                <?php if ($role === 'driver'): ?>
                    <li><a href="driver.php">My Rides</a></li>
                <?php elseif ($role === 'chef' || $role === 'manager'): ?>
                    <li><a href="restaurant.php">Restaurant</a></li>
                <?php endif; ?>
                <li><a href="#">My Profile</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>
        
        <main class="content">
            <h2>Welcome, <?= htmlspecialchars($person['full_name'] ?? ucfirst($role)); ?></h2>
            
            <div class="dashboard-cards">
                <?php if ($role === 'driver'): ?>
                    <div class="card">
                        <h3>Scheduled Rides</h3>
                        <p>8</p>
                    </div>
                    <div class="card">
                        <h3>Completed Today</h3>
                        <p>3</p>
                    </div>
                    <div class="card">
                        <h3>Earnings</h3>
                        <p>$120</p>
                    </div>
                <?php elseif ($role === 'chef'): ?>
                    <div class="card">
                        <h3>Orders Today</h3>
                        <p>24</p>
                    </div>
                    <div class="card">
                        <h3>Special Requests</h3>
                        <p>5</p>
                    </div>
                <?php elseif ($role === 'manager'): ?>
                    <div class="card">
                        <h3>Daily Revenue</h3>
                        <p>$1,250</p>
                    </div>
                    <div class="card">
                        <h3>Staff On Duty</h3>
                        <p>8</p>
                    </div>
                    <div class="card">
                        <h3>Reservations</h3>
                        <p>15</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <section class="notifications">
                <h3>Notifications</h3>
                <ul>
                    <li>New booking request received</li>
                    <li>System maintenance scheduled for tomorrow</li>
                </ul>
            </section>
        </main>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>