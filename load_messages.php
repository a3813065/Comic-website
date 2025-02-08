<?php
require_once 'db_connection.php';

// 每頁顯示的訊息數量
$messages_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;  // 默認顯示第1頁

// 計算訊息的起始位置
$start_from = ($page - 1) * $messages_per_page;

$mysqli = getDatabaseConnection();

// 獲取總訊息數量
$query_count = "SELECT COUNT(*) AS total_messages FROM messages";
$result_count = $mysqli->query($query_count);
$total_messages = $result_count->fetch_assoc()['total_messages'];

// 計算總頁數
$total_pages = ceil($total_messages / $messages_per_page);

// 查詢當前頁的訊息
$query = "SELECT m.message, m.timestamp, u.username 
          FROM messages m 
          JOIN users u ON m.user_id = u.id 
          ORDER BY m.timestamp DESC 
          LIMIT $start_from, $messages_per_page";
$result = $mysqli->query($query);

$messages = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $messages[] = array(
            'username' => htmlspecialchars($row['username']),
            'message' => htmlspecialchars($row['message']),
            'timestamp' => $row['timestamp']
        );
    }
}

$mysqli->close();

// 返回 JSON 格式的訊息和總頁數
echo json_encode(array('messages' => $messages, 'total_pages' => $total_pages), JSON_UNESCAPED_UNICODE);
?>
