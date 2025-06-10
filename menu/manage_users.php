<?php
session_start();
require_once '../config.php';  // your DB connection

// Check admin login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../includes/login.php');
    exit;
}

$errors = [];
$success = "";

// -- ADD TOURIST --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tourist'])) {
    $name = trim($_POST['tourist_name'] ?? '');
    $email = trim($_POST['tourist_email'] ?? '');
    $phone = trim($_POST['tourist_phone'] ?? '');

    if (!$name || !$email || !$phone) {
        $errors[] = "All tourist fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tourists (name, email, phone) VALUES (:name, :email, :phone)");
            $stmt->execute(['name' => $name, 'email' => $email, 'phone' => $phone]);
            $success = "Tourist added successfully.";
        } catch (PDOException $e) {
            $errors[] = "Failed to add tourist: " . $e->getMessage();
        }
    }
}

// -- DELETE TOURIST --
if (isset($_GET['delete_tourist'])) {
    $del_id = (int)$_GET['delete_tourist'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tourists WHERE id = :id");
        $stmt->execute(['id' => $del_id]);
        $success = "Tourist deleted successfully.";
    } catch (PDOException $e) {
        $errors[] = "Failed to delete tourist: " . $e->getMessage();
    }
}

// -- ADD STAFF --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $username = trim($_POST['staff_username'] ?? '');
    $email = trim($_POST['staff_email'] ?? '');
    $password = $_POST['staff_password'] ?? '';

    if (!$username || !$email || !$password) {
        $errors[] = "All staff fields are required.";
    } else {
        // Simple password hash
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'staff')");
            $stmt->execute(['username' => $username, 'email' => $email, 'password' => $password_hash]);
            $success = "Staff user added successfully.";
        } catch (PDOException $e) {
            $errors[] = "Failed to add staff user: " . $e->getMessage();
        }
    }
}

// -- DELETE STAFF --
if (isset($_GET['delete_staff'])) {
    $del_id = (int)$_GET['delete_staff'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'");
        $stmt->execute(['id' => $del_id]);
        $success = "Staff user deleted successfully.";
    } catch (PDOException $e) {
        $errors[] = "Failed to delete staff user: " . $e->getMessage();
    }
}

// Fetch all tourists
$tourists = $pdo->query("SELECT * FROM tourists ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all staff users
$staffs = $pdo->prepare("SELECT * FROM users WHERE role = 'staff' ORDER BY id DESC");
$staffs->execute();
$staffs = $staffs->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Manage Tourists & Staff</title>
<style>
    body { font-family: Arial, sans-serif; background: #f9fafb; padding: 20px; max-width: 1000px; margin: auto; }
    h1 { color: #1e293b; }
    form { background: #fff; padding: 15px 20px; margin-bottom: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
    label { display: block; margin-top: 10px; font-weight: 600; color: #334155; }
    input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #cbd5e1; }
    input[type="submit"] { margin-top: 15px; background: #3b82f6; color: white; padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700;}
    input[type="submit"]:hover { background: #2563eb; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    th, td { padding: 12px 15px; border: 1px solid #cbd5e1; text-align: left; }
    th { background-color: #1e293b; color: #f1f5f9; }
    tr:nth-child(even) { background-color: #f1f5f9; }
    a.delete-btn { color: #ef4444; font-weight: bold; text-decoration: none; }
    a.delete-btn:hover { text-decoration: underline; }
    .message { padding: 10px 15px; border-radius: 6px; margin-bottom: 20px; }
    .error { background: #fecaca; color: #b91c1c; }
    .success { background: #bbf7d0; color: #15803d; }
</style>
</head>
<body>

<h1>Manage Tourists</h1>

<?php if ($errors): ?>
    <div class="message error">
        <?php foreach ($errors as $error) echo htmlspecialchars($error) . "<br>"; ?>
    </div>
<?php elseif ($success): ?>
    <div class="message success">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Add Tourist Form -->
<form method="post" action="">
    <h2>Add New Tourist</h2>
    <label for="tourist_name">Name</label>
    <input type="text" name="tourist_name" id="tourist_name" required />
    
    <label for="tourist_email">Email</label>
    <input type="email" name="tourist_email" id="tourist_email" required />
    
    <label for="tourist_phone">Phone</label>
    <input type="text" name="tourist_phone" id="tourist_phone" required />
    
    <input type="submit" name="add_tourist" value="Add Tourist" />
</form>

<!-- Tourists List -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($tourists): ?>
            <?php foreach ($tourists as $tourist): ?>
                <tr>
                    <td><?= htmlspecialchars($tourist['id']) ?></td>
                    <td><?= htmlspecialchars($tourist['name']) ?></td>
                    <td><?= htmlspecialchars($tourist['email']) ?></td>
                    <td><?= htmlspecialchars($tourist['phone']) ?></td>
                    <td><a href="?delete_tourist=<?= (int)$tourist['id'] ?>" onclick="return confirm('Delete this tourist?');" class="delete-btn">Delete</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No tourists found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<hr />

<h1>Manage Staff</h1>

<!-- Add Staff Form -->
<form method="post" action="">
    <h2>Add New Staff User</h2>
    <label for="staff_username">Username</label>
    <input type="text" name="staff_username" id="staff_username" required />
    
    <label for="staff_email">Email</label>
    <input type="email" name="staff_email" id="staff_email" required />
    
    <label for="staff_password">Password</label>
    <input type="password" name="staff_password" id="staff_password" required />
    
    <input type="submit" name="add_staff" value="Add Staff" />
</form>

<!-- Staff List -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($staffs): ?>
            <?php foreach ($staffs as $staff): ?>
                <tr>
                    <td><?= htmlspecialchars($staff['id']) ?></td>
                    <td><?= htmlspecialchars($staff['username']) ?></td>
                    <td><?= htmlspecialchars($staff['email']) ?></td>
                    <td><?= htmlspecialchars($staff['role']) ?></td>
                    <td><a href="?delete_staff=<?= (int)$staff['id'] ?>" onclick="return confirm('Delete this staff user?');" class="delete-btn">Delete</a></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No staff users found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
