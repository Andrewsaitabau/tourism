<?php
session_start();
require_once '../includes/db.php'; // Your PDO DB connection

// Auth checks
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
if ($_SESSION['role'] !== 'chef') {
    header('Location: ../unauthorized.php');
    exit();
}

$username = $_SESSION['username'];

// Handle AJAX request to update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $itemId = intval($_POST['item_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';

    $allowedStatuses = ['not_ready', 'ready', 'finished'];
    if ($itemId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE menu_items SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $itemId]);
            echo json_encode(['success' => true, 'message' => 'Status updated']);
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }
}

// Handle search query (GET)
$searchTerm = trim($_GET['search'] ?? '');

// Fetch menu items from DB
try {
    if ($searchTerm !== '') {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE name LIKE ? ORDER BY name ASC");
        $stmt->execute(["%$searchTerm%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY name ASC");
    }
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menuItems = [];
    $error = "Failed to fetch menu items: " . $e->getMessage();
}

function displayStatusBadge($status) {
    $colors = [
        'not_ready' => '#ff6f61',
        'ready' => '#4caf50',
        'finished' => '#2196f3'
    ];
    $labels = [
        'not_ready' => 'Not Ready',
        'ready' => 'Ready',
        'finished' => 'Finished'
    ];
    $color = $colors[$status] ?? '#999';
    $label = $labels[$status] ?? 'Unknown';
    return "<span style='background:$color;color:#fff;padding:4px 8px;border-radius:4px;font-weight:bold;'>$label</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Restaurant Dashboard - Chef</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff8f0;
            margin: 0; padding: 0;
        }
        header {
            background: #ff5722;
            color: white;
            padding: 15px 20px;
            text-align: center;
            position: relative;
        }
        .logout-btn {
            position: absolute;
            right: 20px; top: 15px;
            background: #b71c1c;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: #7f0000;
        }
        main {
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
        }
        h2 {
            color: #d84315;
            margin-bottom: 20px;
        }
        #search-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        #search-input {
            flex-grow: 1;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        #search-btn {
            background: #ff5722;
            border: none;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        #search-btn:hover {
            background: #e64a19;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #ffe0b2;
        }
        select.status-select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-weight: 600;
            cursor: pointer;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
            text-transform: capitalize;
        }
        .msg {
            margin-top: 15px;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
        }
        .msg.success {
            background-color: #c8e6c9;
            color: #256029;
        }
        .msg.error {
            background-color: #ffcdd2;
            color: #c62828;
        }
    </style>
</head>
<body>

<header>
    <h1>Restaurant Dashboard</h1>
    <a href="../logout.php" class="logout-btn">Logout</a>
</header>

<main>
    <h2>Welcome, <?= htmlspecialchars($username) ?>!</h2>

    <form id="search-form" method="GET" action="restaurant.php">
        <input type="text" id="search-input" name="search" placeholder="Search food items..." value="<?= htmlspecialchars($searchTerm) ?>" />
        <button id="search-btn" type="submit">Search</button>
        <?php if($searchTerm !== ''): ?>
            <button type="button" id="clear-btn">Clear</button>
        <?php endif; ?>
    </form>

    <?php if (!empty($error)): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (count($menuItems) === 0): ?>
        <p>No food items found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Food Item</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menuItems as $item): ?>
                <tr data-item-id="<?= $item['id'] ?>">
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td>
                        <select class="status-select">
                            <option value="not_ready" <?= $item['status'] === 'not_ready' ? 'selected' : '' ?>>Not Ready</option>
                            <option value="ready" <?= $item['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                            <option value="finished" <?= $item['status'] === 'finished' ? 'selected' : '' ?>>Finished</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div id="message" class="msg" style="display:none;"></div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Handle status change
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', (e) => {
            const selectElem = e.target;
            const newStatus = selectElem.value;
            const tr = selectElem.closest('tr');
            const itemId = tr.getAttribute('data-item-id');

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('item_id', itemId);
            formData.append('new_status', newStatus);

            fetch('restaurant.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('message');
                if(data.success) {
                    messageDiv.className = 'msg success';
                    messageDiv.textContent = data.message;
                } else {
                    messageDiv.className = 'msg error';
                    messageDiv.textContent = data.message;
                }
                messageDiv.style.display = 'block';
                setTimeout(() => messageDiv.style.display = 'none', 3000);
            })
            .catch(() => {
                const messageDiv = document.getElementById('message');
                messageDiv.className = 'msg error';
                messageDiv.textContent = 'An error occurred while updating status.';
                messageDiv.style.display = 'block';
                setTimeout(() => messageDiv.style.display = 'none', 3000);
            });
        });
    });

    // Clear search button
    const clearBtn = document.getElementById('clear-btn');
    if(clearBtn) {
        clearBtn.addEventListener('click', () => {
            window.location.href = 'restaurant.php';
        });
    }
});
</script>

</body>
</html>
