<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blockchain Logger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        h2 {
            color: #333;
        }
        form {
            background: white;
            padding: 20px;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #2b7cff;
            color: white;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #1a5fd3;
        }
        .link {
            margin-top: 20px;
            display: block;
        }
    </style>
</head>
<body>

    <h2>Log an Action to the Blockchain</h2>

    <form method="POST" action="log_transaction.php">
        <input type="text" name="user" placeholder="Enter your username" required>
        <input type="text" name="action" placeholder="Describe the action (e.g., login, file upload)" required>
        <button type="submit">Log Action</button>
    </form>

    <a class="link" href="view_chain.php">📜 View Blockchain Ledger</a>

</body>
</html>
