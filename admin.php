<?php 
session_start();
require 'config.php'; // 資料庫設定檔
// 驗證是否登入
if (!isset($_COOKIE['login_token'])) {
    header("Location: login.php");
    exit();
}

// 驗證登入憑證
$token = $_COOKIE['login_token'];
$stmt = $mysqli->prepare("SELECT id, username, role FROM users WHERE login_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // 檢查是否是管理員
    if ($user['role'] !== 'admin') {
        echo "您無權訪問此頁面。";
        exit();
    }
    echo "歡迎, " . htmlspecialchars($user['username']) . "！";
} else {
    // 登入無效，重定向到登入頁
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理介面</title>
    
    <!-- 引用CSS檔案 -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="1.css">
</head>
<body>

<h2>管理介面</h2>
<p>這是管理員專用頁面。</p>

<div class="content">
    <?php
    mb_internal_encoding("UTF-8");

    $mysqli = new mysqli("localhost", "hacker", "Fishing90018%", "abc", 1992);
    if ($mysqli->connect_error) {
        die("連接失敗: " . $mysqli->connect_error);
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        die("錯誤: 無法設置 UTF-8 字符集：" . $mysqli->error);
    }

    // 设置每页显示的漫画数量
    $comics_per_page = 12;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $comics_per_page;

    // 获取漫画的总数
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM comics");
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_comics = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    // 获取当前页的漫画
    $stmt = $mysqli->prepare("SELECT id, title, cover_image, static FROM comics LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $comics_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<div class='comic-list'>";
        while ($comic = $result->fetch_assoc()) {
            $comic_id = $comic['id'];
            $cover_image_path = htmlspecialchars($comic['cover_image']);
            $static = $comic['static'];  // 上架狀態

            $image_sql = "SELECT image_url FROM chapter_images WHERE comic_id = ? LIMIT 1";
            $image_stmt = $mysqli->prepare($image_sql);
            $image_stmt->bind_param("i", $comic_id);
            $image_stmt->execute();
            $image_result = $image_stmt->get_result();

            if ($image_result->num_rows > 0) {
                $image_data = $image_result->fetch_assoc();
                $image_url = htmlspecialchars($image_data['image_url']);

                $image_folder = $_SERVER['DOCUMENT_ROOT'] . '/' . $cover_image_path . '/' . $image_url;
                $image_formats = ['webp', 'jpg', 'jpeg', 'png', 'gif'];
                $video_formats = ['mp4'];
                $image_file = null;
                $video_file = null;

                if (is_dir($image_folder)) {
                    $files = scandir($image_folder);
                    foreach ($files as $file) {
                        $file_info = pathinfo($file);
                        // 如果是圖片格式
                        if (in_array(strtolower($file_info['extension']), $image_formats)) {
                            $image_file = $image_folder . '/' . $file;
                            break;
                        }
                        // 如果是影片格式
                        if (in_array(strtolower($file_info['extension']), $video_formats)) {
                            $video_file = $image_folder . '/' . $file;
                            break;
                        }
                    }
                }

                if (!$image_file && !$video_file) {
                    $file_info = "找不到圖片或影片，查找路徑: " . $image_folder;
                } else {
                    if ($video_file) {
                        $file_info = $video_file;
                    } else {
                        $file_info = $image_file;
                    }
                }
            } else {
                $file_info = "找不到圖片或影片，查找路徑: " . $image_folder;
            }

            // 顯示漫畫封面或影片
            echo "<div class='comic-item' id='comic-$comic_id'>";
            echo "<div class='comic-title' style='display: none;'><h2>" . htmlspecialchars($comic['title']) . "</h2></div>";

            if (isset($video_file)) {
                // 顯示影片
                echo "<video width='320' height='240' controls>
                        <source src='" . str_replace($_SERVER['DOCUMENT_ROOT'], '', $video_file) . "' type='video/mp4'>
                        您的瀏覽器不支持 HTML5 視頻。
                      </video>";
            } else {
                // 顯示圖片
                echo "<img src='" . str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_info) . "' alt='" . htmlspecialchars($comic['title']) . "'>";
            }

            echo "<a href='/comic/" . $comic['id'] . "' class='view-button'>觀看漫畫</a>";

            // 上架/下架按鈕
            $action = $static == 1 ? 'unpublish' : 'publish';
            echo "<button class='toggle-button' onclick='toggleComicStatus($comic_id, \"$action\")'>";
            echo $static == 1 ? '下架' : '上架'; 
            echo "</button>";

            echo "</div>";
        }
        echo "</div>";

        $total_pages = ceil($total_comics / $comics_per_page);
        if ($total_pages > 1) {
            echo "<div class='pagination'>";
            if ($current_page > 1) {
                echo "<a href='?page=" . ($current_page - 1) . "'>&laquo; 上一頁</a>";
            }
            if ($current_page < $total_pages) {
                echo "<a href='?page=" . ($current_page + 1) . "'>下一頁 &raquo;</a>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>目前沒有漫畫可供查看。</p>";
    }

    $stmt->close();
    $mysqli->close();
    ?>
</div>

<!-- 引用JS檔案 -->
<script src="script.js"></script>

<script>
// 使用 AJAX 更新漫畫狀態
function toggleComicStatus(comic_id, action) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "update_comic_status.php?comic_id=" + comic_id + "&action=" + action, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // 更新顯示狀態
                const comicItem = document.getElementById('comic-' + comic_id);
                const button = comicItem.querySelector('.toggle-button');
                if (action === 'publish') {
                    button.textContent = '下架';
                    button.setAttribute('onclick', 'toggleComicStatus(' + comic_id + ', "unpublish")');
                } else {
                    button.textContent = '上架';
                    button.setAttribute('onclick', 'toggleComicStatus(' + comic_id + ', "publish")');
                }
            } else {
                alert("更新失敗，請重試！");
            }
        }
    };
    xhr.send();
}
</script>

</body>
</html>
