<?php
require_once 'blockchain.php';

$blockchainFile = 'blockchain.json';
$blockchain = new Blockchain();

if (file_exists($blockchainFile)) {
    $savedChain = json_decode(file_get_contents($blockchainFile), true);
    $blockchain->chain = [];
    foreach ($savedChain as $savedBlock) {
        $blockchain->chain[] = new Block(
            $savedBlock['index'],
            $savedBlock['timestamp'],
            $savedBlock['data'],
            $savedBlock['previousHash']
        );
    }

    echo $blockchain->isValid() ? "✅ Blockchain is valid." : "❌ Blockchain has been tampered!";
} else {
    echo "⚠️ No blockchain data found.";
}
?>