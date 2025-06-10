<?php
session_start();

// If user is not registered/logged in
if (!isset($_SESSION['user_id'])) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Error - Please Register</title>
        <style>
            .error-box {
                padding: 20px;
                margin: 100px auto;
                max-width: 400px;
                background-color: #fee2e2;
                color: #991b1b;
                border: 1px solid #fca5a5;
                border-radius: 8px;
                font-family: Arial, sans-serif;
                text-align: center;
                font-weight: bold;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        </style>
        <meta http-equiv="refresh" content="3;URL=http://localhost/tourism/register.php">
    </head>
    <body>
        <div class="error-box">
            ❌ Error! Please register first.<br><br>
            Redirecting to registration page...
        </div>
    </body>
    </html>
    ';
    exit;
}
?>
