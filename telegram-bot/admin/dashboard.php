<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
require '../config.php';
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Telegram 管理后台</title></head>
<body>
<h1>Telegram 机器人管理后台</h1>
<p><a href="broadcast.php">群发消息</a> | <a href="members.php">成员管理</a> | <a href="logout.php">退出</a></p>
</body>
</html>