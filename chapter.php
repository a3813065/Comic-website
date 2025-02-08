<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['comic_id']) || !is_numeric($_GET['comic_id']) || !isset($_GET['chapter_id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

require 'config.php'; // 資料庫設定檔


$comic_id = intval($_GET['comic_id']);
$chapter_id = $mysqli->real_escape_string($_GET['chapter_id']);

$comic_sql = "SELECT cover_image FROM comics WHERE id = ?";
$comic_stmt = $mysqli->prepare($comic_sql);
if ($comic_stmt === false) {
    die("查詢錯誤: " . $mysqli->error);
}
$comic_stmt->bind_param("i", $comic_id);
$comic_stmt->execute();
$comic_result = $comic_stmt->get_result();

$cover_image = '';
if ($comic_result->num_rows > 0) {
    $comic_data = $comic_result->fetch_assoc();
    $cover_image = $comic_data['cover_image'];
} else {
    echo "<p>找不到該漫畫的資料。</p>";
    exit;
}

$images_sql = "SELECT image_url FROM chapter_images WHERE comic_id = ? AND chapter_id = ?";
$images_stmt = $mysqli->prepare($images_sql);
if ($images_stmt === false) {
    die("查詢錯誤: " . $mysqli->error);
}
$images_stmt->bind_param("is", $comic_id, $chapter_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$folder_path = '';
if ($images_result->num_rows > 0) {
    $image_data = $images_result->fetch_assoc();
    $folder_path = $image_data['image_url'];
} else {
    echo "<p>找不到該章節的圖片資料夾。</p>";
    exit;
}

$chapter_sql = "SELECT DISTINCT chapter_id FROM chapter_images WHERE comic_id = ? ORDER BY chapter_id ASC";
$chapter_stmt = $mysqli->prepare($chapter_sql);
$chapter_stmt->bind_param("i", $comic_id);
$chapter_stmt->execute();
$chapter_result = $chapter_stmt->get_result();

$all_chapters = [];
while ($chapter = $chapter_result->fetch_assoc()) {
    $all_chapters[] = $chapter['chapter_id'];
}

$current_index = array_search($chapter_id, $all_chapters);
$prev_chapter = ($current_index > 0) ? $all_chapters[$current_index - 1] : null;
$next_chapter = ($current_index < count($all_chapters) - 1) ? $all_chapters[$current_index + 1] : null;

$images_stmt->close();
$comic_stmt->close();
$mysqli->close();

if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    if ($folder_path && is_dir($cover_image . '/' . $folder_path)) {
        $full_folder_path = $cover_image . '/' . $folder_path;
        $files = glob($full_folder_path . "*.{jpg,jpeg,png,gif,webp,mp4}", GLOB_BRACE);

        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        $files = array_slice($files, $offset, $limit);

        $content = "";
        foreach ($files as $file) {
            $file_extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($file_extension == 'mp4') {
                $content .= "<div class='video-container'>";
                $content .= "<video controls>";
                $content .= "<source src='" . htmlspecialchars($file) . "' type='video/mp4'>";
                $content .= "您的瀏覽器不支持影片播放。";
                $content .= "</video>";
                $content .= "</div>";
            } elseif (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $content .= "<img src='" . htmlspecialchars($file) . "' alt='章節圖片' class='chapter-image'>";
            }
        }

        echo json_encode([
            'content' => $content,
            'next_offset' => $offset + $limit
        ]);
    }

    exit;
}

?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>章節內容</title>
    <link rel="stylesheet" href="chapters.css">
</head>
<body>

<h1>章節內容</h1>

<div id="content"></div>

<div class="container">
    <div class="navigation">
        <?php if ($prev_chapter !== null): ?>
            <a href="chapter.php?comic_id=<?php echo $comic_id; ?>&chapter_id=<?php echo $prev_chapter; ?>" id="prev-chapter-btn">上一章</a>
        <?php endif; ?>
        <a href="comic/<?php echo htmlspecialchars($comic_id); ?>">返回目錄</a>
        <?php if ($next_chapter !== null): ?>
            <a href="chapter.php?comic_id=<?php echo $comic_id; ?>&chapter_id=<?php echo $next_chapter; ?>" id="next-chapter-btn">下一章</a>
        <?php endif; ?>
    </div>
    <div class="navi">
        <a href="/">返回首頁</a>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var comicId = <?php echo $comic_id; ?>;
    var chapterId = "<?php echo $chapter_id; ?>";
    var contentContainer = document.getElementById('content');
    var offset = 0;
    var limit = 10;
    var loading = false;

    function loadImages() {
        if (loading) return;
        loading = true;

        fetch(`chapter.php?comic_id=${comicId}&chapter_id=${chapterId}&ajax=true&offset=${offset}&limit=${limit}`)
            .then(response => response.json())
            .then(data => {
                contentContainer.innerHTML += data.content;
                offset = data.next_offset;
                loading = false;
            })
            .catch(error => {
                console.error('載入圖片錯誤:', error);
                loading = false;
            });
    }

    window.addEventListener("scroll", function() {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            loadImages();
        }
    });

    loadImages();
});
</script>

</body>
</html>