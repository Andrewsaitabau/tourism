<?php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>M-Pesa Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #00c6ff, #0072ff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 1rem;
        }
        button {
            background: #0072ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #005dc1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Complete Payment</h2>
        <p>Enter your M-Pesa number to proceed with payment.</p>
        <form action="process_mpesa.php" method="POST">
            <input type="text" name="mpesa_number" placeholder="e.g. 0712345678" required pattern="07\d{8}">
            <button type="submit">Pay Now</button>
        </form>
    </div>
</body>
</html>
