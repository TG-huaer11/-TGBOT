<?php
require '../config.php';

function sendTelegram($method, $data) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

// 接收消息
$update = json_decode(file_get_contents("php://input"), true);

if ($update && isset($update['message'])) {
    $msg = $update['message'];
    $chat_id = $msg['chat']['id'];
    $user_id = $msg['from']['id'];

    // 保存用户到数据库
    $stmt = $pdo->prepare("INSERT INTO users (id, username, first_name, last_name) 
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE username=VALUES(username), 
        first_name=VALUES(first_name), last_name=VALUES(last_name), last_active=NOW()");
    $stmt->execute([
        $user_id,
        $msg['from']['username'] ?? null,
        $msg['from']['first_name'] ?? '',
        $msg['from']['last_name'] ?? ''
    ]);

    // 简单回复
    if (($msg['text'] ?? '') == '/start') {
        sendTelegram('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "✅ 机器人已连接！\n成员管理后台已记录你的信息。"
        ]);
    }
}

echo "OK";