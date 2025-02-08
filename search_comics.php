<?php
function searchComics($mysqli, $keyword, $comics_per_page, $current_page) {
    $offset = ($current_page - 1) * $comics_per_page;
    $like_keyword = "%" . $keyword . "%";

    // 搜索漫畫
    $stmt = $mysqli->prepare("SELECT id, title, cover_image FROM comics WHERE title LIKE ? AND static = 1 LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $like_keyword, $comics_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $comics = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM comics WHERE title LIKE ? AND static = 1");
    $count_stmt->bind_param("s", $like_keyword);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_comics = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    return ['comics' => $comics, 'total_comics' => $total_comics];
}

function getAllComics($mysqli, $comics_per_page, $current_page) {
    $offset = ($current_page - 1) * $comics_per_page;

    // 查詢所有漫畫
    $stmt = $mysqli->prepare("SELECT id, title, cover_image FROM comics WHERE static = 1 LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $comics_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $comics = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 計算總數
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM comics WHERE static = 1");
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_comics = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    return ['comics' => $comics, 'total_comics' => $total_comics];
}
?>
