<?php
session_start();

// 銷毀 session
session_unset();
session_destroy();

// 清除登入 token 的 cookie
setcookie("login_token", "", time() - 3600, "/");

// 重定向到首頁
header("Location: index.php");
exit();
?>
