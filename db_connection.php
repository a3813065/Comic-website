<?php
function getDatabaseConnection() {
    require 'config.php';
    if ($mysqli->connect_error) {
        die("連接失敗: " . $mysqli->connect_error);
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        die("錯誤: 無法設置 UTF-8 字符集：" . $mysqli->error);
    }

    return $mysqli;
}
?>
