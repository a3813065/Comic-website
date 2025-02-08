<?php
session_start(); // 開啟 session，用來追蹤登入狀態

// 檢查用戶是否已經登入，通過檢查 session 或 login_token cookie
$is_logged_in = isset($_SESSION['role']) || isset($_COOKIE['login_token']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時代變了</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="聊天.css">
    <link rel="stylesheet" href="1.css">
</head>
<body>
<script src="script.js"></script>
<button id="toggleNavbarBtn" class="toggle-btn" onclick="toggleNavbar()">隱藏/顯示</button>
<div class="navbar">
    <h1>網站</h1>
    <button id="toggleButton" class="toggle-btns" onclick="toggleTitle()">顯示標題</button>
    <ul class="night">
        <?php if ($is_logged_in): ?>
            <li><a href='/logout.php'>登出</a></li> <!-- 如果已登入，顯示登出 -->
        <?php else: ?>
            <li><a href='/login.php'>登入</a></li> <!-- 如果未登入，顯示登入 -->
        <?php endif; ?>
        <li><a href='/upload.php'>上傳</a></li>
    </ul>
</div>
<div class="content">
<?php
// 引入數據庫連接模塊
require_once 'db_connection.php';

// 引入通用函數模塊
require_once 'common_functions.php';

// 初始化數據庫連接
$mysqli = getDatabaseConnection();

echo '<form method="GET" action="">
        <input type="text" name="keyword" placeholder="搜尋漫畫標題" required>
        <button type="submit">搜尋</button>
      </form>';
echo "<p>贊助聯繫LINEID:gggfff1234，不提供退款</p>";
echo "<p>找工作  黑產業轉明產業 會各類技術.開發遊戲軟體.伺服器</p>";
echo "<p>常常會使用hack the box.leetcode.github學習新技術</p>";
// 獲取搜索關鍵字和頁碼參數
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$comics_per_page = 12; // 每頁顯示的漫畫數量

// 如果有關鍵字，執行漫畫搜索
if (!empty($keyword)) {
    // 引入搜索漫画模块
    require_once 'search_comics.php';
    require_once 'common_functions.php';

    $search_result = searchComics($mysqli, $keyword, $comics_per_page, $current_page);
    $total_comics = $search_result['total_comics'];
    $comics = $search_result['comics'];

    // 显示搜索结果
    if (!empty($comics)) {
        displayComics($comics, $mysqli);  // 使用传递 mysqli 连接
        displayPagination($total_comics, $comics_per_page, $current_page, $keyword);
    } else {
        echo "<p>没有找到符合条件的漫画。</p>";
    }
} else {
    // 没有关键字，显示全部漫画
    require_once 'search_comics.php';
    require_once 'common_functions.php';

    $all_comics_result = getAllComics($mysqli, $comics_per_page, $current_page);
    $total_comics = $all_comics_result['total_comics'];
    $comics = $all_comics_result['comics'];

    if (!empty($comics)) {
        displayComics($comics, $mysqli);
        displayPagination($total_comics, $comics_per_page, $current_page);
    } else {
        echo "<p>目前没有漫画可供查看。</p>";
    }
}
echo "<p>從這邊開始設計</p>";

echo '<div class="chatbox">
    <div class="chat-window" id="chatWindow">
        <!-- 聊天消息將顯示在這裡 -->
    </div>
    <form method="POST" action="" id="chatForm">
        <input type="text" name="message" id="message" placeholder="輸入訊息..." required>
        <button type="submit">發送</button>
    </form>
</div>';



$mysqli->close();
?>

<script src="聊天室.js"></script>

</div>


</body>
</html>
