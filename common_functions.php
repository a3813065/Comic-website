<?php
function getCoverImagePath($mysqli, $comic_id, $cover_image_path) {
    // 获取漫画章节图片
    $image_sql = "SELECT image_url FROM chapter_images WHERE comic_id = ? LIMIT 1";
    $image_stmt = $mysqli->prepare($image_sql);
    $image_stmt->bind_param("i", $comic_id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();

    if ($image_result->num_rows > 0) {
        $image_data = $image_result->fetch_assoc();
        $image_url = htmlspecialchars($image_data['image_url']);
        $image_folder = $_SERVER['DOCUMENT_ROOT'] . '/' . $cover_image_path . '/' . $image_url;
        $file_formats = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'mp4'];
        $media_file = null;

        if (is_dir($image_folder)) {
            $files = scandir($image_folder);
            foreach ($files as $file) {
                $file_info = pathinfo($file);
                if (in_array(strtolower($file_info['extension']), $file_formats)) {
                    $media_file = $image_folder . '/' . $file;
                    break;
                }
            }
        }

        if (!$media_file) {
            $media_file = "找不到文件，查找路徑: " . $image_folder;
        } else {
            $media_file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $media_file);
        }
    } else {
        $media_file = "找不到文件，查找路徑: " . $image_folder;
    }

    return $media_file;
}

function displayComics($comics, $mysqli) {
    echo "<div class='comic-list'>";
    foreach ($comics as $comic) {
        $comic_id = htmlspecialchars($comic['id']);
        $title = htmlspecialchars($comic['title']);
        $cover_image_path = htmlspecialchars($comic['cover_image']);

        // 调用获取封面图像路径的函数
        $media_file = getCoverImagePath($mysqli, $comic_id, $cover_image_path);


        echo "<div class='comic-item'>";
        echo "<div class='comic-title' style='display: none;'><h2>" . htmlspecialchars($comic['title']) . "</h2></div>";
        // 检查是否是图片或视频
        $file_ext = pathinfo($media_file, PATHINFO_EXTENSION);
        if ($file_ext == 'mp4') {
            echo "<div class='video-container'><video controls><source src='" . $media_file . "' type='video/mp4'>您的瀏覽器不支援影片播放。</video></div>";
        } else {
            echo "<img src='" . $media_file . "' alt='" . $title . "'>";
        }
        
        echo "<a href='/comic/$comic_id' class='view-button'>觀看漫畫</a>";
        echo "</div>";
    }
    echo "</div>";
}

function displayPagination($total_comics, $comics_per_page, $current_page, $keyword = '') {
    $total_pages = ceil($total_comics / $comics_per_page);

    if ($total_pages > 1) {
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            echo "<a href='?keyword=" . urlencode($keyword) . "&page=" . ($current_page - 1) . "'>&laquo; 上一頁</a>";
        }
        if ($current_page < $total_pages) {
            echo "<a href='?keyword=" . urlencode($keyword) . "&page=" . ($current_page + 1) . "'>下一頁 &raquo;</a>";
        }
        echo "</div>";
    }
}
?>
