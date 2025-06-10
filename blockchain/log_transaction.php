<?php
require_once 'blockchain.php';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "tourism_system");
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Get form inputs safely
$user = $_POST['user'] ?? 'unknown';
$action = $_POST['action'] ?? 'no action';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Create the data to add as a new block
$data = [
    'user' => $user,
    'action' => $action,
    'ip' => $ip
];

// Load existing blockchain or initialize a new one
$blockchainFile = 'blockchain.json';
$blockchain = new Blockchain();

if (file_exists($blockchainFile)) {
    $savedChain = json_decode(file_get_contents($blockchainFile));
    $blockchain->chain = [];
    foreach ($savedChain as $block) {
        $blockchain->chain[] = new Block(
            $block->index,
            $block->timestamp,
            $block->data,
            $block->previousHash
        );
    }
}

// Add the new block with the action data
$blockchain->addBlock($data);

// Save updated blockchain to file
file_put_contents($blockchainFile, json_encode($blockchain->getChain(), JSON_PRETTY_PRINT));

// Get the latest block
$chain = $blockchain->getChain();
$latestBlock = end($chain);

$blockIndex = $latestBlock->index;
$blockTimestamp = $latestBlock->timestamp;
$blockDataJson = json_encode($latestBlock->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$blockPreviousHash = $latestBlock->previousHash;
$blockHash = $latestBlock->hash;

// Insert into database
$stmt = $mysqli->prepare("INSERT INTO blockchain_ledger 
    (block_index, timestamp, data, previous_hash, hash) 
    VALUES (?, ?, ?, ?, ?)");

$stmt->bind_param("issss",
    $blockIndex,
    $blockTimestamp,
    $blockDataJson,
    $blockPreviousHash,
    $blockHash
);

if ($stmt->execute()) {
    echo "<h3>✅ Action logged successfully to blockchain and database.</h3>";
} else {
    echo "<h3>❌ Error saving to database: " . $stmt->error . "</h3>";
}

$stmt->close();
$mysqli->close();

echo "<a href='index.php'>← Back</a> | <a href='view_chain.php'>📜 View Blockchain</a>";
?>

