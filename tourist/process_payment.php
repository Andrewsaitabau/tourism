<?php
$message = '';
if (isset($_POST['submit'])) {
    $amount = $_POST['amount']; // Amount to transact
    $phone = $_POST['phone'];   // Phone number paying
    $Account_no = 'COMRADE MARKET'; // Optional account number

    // Format phone number: remove leading '0' and add '254'
    $phone = '254' . ltrim($phone, '0');

    // Requesting Access Token
    $ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    $consumer_key = 'GswwLfq13u0yTxbeWtl8hFl9CPWJ6leSnCazjMQ5CbfroHH8'; // Sandbox credentials
    $consumer_secret = 'mxSdtSKGnXC7G31nbfZ8FCuWg58dqOu6of2xWjZcG4HIATifT1sfy2L14UJrGUYG';
    $credentials = base64_encode($consumer_key . ':' . $consumer_secret);
    $headers = [
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    // Get access token
    $response_data = json_decode($response, true);
    $access_token = $response_data['access_token'];

    // Generate password for STK Push
    $business_short_code = '174379';
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Sandbox passkey
    $timestamp = date('YmdHis');
    $password = base64_encode($business_short_code . $passkey . $timestamp);

    // Prepare STK push request
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $data = [
        'BusinessShortCode' => $business_short_code,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $business_short_code,
        'PhoneNumber' => $phone,
        'CallBackURL' => 'https://yourdomain.com/callback.php', // ✅ REPLACE with your actual domain
        'AccountReference' => 'Kilimani Cooperative Society',
        'TransactionDesc' => 'Payment of bill'
    ];
    $payload = json_encode($data);

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($curl);
    curl_close($curl);

    $msg_resp = json_decode($resp);

    if (isset($msg_resp->ResponseCode) && $msg_resp->ResponseCode == "0") {
        $message = "WAIT FOR STK POP UP🔥";
    } else {
        if (isset($msg_resp->errorMessage)) {
            $message = "Transaction Failed: " . $msg_resp->errorMessage;
        } else {
            $message = "Transaction Failed";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lipa na Mpesa</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .message {
            text-align: center;
            padding: 15px;
            background-color: lightblue;
            font-size: 16px;
            width: 60%;
            margin: 20px auto;
            border-radius: 5px;
            color: black;
        }
    </style>
</head>
<body>
    <div class="navbar bg-light p-3 mb-3">
        <div class="container">
            <span class="navbar-brand">MAASAI TOURISM</span>
        </div>
    </div>

    <div class="text-center mb-3">
        <a href="http://127.0.0.1/kibet/my-loans" class="btn btn-secondary">Back to Site</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-6">
                <div class="card px-4 py-4">
                    <h4 class="text-center mb-4">Make Payment for Your Loan</h4>
                    <form method="POST" action="">
                        <div class="form-group mb-3">
                            <label>Amount</label>
                            <input type="text" class="form-control" name="amount" placeholder="Enter Amount" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Phone Number</label>
                            <input type="text" class="form-control" name="phone" placeholder="07XXXXXXXX" required>
                        </div>
                        <button type="submit" name="submit" value="submit" class="btn btn-success btn-block">PAY</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
</body>
</html>
