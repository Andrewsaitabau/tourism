<?php
$blockchainFile = 'blockchain.json';

echo "<h2>Blockchain Ledger</h2>";

if (file_exists($blockchainFile)) {
    $blocks = json_decode(file_get_contents($blockchainFile), true);

    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background-color:#f2f2f2;'>
            <th>Index</th>
            <th>Timestamp</th>
            <th>User</th>
            <th>Action</th>
            <th>IP</th>
            <th>Previous Hash</th>
            <th>Hash</th>
          </tr>";

    foreach ($blocks as $block) {
        $user = $action = $ip = '-';
        if (is_array($block['data'])) {
            $user = htmlspecialchars($block['data']['user'] ?? '-');
            $action = htmlspecialchars($block['data']['action'] ?? '-');
            $ip = htmlspecialchars($block['data']['ip'] ?? '-');
        } else {
            $action = htmlspecialchars($block['data']);
        }

        echo "<tr>
                <td>{$block['index']}</td>
                <td>{$block['timestamp']}</td>
                <td>{$user}</td>
                <td>{$action}</td>
                <td>{$ip}</td>
                <td style='word-break:break-all;'>{$block['previousHash']}</td>
                <td style='word-break:break-all;'>{$block['hash']}</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "⚠️ No blockchain data found.";
}
?>
