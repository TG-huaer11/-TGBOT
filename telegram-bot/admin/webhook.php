<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

$action = $_GET['action'] ?? '';

if ($action == 'on') {
    $url = "https://huaer.shop/telegram-bot/bot/hook.php";
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($url));
    $message = "✅ Webhook 已开启";
} 
elseif ($action == 'off') {
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook");
    $message = "✅ Webhook 已关闭";
} 
else {
    $status = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo");
    $info = json_decode($status, true);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Webhook 控制</title>
</head>
<body>
    <h2>Webhook 控制中心</h2>

    <?php if (isset($message)): ?>
        <p style="color:green; font-size:18px;"><strong><?= $message ?></strong></p>
    <?php endif; ?>

    <p>当前状态：
        <?php 
        $info = json_decode(file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo"), true);
        echo $info['result']['url'] ? '<span style="color:green">已开启</span>' : '<span style="color:red">已关闭</span>';
        ?>
    </p>

    <br>
    <a href="?action=on" style="padding:15px 30px; background:#28a745; color:white; text-decoration:none; margin-right:10px; border-radius:5px;">
        开启 Webhook
    </a>
    
    <a href="?action=off" style="padding:15px 30px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;">
        关闭 Webhook
    </a>

    <br><br><hr>
    <a href="dashboard.php">返回后台首页</a> | 
    <a href="sync.php">手动同步用户</a> | 
    <a href="members.php">成员列表</a>
</body>
</html>