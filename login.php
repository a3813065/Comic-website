<?php
session_start();
require 'config.php';

// 檢查操作模式：登入或註冊
$mode = isset($_GET['mode']) && $_GET['mode'] == 'register' ? 'register' : 'login';

// 檢查是否已經登入（檢查 Cookie）
if (isset($_COOKIE['login_token'])) {
    $token = $_COOKIE['login_token'];
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE login_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['role'] = $user['role'];
        // 已登入，重定向到相應頁面
        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($mode == 'login') {
        // 登入邏輯
        $stmt = $mysqli->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // 生成一個唯一的 login_token
                $token = bin2hex(random_bytes(16));
                setcookie("login_token", $token, time() + (86400 * 7), "/"); // 設定一週有效期的 Cookie

                // 更新資料庫，將 login_token 和登入時間記錄
                $stmt = $mysqli->prepare("UPDATE users SET login_token = ?, login_time = NOW() WHERE id = ?");
                $stmt->bind_param("si", $token, $user['id']);
                $stmt->execute();

                $_SESSION['role'] = $user['role'];

                // 重定向到相應頁面
                if ($user['role'] == 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                echo "密碼錯誤！";
            }
        } else {
            echo "無效的使用者名稱！";
        }
    } elseif ($mode == 'register') {
        // 註冊邏輯
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "使用者名稱已被使用！";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // 註冊的使用者預設為普通用戶
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            $stmt->execute();
            echo "註冊成功！請登入。";
        }
    }
}
?>

<!-- 表單切換 -->
<h2><?php echo $mode == 'register' ? '註冊' : '登入'; ?></h2>
<form method="post" action="login.php?mode=<?php echo $mode; ?>">
    <label>使用者名稱: <input type="text" name="username" required></label><br>
    <label>密碼: <input type="password" name="password" required></label><br>
    <button type="submit"><?php echo $mode == 'register' ? '註冊' : '登入'; ?></button>
</form>

<p>
    <?php if ($mode == 'login'): ?>
        沒有帳號？ <a href="login.php?mode=register">註冊</a>
    <?php else: ?>
        已有帳號？ <a href="login.php?mode=login">登入</a>
    <?php endif; ?>
</p>
