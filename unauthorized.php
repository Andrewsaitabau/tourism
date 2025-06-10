<?php
// unauthorized.php

// Optionally, you can start session here if you want to check or log user info
session_start();

// You might want to redirect if user is logged in but not authorized
// or just display the message.

// Simple message for unauthorized access

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Unauthorized Access</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8d7da;
            color: #721c24;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            text-align: center;
            max-width: 400px;
            background-color: #f5c6cb;
            border: 1px solid #f5c2c7;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(114, 28, 36, 0.3);
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        a.btn {
            background-color: #721c24;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        a.btn:hover {
            background-color: #501217;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Access Denied</h1>
    <p>You do not have permission to access this page.</p>
    <a href="/tourism/login.php" class="btn">Go to Login</a>
</div>
</body>
</html>
