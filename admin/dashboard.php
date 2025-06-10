<?php
// Start session and includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check authentication and admin role
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if (!hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

// Allowed staff roles including tourguide
$allowedStaffRoles = ['manager', 'driver', 'chef', 'tourguide'];

// Handle email sending form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    try {
        $recipient_type = filter_input(INPUT_POST, 'recipient_type', FILTER_SANITIZE_STRING);
        $recipient_id = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        
        if (!$recipient_type || !$subject || !$message) {
            throw new Exception("All fields are required");
        }
        
        // Get recipient email based on type
        $recipient_email = '';
        $recipient_name = '';
        
        if ($recipient_type === 'staff') {
            if (!$recipient_id) {
                throw new Exception("Please select a staff member");
            }
            $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmt->execute([$recipient_id]);
            $recipient = $stmt->fetch();
            
            if (!$recipient) {
                throw new Exception("Staff member not found");
            }
            
            $recipient_email = $recipient['email'];
            $recipient_name = $recipient['username'];
        } elseif ($recipient_type === 'booking') {
            if (!$recipient_id) {
                throw new Exception("Please select a booking");
            }
            $stmt = $pdo->prepare("
                SELECT u.email, u.username 
                FROM bookings b
                JOIN users u ON b.customer_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$recipient_id]);
            $recipient = $stmt->fetch();
            
            if (!$recipient) {
                throw new Exception("Booking customer not found");
            }
            
            $recipient_email = $recipient['email'];
            $recipient_name = $recipient['username'];
        } else {
            throw new Exception("Invalid recipient type");
        }
        
        // Prepare email headers
        $headers = "From: " . ADMIN_EMAIL . "\r\n";
        $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send email
        $email_sent = mail($recipient_email, $subject, nl2br($message), $headers);
        
        if ($email_sent) {
            $_SESSION['success'] = "Email sent successfully to " . htmlspecialchars($recipient_name) . "!";
        } else {
            throw new Exception("Failed to send email. Please check your server mail configuration.");
        }
        
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle new staff creation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

        if (!$username || !$email || !$password || !$confirm_password || !$role) {
            throw new Exception("All fields are required");
        }
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        if (!in_array($role, $allowedStaffRoles)) {
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
        header("Location: dashboard.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    try {
        $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        
        if (!$booking_id || !$status) {
            throw new Exception("Invalid booking data");
        }
        
        $allowed_statuses = ['pending', 'approved', 'rejected', 'completed'];
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("Invalid status");
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $booking_id]);
        
        $_SESSION['success'] = "Booking status updated successfully!";
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle staff assignment to booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_staff'])) {
    try {
        $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $staff_id = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
        $role_assigned = filter_input(INPUT_POST, 'role_assigned', FILTER_SANITIZE_STRING);
        
        if (!$booking_id || !$staff_id || !$role_assigned) {
            throw new Exception("All fields are required");
        }
        
        if (!in_array($role_assigned, $allowedStaffRoles)) {
            throw new Exception("Invalid role selected");
        }
        
        // Check if staff member has the assigned role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        
        if (!$staff) {
            throw new Exception("Staff member not found");
        }
        
        if ($staff['role'] !== $role_assigned) {
            throw new Exception("Selected staff member does not have the required role");
        }
        
        $stmt = $pdo->prepare("UPDATE bookings SET assigned_staff_id = ?, role_assigned = ? WHERE id = ?");
        $stmt->execute([$staff_id, $role_assigned, $booking_id]);
        
        $_SESSION['success'] = "Staff assigned to booking successfully!";
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Handle task assignment to staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    try {
        $staff_id = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
        $task_description = filter_input(INPUT_POST, 'task_description', FILTER_SANITIZE_STRING);
        $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);
        
        if (!$staff_id || !$task_description || !$due_date) {
            throw new Exception("All fields are required");
        }
        
        // Validate due date
        $due_timestamp = strtotime($due_date);
        if (!$due_timestamp) {
            throw new Exception("Invalid due date");
        }
        
        $stmt = $pdo->prepare("INSERT INTO staff_tasks (staff_id, task_description, due_date, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$staff_id, $task_description, date('Y-m-d H:i:s', $due_timestamp)]);
        
        $_SESSION['success'] = "Task assigned to staff successfully!";
        header("Location: dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
}

// Fetch total users and staff count
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$staff_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();

// Fetch recent users
$recent_users = $pdo->query("SELECT username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Fetch menu items and statuses for admin to view chef updates
try {
    $menu_stmt = $pdo->query("SELECT id, name, description, status FROM menu_items ORDER BY name ASC");
    $menu_items = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $menu_items = [];
    $_SESSION['error'] = "Failed to fetch menu items: " . $e->getMessage();
}

// Fetch bookings with customer and assigned staff info
try {
    $bookings_stmt = $pdo->query("
        SELECT 
            b.id, 
            u.username AS customer_name, 
            b.booking_date, 
            b.status, 
            b.assigned_staff_id, 
            s.username AS assigned_staff_name,
            b.role_assigned
        FROM bookings b
        LEFT JOIN users u ON b.customer_id = u.id
        LEFT JOIN users s ON b.assigned_staff_id = s.id
        ORDER BY b.booking_date DESC
        LIMIT 10
    ");
    $bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $_SESSION['error'] = "Failed to fetch bookings: " . $e->getMessage();
}

// Fetch all staff members for assignment dropdowns
try {
    $staff_stmt = $pdo->query("SELECT id, username, role FROM users WHERE role IN ('manager', 'driver', 'chef', 'tourguide') ORDER BY role, username");
    $staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $staff_members = [];
    $_SESSION['error'] = "Failed to fetch staff members: " . $e->getMessage();
}

// Fetch recent tasks assigned to staff
try {
    $tasks_stmt = $pdo->query("
        SELECT t.id, t.task_description, t.due_date, t.status, u.username AS staff_name
        FROM staff_tasks t
        JOIN users u ON t.staff_id = u.id
        ORDER BY t.due_date DESC
        LIMIT 5
    ");
    $recent_tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_tasks = [];
    $_SESSION['error'] = "Failed to fetch tasks: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard | Tourism System</title>
    <style>
        /* Reset and base */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 0; background: #f4f7fa;
            color: #333;
        }
        a {
            color: #0d6efd; text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        /* Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        aside.sidebar {
            background: #1e293b;
            color: #fff;
            width: 230px;
            padding: 20px;
            box-sizing: border-box;
        }
        aside.sidebar h3 {
            margin-top: 0;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 0.5rem;
        }
        aside.sidebar ul {
            list-style: none;
            padding: 0;
        }
        aside.sidebar ul li {
            margin-bottom: 12px;
        }
        aside.sidebar ul li a {
            color: #cbd5e1;
            display: block;
            padding: 8px 10px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        aside.sidebar ul li.active a,
        aside.sidebar ul li a:hover {
            background: #3b82f6;
            color: white;
        }

        main.content {
            flex: 1;
            padding: 30px 40px;
            background: white;
            box-sizing: border-box;
            overflow-y: auto;
        }
        main.content h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.8rem;
            color: #111827;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .alert.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .alert.success {
            background: #d1fae5;
            color: #047857;
            border: 1px solid #6ee7b7;
        }

        /* Cards */
        .dashboard-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        .card {
            background: #e0f2fe;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgb(14 165 233 / 0.3);
            flex: 1;
            min-width: 180px;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        .card:hover {
            background-color: #bae6fd;
        }
        .card h3 {
            margin: 0 0 12px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #0369a1;
        }
        .card p {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #0c4a6e;
        }

        /* Forms */
        section.create-user-form,
        section.assign-task-form,
        section.assign-staff-form,
        section.send-email-form {
            background: #f9fafb;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgb(0 0 0 / 0.1);
            max-width: 700px;
            margin-bottom: 30px;
        }
        section.create-user-form h3,
        section.assign-task-form h3,
        section.assign-staff-form h3,
        section.send-email-form h3 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #111827;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 180px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="datetime-local"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 1rem;
            color: #374151;
            transition: border-color 0.2s ease;
        }
        textarea {
            min-height: 120px;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="datetime-local"]:focus,
        select:focus,
        textarea:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.3);
        }
        button[type="submit"] {
            background-color: #2563eb;
            color: white;
            font-weight: 700;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 1.1rem;
        }
        button[type="submit"]:hover {
            background-color: #1e40af;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            color: #374151;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #e0f2fe;
            font-weight: 700;
            color: #0369a1;
        }
        tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #92400e;
        }
        .status-completed {
            background-color: #e0f2fe;
            color: #1e40af;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            font-weight: 600;
        }
        .btn-approve {
            background-color: #d1fae5;
            color: #065f46;
        }
        .btn-reject {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .btn-complete {
            background-color: #e0f2fe;
            color: #1e40af;
        }
        .btn-assign {
            background-color: #ede9fe;
            color: #5b21b6;
        }
        
        /* Small responsive tweaks */
        @media (max-width: 768px) {
            .dashboard-cards {
                flex-direction: column;
            }
            .form-row {
                flex-direction: column;
            }
            aside.sidebar {
                width: 100%;
                padding: 15px;
            }
            main.content {
                padding: 20px 15px;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar">
        <h3>Admin Dashboard</h3>
        <ul>
            <li class="active"><a href="dashboard.php">Home</a></li>
            <li><a href="../menu/menu.php">Menu Items</a></li>
            <li><a href="../menu/bookings.php">Bookings</a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="content">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <!-- Display Alerts -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="card">
                <h3>Total Staff</h3>
                <p><?php echo $staff_count; ?></p>
            </div>
        </div>

        <!-- Send Email Form -->
        <section class="send-email-form">
            <h3>Send Email</h3>
            <form method="POST" action="dashboard.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="recipient_type">Recipient Type *</label>
                        <select id="recipient_type" name="recipient_type" required onchange="updateRecipientOptions()">
                            <option value="" disabled selected>Select recipient type</option>
                            <option value="staff">Staff Member</option>
                            <option value="booking">Tourist (Booking)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="recipient_id">Recipient *</label>
                        <select id="recipient_id" name="recipient_id" required>
                            <option value="" disabled selected>Select recipient</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                </div>
                <button type="submit" name="send_email">Send Email</button>
            </form>
        </section>

        <!-- Create Staff User Form -->
        <section class="create-user-form">
            <h3>Create Staff Account</h3>
            <form method="POST" action="dashboard.php" autocomplete="off" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required minlength="3" maxlength="50" />
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required maxlength="100" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required minlength="8" />
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Staff Role *</label>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select role</option>
                            <?php foreach ($allowedStaffRoles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role); ?>"><?php echo ucfirst(htmlspecialchars($role)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_user">Create Staff</button>
            </form>
        </section>

        <!-- Assign Task to Staff Form -->
        <section class="assign-task-form">
            <h3>Assign Task to Staff</h3>
            <form method="POST" action="dashboard.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="staff_id">Staff Member *</label>
                        <select id="staff_id" name="staff_id" required>
                            <option value="" disabled selected>Select staff member</option>
                            <?php foreach ($staff_members as $staff): ?>
                                <option value="<?php echo htmlspecialchars($staff['id']); ?>">
                                    <?php echo htmlspecialchars($staff['username']); ?> (<?php echo ucfirst(htmlspecialchars($staff['role'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="datetime-local" id="due_date" name="due_date" required />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="task_description">Task Description *</label>
                        <input type="text" id="task_description" name="task_description" required />
                    </div>
                </div>
                <button type="submit" name="assign_task">Assign Task</button>
            </form>
        </section>

        <!-- Recent Tasks -->
        <section class="recent-tasks">
            <h3>Recent Tasks</h3>
            <?php if (!empty($recent_tasks)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Task Description</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['staff_name']); ?></td>
                            <td><?php echo htmlspecialchars($task['task_description']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($task['due_date']))); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($task['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($task['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent tasks found.</p>
            <?php endif; ?>
        </section>

        <!-- Recent Users -->
        <section class="recent-users">
            <h3>Recent Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Menu Items & Chef Status -->
        <section class="menu-status" style="margin-top:40px;">
            <h3>Menu Items and Chef Status</h3>
            <?php if (!empty($menu_items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Menu Item</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($item['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No menu items found.</p>
            <?php endif; ?>
        </section>

        <!-- Bookings Overview -->
        <section class="bookings-overview" style="margin-top:40px;">
            <h3>Recent Bookings</h3>
            <?php if (!empty($bookings)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer Name</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Staff Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['id']); ?></td>
                            <td><?php echo htmlspecialchars($booking['customer_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($booking['booking_date']))); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($booking['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($booking['assigned_staff_name'] ?? 'None'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($booking['role_assigned'] ?? 'N/A')); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="dashboard.php" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>" />
                                        <?php if ($booking['status'] == 'pending'): ?>
                                            <button type="submit" name="update_booking_status" value="approved" class="action-btn btn-approve">Approve</button>
                                            <button type="submit" name="update_booking_status" value="rejected" class="action-btn btn-reject">Reject</button>
                                        <?php elseif ($booking['status'] == 'approved'): ?>
                                            <button type="submit" name="update_booking_status" value="completed" class="action-btn btn-complete">Complete</button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <!-- Assign Staff Form -->
                                    <form method="POST" action="dashboard.php" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>" />
                                        <select name="staff_id" required style="width:120px; padding:6px; margin-right:5px;">
                                            <option value="" disabled selected>Assign Staff</option>
                                            <?php foreach ($staff_members as $staff): ?>
                                                <option value="<?php echo htmlspecialchars($staff['id']); ?>">
                                                    <?php echo htmlspecialchars($staff['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="role_assigned" required style="width:100px; padding:6px; margin-right:5px;">
                                            <option value="" disabled selected>Role</option>
                                            <?php foreach ($allowedStaffRoles as $role): ?>
                                                <option value="<?php echo htmlspecialchars($role); ?>"><?php echo ucfirst(htmlspecialchars($role)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_staff" class="action-btn btn-assign">Assign</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent bookings found.</p>
            <?php endif; ?>
        </section>

    </main>
</div>

<script>
// Function to update recipient options based on selected type
function updateRecipientOptions() {
    const recipientType = document.getElementById('recipient_type').value;
    const recipientSelect = document.getElementById('recipient_id');
    
    // Clear existing options
    recipientSelect.innerHTML = '<option value="" disabled selected>Select recipient</option>';
    
    if (recipientType === 'staff') {
        // Add staff members as options
        <?php foreach ($staff_members as $staff): ?>
            const option = document.createElement('option');
            option.value = '<?php echo $staff['id']; ?>';
            option.textContent = '<?php echo htmlspecialchars($staff['username'] . " (" . ucfirst($staff['role']) . ")"); ?>';
            recipientSelect.appendChild(option);
        <?php endforeach; ?>
    } else if (recipientType === 'booking') {
        // Add bookings as options (tourists)
        <?php foreach ($bookings as $booking): ?>
            const option = document.createElement('option');
            option.value = '<?php echo $booking['id']; ?>';
            option.textContent = '<?php echo htmlspecialchars($booking['customer_name'] ?? 'Unknown') . " (Booking #" . $booking['id'] . ")"; ?>';
            recipientSelect.appendChild(option);
        <?php endforeach; ?>
    }
}
</script>

</body>
</html>