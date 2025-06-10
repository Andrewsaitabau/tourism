<?php
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;

    public function __construct($index, $timestamp, $data, $previousHash) {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
    }

    public function calculateHash() {
        return hash('sha256', $this->index . $this->timestamp . json_encode($this->data) . $this->previousHash);
    }
}

// Load existing blockchain
$blockchainFile = 'blockchain.json';
$blockchain = [];

if (file_exists($blockchainFile)) {
    $blockchain = json_decode(file_get_contents($blockchainFile), true);
}

// Get last block index
$lastIndex = count($blockchain) - 1;
$lastBlock = $blockchain[$lastIndex] ?? null;
$lastHash = $lastBlock['hash'] ?? '0';
$lastBookingId = $lastBlock['data']['id'] ?? 0;

// Connect to tourism_system DB
$conn = new mysqli("localhost", "root", "", "tourism_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get new bookings after the last logged ID
$sql = "SELECT 
    b.id, 
    b.username, 
    b.service_id, 
    s.name AS service_name, 
    b.start_date, 
    b.end_date, 
    b.total_price 
FROM bookings b 
JOIN services s ON b.service_id = s.id 
WHERE b.id > $lastBookingId
ORDER BY b.id ASC";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $index = count($blockchain);
        $timestamp = date("Y-m-d H:i:s");
        $data = [
            'id' => $row['id'],
            'username' => $row['username'],
            'service_id' => $row['service_id'],
            'service_name' => $row['service_name'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'total_price' => $row['total_price']
        ];
        $block = new Block($index, $timestamp, $data, $lastHash);
        $blockchain[] = [
            'index' => $block->index,
            'timestamp' => $block->timestamp,
            'data' => $block->data,
            'previousHash' => $block->previousHash,
            'hash' => $block->hash
        ];
        $lastHash = $block->hash;
    }

    // Save updated blockchain
    file_put_contents($blockchainFile, json_encode($blockchain, JSON_PRETTY_PRINT));
    echo "Blockchain updated with new booking records.";
} else {
    echo "No new bookings to log.";
}

$conn->close();
?>
