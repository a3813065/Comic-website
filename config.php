<?php
$servername = "localhost";
$username = "YOURMYSQLACCOUNT";
$password = "YOURMYSQLPASSWORD";
$dbname = "YOURMYSQLdbname";

// 建立連線
$mysqli = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($mysqli->connect_error) {
    die("連線失敗: " . $mysqli->connect_error);
}
?>
