<?php
require 'config.php'; // 資料庫設定檔
session_start();

// 確保用戶已經登入並且是管理員
if (!isset($_COOKIE['login_token'])) {
    echo json_encode(["success" => false, "message" => "未登入"]);
    exit();
}

$token = $_COOKIE['login_token'];

// 檢查用戶的登入憑證
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE login_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "無效的登入憑證"]);
    exit();
}

$user = $result->fetch_assoc();

// 確保用戶是管理員
if ($user['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "您無權進行此操作"]);
    exit();
}

if (isset($_GET['comic_id']) && isset($_GET['action'])) {
    $comic_id = (int)$_GET['comic_id'];
    $action = $_GET['action'];

    // 根據 action 設置新狀態
    $new_status = ($action == 'publish') ? 1 : 0;

    // 更新漫畫狀態
    $stmt = $conn->prepare("UPDATE comics SET static = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $comic_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }

    $stmt->close();
}

$conn->close();
?>
