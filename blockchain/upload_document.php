<?php
require_once 'blockchain.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $target = "uploads/" . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $target);

    $fileHash = hash_file('sha256', $target);
    $username = $_SESSION['user'] ?? 'unknown';

    // Insert into DB
    $conn = new mysqli("localhost", "root", "", "your_database_name");
    $stmt = $conn->prepare("INSERT INTO documents (filename, hash, uploaded_by) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $file['name'], $fileHash, $username);
    $stmt->execute();
    $stmt->close();

    // Log to blockchain
    $blockchainFile = 'blockchain.json';
    $blockchain = new Blockchain();
    if (file_exists($blockchainFile)) {
        $savedChain = json_decode(file_get_contents($blockchainFile), true);
        $blockchain->chain = [];
        foreach ($savedChain as $block) {
            $blockchain->chain[] = new Block($block['index'], $block['timestamp'], $block['data'], $block['previousHash']);
        }
    }

    $data = [
        "user" => $username,
        "action" => "uploaded document: " . $file['name'],
        "file_hash" => $fileHash,
        "ip" => $_SERVER['REMOTE_ADDR']
    ];
    $blockchain->addBlock($data);
    file_put_contents($blockchainFile, json_encode($blockchain->getChain(), JSON_PRETTY_PRINT));

    echo "✅ File uploaded and logged in blockchain.";
}
?>

<form method="POST" enctype="multipart/form-data">
    Upload Document: <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>
