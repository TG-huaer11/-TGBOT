<?php
require '../config.php';

$url = "https://huaer.shop/telegram-bot/bot/hook.php";

$result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($url));

echo "<h1>Webhook 设置结果</h1>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";
echo '<br><a href="/telegram-bot/admin/members.php">前往成员管理</a>';