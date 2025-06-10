<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

redirectIfNotLoggedIn();

$message = '';
$exchangeRate = 113; // USD to KES
$amountKES = 0;

try {
    // Get latest booking with service price for the logged-in user
    $stmt = $pdo->prepare("
        SELECT b.*, s.price 
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $lastBooking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastBooking) {
        if (!empty($lastBooking['start_date']) && !empty($lastBooking['end_date'])) {
            $startDate = new DateTime($lastBooking['start_date']);
            $endDate = new DateTime($lastBooking['end_date']);
            $interval = $startDate->diff($endDate);
            $days = max(1, $interval->days);
            $totalCostUSD = $lastBooking['price'] * $days;
            $amountKES = round($totalCostUSD * $exchangeRate);
            $message = "Total amount calculated for $days day(s).";
        } else {
            $message = "Booking dates are missing or empty.";
        }
    } else {
        $message = "No booking found to pay for. Please make a booking first.";
    }
} catch (PDOException $e) {
    $message = "Error fetching booking: " . $e->getMessage();
}

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $amount = $_POST['amount'];
    $phone = $_POST['phone'];
    // Format phone number for M-Pesa API (2547XXXXXXXX)
    $phone = '254' . ltrim($phone, '0');

    // TODO: Replace with your actual sandbox Consumer Key and Secret
    $consumer_key = 'GswwLfq13u0yTxbeWtl8hFl9CPWJ6leSnCazjMQ5CbfroHH8';
    $consumer_secret = 'mxSdtSKGnXC7G31nbfZ8FCuWg58dqOu6of2xWjZcG4HIATifT1sfy2L14UJrGUYG';

    // Step 1: Get access token
    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    $credentials = base64_encode("$consumer_key:$consumer_secret");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic $credentials"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response_data = json_decode($response, true);
    $access_token = $response_data['access_token'] ?? null;

    if (!$access_token) {
        // Show detailed debug info and stop execution
        $message = "Failed to get access token. HTTP Status: $http_code. Response: $response";
    } else {
        // Step 2: Prepare STK Push request
        $business_short_code = '174379'; // Sandbox shortcode
        $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
        $timestamp = date('YmdHis');
        $password = base64_encode($business_short_code . $passkey . $timestamp);

        $stk_data = [
            'BusinessShortCode' => $business_short_code,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $business_short_code,
            'PhoneNumber' => $phone,
            // Change callback URL to your actual URL that handles the payment response
            'CallBackURL' => 'https://yourdomain.com/mpesa_callback.php',
            'AccountReference' => 'Kilimani Cooperative Society',
            'TransactionDesc' => 'Payment for booking'
        ];

        $stk_ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
        curl_setopt($stk_ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $access_token"
        ]);
        curl_setopt($stk_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($stk_ch, CURLOPT_POST, true);
        curl_setopt($stk_ch, CURLOPT_POSTFIELDS, json_encode($stk_data));
        curl_setopt($stk_ch, CURLOPT_SSL_VERIFYPEER, false);

        $stk_response = curl_exec($stk_ch);
        curl_close($stk_ch);

        $stk_result = json_decode($stk_response);

        if (isset($stk_result->ResponseCode) && $stk_result->ResponseCode === '0') {
            try {
                $stmt = $pdo->prepare("INSERT INTO payments (booking_id, phone_number, amount, status) VALUES (?, ?, ?, 'Pending')");
                $stmt->execute([$lastBooking['id'], $phone, $amount]);
                $message = "STK Push sent successfully! Check your phone for the prompt.";
            } catch (PDOException $e) {
                $message = "STK Push sent, but failed to record payment: " . $e->getMessage();
            }
        } else {
            $errorMessage = $stk_result->errorMessage ?? 'Unknown error during STK Push.';
            $message = "STK Push failed: " . $errorMessage;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>M-Pesa Payment | Tourism System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f4f8;
            padding: 2rem;
        }
        .container {
            max-width: 450px;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin: auto;
        }
        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 5px;
            font-weight: 600;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center mb-4">M-Pesa Payment</h2>

    <?php if ($message): ?>
        <div class="message <?= (stripos($message, 'failed') === false && stripos($message, 'error') === false) ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($amountKES > 0): ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="phone" class="form-label">Phone Number (07XXXXXXXX)</label>
                <input type="text" name="phone" id="phone" class="form-control" pattern="\d{10}" placeholder="07XXXXXXXX" required>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Amount (KES)</label>
                <input type="number" name="amount" id="amount" class="form-control" value="<?= htmlspecialchars($amountKES) ?>" readonly required>
            </div>
            <button type="submit" name="submit" class="btn btn-success w-100">Pay Now</button>
        </form>
    <?php else: ?>
        <p class="text-danger">No payable booking found. Please make a booking first.</p>
    <?php endif; ?>

    <a href="/tourism/tourist/dashboard.php" class="btn btn-primary w-100 mt-3">Back to Dashboard</a>
</div>
</body>
</html>
