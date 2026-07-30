<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

$action = $_GET['action'] ?? '';

if ($action == 'on') {
    $url = "https://huaer.shop/huaerbot/bot/hook.php";   // 请确认你的域名正确
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($url));
    $message = "✅ Webhook 已开启";
} 
elseif ($action == 'off') {
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook");
    $message = "✅ Webhook 已关闭";
} 

// 获取当前状态
$status = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo");
$info = json_decode($status, true);
$is_active = !empty($info['result']['url']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Webhook 控制中心</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
<div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-xl p-10">

    <!-- 返回首页按钮 -->
    <div class="flex justify-end mb-6">
        <a href="dashboard.php" 
           class="bg-gray-700 hover:bg-gray-800 text-white px-6 py-3 rounded-2xl flex items-center gap-2 transition">
            ← 返回首页
        </a>
    </div>

    <h1 class="text-4xl font-bold text-center mb-8">Webhook 控制中心</h1>

    <div class="text-center mb-10">
        <p class="text-xl mb-3">当前状态：</p>
        <span class="inline-flex items-center gap-3 text-2xl font-medium">
            <span class="w-4 h-4 rounded-full <?= $is_active ? 'bg-green-500' : 'bg-red-500' ?>"></span>
            <?= $is_active ? '🟢 已开启' : '🔴 已关闭' ?>
        </span>
    </div>

    <?php if (isset($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-8 py-5 rounded-2xl text-center text-xl mb-10">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-center gap-6">
        <a href="?action=on" 
           class="bg-green-600 hover:bg-green-700 text-white px-12 py-5 rounded-3xl text-xl font-medium transition">
            开启 Webhook
        </a>
        
        <a href="?action=off" 
           class="bg-red-600 hover:bg-red-700 text-white px-12 py-5 rounded-3xl text-xl font-medium transition">
            关闭 Webhook
        </a>
    </div>

    <div class="mt-12 text-center text-gray-500">
        <p>Webhook URL: <code class="bg-gray-100 px-3 py-1 rounded">https://huaer.shop/huaerbot/bot/hook.php</code></p>
    </div>

    <div class="mt-10 text-center">
        <a href="dashboard.php" 
           class="text-blue-600 hover:text-blue-700 font-medium">← 返回后台首页</a>
    </div>
</div>
</body>
</html>