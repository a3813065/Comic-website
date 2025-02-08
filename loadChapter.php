<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['comic_id']) || !is_numeric($_GET['comic_id']) || !isset($_GET['chapter_id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

require 'config.php';


$comic_id = intval($_GET['comic_id']);
$chapter_id = $mysqli->real_escape_string($_GET['chapter_id']);

$images_sql = "SELECT image_url FROM chapter_images WHERE comic_id = ? AND chapter_id = ?";
$images_stmt = $mysqli->prepare($images_sql);
$images_stmt->bind_param("is", $comic_id, $chapter_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$image_urls = [];
if ($images_result->num_rows > 0) {
    while ($image_data = $images_result->fetch_assoc()) {
        $image_urls[] = $image_data['image_url'];
    }
}
$images_stmt->close();
$mysqli->close();

echo json_encode(["chapter_id" => $chapter_id, "images" => $image_urls]);
