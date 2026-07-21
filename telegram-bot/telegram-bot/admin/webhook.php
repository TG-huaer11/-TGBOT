<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

$action = $_GET['action'] ?? '';

if ($action == 'on') {
    // ←←← 修改这里为你的实际 hook.php 地址
    $url = "https://huaer.shop/telegram-bot/bot/hook.php";   // ← 修改这里

    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($url));
    $message = "✅ Webhook 已开启";
} 
elseif ($action == 'off') {
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook");
    $message = "✅ Webhook 已关闭";
}

$status = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo");
$info = json_decode($status, true);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>Webhook 控制中心</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="max-w-3xl mx-auto p-8">
    <div class="bg-white rounded-3xl shadow p-10 text-center">
        <h1 class="text-4xl font-bold mb-8">Webhook 控制中心</h1>

        <?php if (isset($message)): ?>
            <div class="bg-green-100 text-green-700 p-6 rounded-2xl text-xl mb-8"><?= $message ?></div>
        <?php endif; ?>

        <p class="text-2xl mb-8">
            当前状态： 
            <span class="<?= !empty($info['result']['url']) ? 'text-green-600' : 'text-red-600' ?>">
                <?= !empty($info['result']['url']) ? '🟢 已开启' : '🔴 已关闭' ?>
            </span>
        </p>

        <div class="flex gap-6 justify-center">
            <a href="?action=on" class="px-10 py-5 bg-green-600 text-white text-xl rounded-2xl hover:bg-green-700">开启 Webhook</a>
            <a href="?action=off" class="px-10 py-5 bg-red-600 text-white text-xl rounded-2xl hover:bg-red-700">关闭 Webhook</a>
        </div>
    </div>
</div>
</body>
</html>