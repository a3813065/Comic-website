<?php
error_reporting(E_ALL); // 開啟錯誤報告
ini_set('display_errors', 1); // 顯示錯誤

if (!isset($_SERVER['REQUEST_URI']) || preg_match('/^\/comic\/\d+$/', $_SERVER['REQUEST_URI']) === 0) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// 連接資料庫
require 'config.php'; // 資料庫設定檔

// 獲取漫畫ID
$comic_id = intval(basename($_SERVER['REQUEST_URI'])); // 直接從 URL 中獲取漫畫 ID

// 查詢漫畫詳細資料
$sql = "SELECT id, title, cover_image, content FROM comics WHERE id = ?";
$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    die("查詢錯誤: " . $mysqli->error);
}

$stmt->bind_param("i", $comic_id);
$stmt->execute();
$result = $stmt->get_result();

// 開始生成 HTML 結構
$title = "漫畫詳情"; // 預設標題

// 顯示漫畫詳細資料
if ($result->num_rows > 0) {
    $comic = $result->fetch_assoc();
    $title = htmlspecialchars($comic['title']); // 更新標題
    echo "<div class='comic'>";
    echo "<div class='comic-item'>";
    echo "<a>" . htmlspecialchars($comic['title']) . "</a>";

    // 查詢 chapter_images 表以獲取圖片路徑
    $image_sql = "SELECT cover_image FROM comics WHERE id = ? LIMIT 1"; // 獲取一張圖片
    $image_stmt = $mysqli->prepare($image_sql);
    $image_stmt->bind_param("i", $comic_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();

    // 檢查是否找到圖片或影片
    if ($image_result->num_rows > 0) {
        $image_data = $image_result->fetch_assoc();
        $image_url = htmlspecialchars($image_data['cover_image']); // 獲取資料夾路徑
        
        // 查找圖片或影片的邏輯
        $media_formats = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'mp4'];
        $media_file = null;

        // 檢查原始目錄
        $media_folder = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_url;
        if (is_dir($media_folder)) {
            $files = scandir($media_folder); // 列出目錄中的所有文件
            foreach ($files as $file) {
                $file_info = pathinfo($file);
                
                // 檢查文件擴展名是否為有效的格式（圖片或影片）
                if (isset($file_info['extension']) && in_array(strtolower($file_info['extension']), $media_formats)) {
                    $media_file = $media_folder . '/' . $file;
                    break; // 找到第一個符合條件的文件後退出
                }
            }
        }

        // 如果在原始目錄中找不到，檢查子目錄 1/
        if (!$media_file) {
            $media_folder_sub = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_url . '1'; // 子目錄 1
            if (is_dir($media_folder_sub)) {
                $files = scandir($media_folder_sub); // 列出目錄中的所有文件
                foreach ($files as $file) {
                    $file_info = pathinfo($file);
                    
                    // 檢查文件擴展名是否為有效的格式（圖片或影片）
                    if (isset($file_info['extension']) && in_array(strtolower($file_info['extension']), $media_formats)) {
                        $media_file = $media_folder_sub . '/' . $file;
                        break; // 找到第一個符合條件的文件後退出
                    }
                }
            }
        }

        if ($media_file) {
            $file_ext = pathinfo($media_file, PATHINFO_EXTENSION);
            // 如果是影片，顯示 video 元素
            if ($file_ext == 'mp4') {
                echo "<div class='video-container'><video controls><source src='" . htmlspecialchars(str_replace($_SERVER['DOCUMENT_ROOT'], '', $media_file)) . "' type='video/mp4'>您的瀏覽器不支援影片播放。</video></div>";
            } else {
                // 否則顯示圖片
                echo "<img src='" . htmlspecialchars(str_replace($_SERVER['DOCUMENT_ROOT'], '', $media_file)) . "' alt='" . htmlspecialchars($comic['title']) . "'>";
            }
        } else {
            echo "<p>找不到封面圖片或影片。</p>";
        }
    } else {
        echo "<p>找不到封面圖片或影片。</p>";
    }
    
    echo "<div class='comic2'>";
    echo "<a href='/comic/" . $comic['id'] . "' class='view-button'>觀看漫畫</a>";
    echo "<a href='/' class='back'>返回首頁</a>";
    echo "</div>";

    // 查詢所有章節
    $chapter_sql = "SELECT DISTINCT chapter_id FROM chapter_images WHERE comic_id = ? ORDER BY chapter_id ASC";
    $chapter_stmt = $mysqli->prepare($chapter_sql);
    $chapter_stmt->bind_param("i", $comic_id);
    $chapter_stmt->execute();
    $chapter_result = $chapter_stmt->get_result();

    if ($chapter_result->num_rows > 0) {
        echo "<div class='chapter-list'>";

        $chapter_number = 1; // 用於顯示章節順序的變量
        while ($chapter = $chapter_result->fetch_assoc()) {
            echo "<li><a href='/chapter.php?comic_id=" . $comic_id . "&chapter_id=" . urlencode($chapter['chapter_id']) . "' class='chapter-link'>" . 
                 "第 " . $chapter_number . " 章</a></li>";
            $chapter_number++;
        }

        echo "</div>";
    } else {
        echo "<p>此漫畫沒有章節。</p>";
    }

} else {
    echo "<p>找不到該漫畫。</p>";
}

// 關閉資料庫連接
$stmt->close();
$image_stmt->close();

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="comic.css"> <!-- 確保路徑正確 -->
</head>
<body>
    <div class="navi">
        <!-- 可放置導航按鈕等內容 -->
    </div>
    <div class="comic-detail">
        <!-- 這裡放漫畫顯示代碼 -->
    </div>
</body>
</html>
