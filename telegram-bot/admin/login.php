<?php
session_start();
require '../config.php';

if ($_POST) {
    if ($_POST['username'] === $ADMIN_USERNAME && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "账号或密码错误";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理后台登录</title>
</head>
<body>
    <h2>管理后台登录</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    
    <form method="post">
        用户名: <input type="text" name="username" value="root"><br><br>
        密码: <input type="password" name="password" value="admin123"><br><br>
        <button type="submit">登录</button>
    </form>
</body>
</html>