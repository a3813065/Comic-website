<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $mysqli = getDatabaseConnection();
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '匿名';

    $stmt = $mysqli->prepare("INSERT INTO chat_messages (username, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ss', $username, $message);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    echo "訊息已送出！";
    exit;
}
?>
