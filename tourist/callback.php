<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Set content type to JSON
header("Content-Type: application/json");

// Get raw POST data from Safaricom
$callbackJSONData = file_get_contents('php://input');

// Decode it to an associative array
$callbackData = json_decode($callbackJSONData, true);

// Log callback data for debugging
file_put_contents(__DIR__ . '/stk_callback_log.txt', date("Y-m-d H:i:s") . " - " . print_r($callbackData, true) . "\n", FILE_APPEND);

if (
    isset($callbackData['Body']['stkCallback']['ResultCode']) &&
    $callbackData['Body']['stkCallback']['ResultCode'] == 0
) {
    $mpesa_data = $callbackData['Body']['stkCallback'];

    $amount = null;
    $mpesa_receipt_number = null;
    $phone_number = null;
    $transaction_date = null;

    // Extract transaction details from CallbackMetadata
    if (isset($mpesa_data['CallbackMetadata']['Item']) && is_array($mpesa_data['CallbackMetadata']['Item'])) {
        foreach ($mpesa_data['CallbackMetadata']['Item'] as $item) {
            switch ($item['Name']) {
                case "Amount":
                    $amount = $item['Value'];
                    break;
                case "MpesaReceiptNumber":
                    $mpesa_receipt_number = $item['Value'];
                    break;
                case "PhoneNumber":
                    $phone_number = $item['Value'];
                    break;
                case "TransactionDate":
                    // Safaricom transaction date format: YYYYMMDDHHMMSS
                    $transaction_date = DateTime::createFromFormat('YmdHis', $item['Value']);
                    if ($transaction_date) {
                        $transaction_date = $transaction_date->format('Y-m-d H:i:s');
                    } else {
                        $transaction_date = null;
                    }
                    break;
            }
        }
    }

    if ($amount && $mpesa_receipt_number && $phone_number) {
        try {
            // Normalize phone number to match stored format (2547xxxxxxxx)
            $normalized_phone = preg_replace('/^0/', '254', $phone_number);

            // Update the payments table record with the mpesa receipt and status 'Completed'
            $stmt = $pdo->prepare("UPDATE payments SET mpesa_receipt_number = ?, status = 'Completed', transaction_date = ? WHERE phone_number = ? AND amount = ? AND status = 'Pending' ORDER BY id DESC LIMIT 1");
            $stmt->execute([
                $mpesa_receipt_number,
                $transaction_date,
                $normalized_phone,
                $amount
            ]);

            // Respond to Safaricom with success
            echo json_encode([
                "ResultCode" => 0,
                "ResultDesc" => "Confirmation received successfully"
            ]);
            exit;
        } catch (PDOException $e) {
            // Log error but still respond success to Safaricom to avoid repeated callbacks
            file_put_contents(__DIR__ . '/stk_callback_error_log.txt', date("Y-m-d H:i:s") . " - DB Update Error: " . $e->getMessage() . "\n", FILE_APPEND);

            echo json_encode([
                "ResultCode" => 0,
                "ResultDesc" => "Confirmation received successfully"
            ]);
            exit;
        }
    } else {
        // Missing required data
        echo json_encode([
            "ResultCode" => 1,
            "ResultDesc" => "Incomplete transaction data"
        ]);
        exit;
    }

} else {
    // Handle failed transactions or invalid data
    echo json_encode([
        "ResultCode" => 1,
        "ResultDesc" => "Transaction failed or invalid data"
    ]);
    exit;
}
