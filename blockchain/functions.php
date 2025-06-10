<?php
function log_notification($message) {
    $conn = new mysqli("localhost", "root", "", "your_database_name");
    $stmt = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
    $stmt->bind_param("s", $message);
    $stmt->execute();
    $stmt->close();
}
