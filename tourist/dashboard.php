<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

redirectIfNotLoggedIn();
if (getUserRole() !== 'tourist') {
    header('Location: ../index.php');
    exit();
}

// Get tourist details
$stmt = $pdo->prepare("SELECT t.* FROM tourists t JOIN users u ON t.user_id = u.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tourist = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Dashboard | Tourism System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f7fa;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #002b5c;
            color: white;
            padding: 2rem 1rem;
        }

        .sidebar h3 {
            margin-bottom: 2rem;
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
        }

        .sidebar li {
            margin: 1rem 0;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
        }

        .sidebar .active a {
            font-weight: bold;
            text-decoration: underline;
        }

        .content {
            flex-grow: 1;
            padding: 2rem;
        }

        .dashboard-cards {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 250px;
        }

        .card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .card p {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0069d9;
        }

        .recent-activities {
            margin-top: 3rem;
        }

        .recent-activities h3 {
            margin-bottom: 1rem;
        }

        .recent-activities ul {
            list-style-type: disc;
            padding-left: 1.5rem;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="dashboard-container">
    <aside class="sidebar">
        <h3>Tourist Dashboard</h3>
        <ul>
            <li class="active"><a href="dashboard.php">Overview</a></li>
            <li><a href="bookings.php">My Bookings</a></li>
            <li><a href="#">My Profile</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </aside>

    <main class="content">
        <h2>Welcome, <?= htmlspecialchars($tourist['full_name'] ?? 'Tourist'); ?></h2>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Upcoming Trips</h3>
                <p>5</p>
            </div>
            <div class="card">
                <h3>Pending Bookings</h3>
                <p>2</p>
            </div>
            <div class="card">
                <h3>Total Spent</h3>
                <p id="usd-amount">$1,250 <span id="kes-amount" style="font-size: 0.9rem; color: #555;"></span></p>
            </div>
        </div>

        <section class="recent-activities">
            <h3>Recent Activities</h3>
            <ul>
                <li>Booked a hotel in Nairobi (Yesterday)</li>
                <li>Reserved a tour guide (3 days ago)</li>
                <li>Paid for transportation (1 week ago)</li>
            </ul>
        </section>
    </main>
</div>

<script>
    // Assume static conversion rate (1 USD = 130 KES), or fetch from an API later
    const usdAmount = 1250;
    const conversionRate = 130;
    const kesAmount = usdAmount * conversionRate;
    document.getElementById('kes-amount').innerText = `(KSh ${kesAmount.toLocaleString()})`;
</script>

</body>
</html>
