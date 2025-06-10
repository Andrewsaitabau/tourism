<?php
session_start();
require_once '../config.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../includes/login.php');
    exit;
}

// Fetch all staff users
try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'staff' ORDER BY username ASC");
    $stmt->execute();
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch staff members: " . $e->getMessage());
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_title = trim($_POST['task_title'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $assigned_staff_id = $_POST['assigned_staff_id'] ?? [];

    if (empty($task_title)) {
        $error = "Task title is required.";
    } elseif (empty($assigned_staff_id) || !is_array($assigned_staff_id)) {
        $error = "Please select at least one staff member.";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO staff_tasks (task_title, task_description, staff_id, assigned_at) VALUES (:title, :desc, :staff_id, NOW())");

            foreach ($assigned_staff_id as $staff_id) {
                $stmt->execute([
                    ':title' => $task_title,
                    ':desc' => $task_description,
                    ':staff_id' => $staff_id,
                ]);
            }

            $pdo->commit();
            $message = "Task assigned successfully to selected staff.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Failed to assign tasks: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Assign Tasks to Staff</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background: #f9fafb;
    }
    h1 {
        color: #1e293b;
    }
    form {
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 600px;
        box-shadow: 0 2px 8px rgb(0 0 0 / 0.1);
    }
    label {
        display: block;
        margin-top: 15px;
        font-weight: 600;
    }
    input[type="text"], textarea, select {
        width: 100%;
        padding: 8px 10px;
        margin-top: 6px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 16px;
        font-family: inherit;
    }
    select[multiple] {
        height: 120px;
    }
    button {
        margin-top: 20px;
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #2563eb;
    }
    .message {
        margin-bottom: 15px;
        padding: 12px 15px;
        border-radius: 6px;
        font-weight: 600;
    }
    .error {
        background-color: #fecaca;
        color: #991b1b;
    }
    .success {
        background-color: #bbf7d0;
        color: #166534;
    }
    .back-button {
        margin-bottom: 20px;
        display: inline-block;
        color: #3b82f6;
        text-decoration: none;
        font-weight: 600;
    }
</style>
</head>
<body>

<a href="/tourism/admin/dashboard.php" class="back-button">← Back to Dashboard</a>

<h1>Assign Tasks to Staff</h1>

<?php if ($error): ?>
    <div class="message error"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="message success"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<form method="POST" action="">
    <label for="task_title">Task Title:</label>
    <input type="text" name="task_title" id="task_title" required />

    <label for="task_description">Task Description:</label>
    <textarea name="task_description" id="task_description" rows="4"></textarea>

    <label for="assigned_staff_id">Assign to Staff (hold Ctrl / Cmd to select multiple):</label>
    <select name="assigned_staff_id[]" id="assigned_staff_id" multiple required>
        <?php foreach ($staff_members as $staff): ?>
            <option value="<?= htmlspecialchars($staff['id']) ?>"><?= htmlspecialchars($staff['username']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Assign Task</button>
</form>

</body>
</html>
