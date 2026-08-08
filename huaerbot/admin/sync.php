<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// 获取当前 Webhook 状态
function getWebhookInfo() {
    $result = @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getWebhookInfo");
    return json_decode($result, true);
}

// 删除 Webhook
function deleteWebhook() {
    $result = @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/deleteWebhook");
    return json_decode($result, true);
}

// 设置 Webhook（请确认你的实际地址）
function setWebhook() {
    $url = "https://huaer.shop/huaerbot/bot/hook.php";  // ← 确认这个地址正确
    $result = @file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode($url));
    return json_decode($result, true);
}

// 通过 getUpdates 同步用户
function syncUsers() {
    global $pdo;
    $count = 0;
    
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?limit=100";
    $result = @file_get_contents($url);
    
    if ($result === false) {
        return ['success' => false, 'message' => '请求失败，请检查网络或 Token'];
    }
    
    $data = json_decode($result, true);
    
    if (isset($data['error_code']) && $data['error_code'] == 409) {
        return ['success' => false, 'message' => 'Webhook 仍在生效，请先关闭 Webhook 再同步'];
    }
    
    if (isset($data['result']) && is_array($data['result'])) {
        foreach ($data['result'] as $update) {
            $user = null;
            if (isset($update['message']['from'])) {
                $user = $update['message']['from'];
            } elseif (isset($update['callback_query']['from'])) {
                $user = $update['callback_query']['from'];
            }
            
            if ($user) {
                $stmt = $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, last_active) 
                    VALUES (?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    username = VALUES(username), 
                    first_name = VALUES(first_name), 
                    last_name = VALUES(last_name), 
                    last_active = NOW()");
                
                $stmt->execute([
                    $user['id'],
                    $user['username'] ?? null,
                    $user['first_name'] ?? '',
                    $user['last_name'] ?? ''
                ]);
                $count++;
            }
        }
    }
    
    return ['success' => true, 'count' => $count];
}

// 处理操作
$message = '';
$messageType = 'info';
$webhookInfo = getWebhookInfo();
$isWebhookActive = !empty($webhookInfo['result']['url']);

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'delete_webhook':
            $res = deleteWebhook();
            if (isset($res['ok']) && $res['ok']) {
                $message = '✅ Webhook 已成功关闭，现在可以同步用户了';
                $messageType = 'success';
                $isWebhookActive = false;
            } else {
                $message = '❌ 关闭 Webhook 失败';
                $messageType = 'error';
            }
            break;
            
        case 'set_webhook':
            $res = setWebhook();
            if (isset($res['ok']) && $res['ok']) {
                $message = '✅ Webhook 已重新开启';
                $messageType = 'success';
                $isWebhookActive = true;
            } else {
                $message = '❌ 开启 Webhook 失败，请检查地址是否正确';
                $messageType = 'error';
            }
            break;
            
        case 'sync':
            if ($isWebhookActive) {
                $message = '❌ 当前 Webhook 已开启，无法使用 getUpdates 同步。<br>请先点击下方「关闭 Webhook」按钮，再执行同步。';
                $messageType = 'error';
            } else {
                $result = syncUsers();
                if ($result['success']) {
                    $message = "✅ 同步完成！本次同步到 <strong>{$result['count']}</strong> 个用户";
                    $messageType = 'success';
                } else {
                    $message = '❌ ' . $result['message'];
                    $messageType = 'error';
                }
            }
            break;
    }
    
    // 重新获取最新状态
    $webhookInfo = getWebhookInfo();
    $isWebhookActive = !empty($webhookInfo['result']['url']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>手动同步用户</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50">

<?php include 'nav.php'; ?>

<div class="max-w-2xl mx-auto p-8">
    <div class="bg-white rounded-3xl shadow-xl p-10">
        
        <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
            <i class="fas fa-sync text-cyan-600"></i>
            手动同步用户
        </h1>
        <p class="text-gray-500 mb-8">通过 getUpdates 获取最近与机器人互动的用户</p>

        <!-- 当前状态 -->
        <div class="mb-8 p-5 rounded-2xl border <?= $isWebhookActive ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200' ?>">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-medium text-gray-800">当前 Webhook 状态</div>
                    <div class="text-sm mt-1 <?= $isWebhookActive ? 'text-amber-700' : 'text-green-700' ?>">
                        <?php if ($isWebhookActive): ?>
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            已开启 → 无法直接同步
                        <?php else: ?>
                            <i class="fas fa-check-circle mr-1"></i>
                            已关闭 → 可以同步
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php if ($isWebhookActive): ?>
                        <a href="?action=delete_webhook" 
                           class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-medium transition">
                            关闭 Webhook
                        </a>
                    <?php else: ?>
                        <a href="?action=set_webhook" 
                           class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition">
                            重新开启 Webhook
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 消息提示 -->
        <?php if ($message): ?>
            <div class="mb-8 p-5 rounded-2xl border 
                <?= $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                   ($messageType === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-blue-50 border-blue-200 text-blue-800') ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- 说明 -->
        <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-sm text-gray-600 leading-relaxed">
            <p class="font-medium text-gray-800 mb-2">使用说明：</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>如果 Webhook 已开启，请先点击「关闭 Webhook」</li>
                <li>关闭后点击下方「开始手动同步用户」按钮</li>
                <li>同步完成后，建议重新开启 Webhook，否则机器人将无法接收消息</li>
            </ol>
            <p class="mt-3 text-amber-600">
                <i class="fas fa-info-circle mr-1"></i>
                此方法只能获取最近 100 条更新中的用户，主要依赖 Webhook 实时写入更可靠。
            </p>
        </div>

        <!-- 同步按钮 -->
        <div class="text-center">
            <?php if ($isWebhookActive): ?>
                <button disabled
                        class="inline-flex items-center gap-3 px-8 py-4 bg-gray-300 text-gray-500 text-lg font-medium rounded-2xl cursor-not-allowed">
                    <i class="fas fa-sync"></i>
                    请先关闭 Webhook
                </button>
            <?php else: ?>
                <a href="?action=sync" 
                   class="inline-flex items-center gap-3 px-8 py-4 bg-cyan-600 hover:bg-cyan-700 text-white text-lg font-medium rounded-2xl transition">
                    <i class="fas fa-sync"></i>
                    开始手动同步用户
                </a>
            <?php endif; ?>
        </div>

        <div class="mt-10 flex gap-6 text-sm justify-center">
            <a href="members.php" class="text-indigo-600 hover:underline">→ 查看成员列表</a>
            <a href="webhook.php" class="text-indigo-600 hover:underline">→ Webhook 管理</a>
            <a href="dashboard.php" class="text-indigo-600 hover:underline">→ 返回首页</a>
        </div>
    </div>
</div>
</body>
</html>