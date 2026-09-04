<?php
// 检查是否存在名为 'username' 的 cookie
if (isset($_COOKIE['username'])) {
    // 如果存在，跳转到 main.php
    header('Location: main.php');
} else {
    // 如果不存在，跳转到 login.php
    header('Location: login.html');
}

// 确保在调用 header 之后立即退出脚本
exit();
?>