<?php
require_once 'db_connection.php';

$mysqli = getDatabaseConnection();
$result = $mysqli->query("SELECT username, message, created_at FROM chat_messages ORDER BY created_at DESC LIMIT 50");
$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = "<p><strong>{$row['username']}</strong>: {$row['message']} <small>({$row['created_at']})</small></p>";
}
$result->free();
$mysqli->close();

echo implode('', array_reverse($messages)); // 按時間順序顯示訊息
?>
