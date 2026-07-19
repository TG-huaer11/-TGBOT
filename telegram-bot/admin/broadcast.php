<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// 发送消息函数
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

if ($_POST) {
    $text = trim($_POST['text']);
    $buttons_json = trim($_POST['buttons'] ?? '');

    $keyboard = null;
    if (!empty($buttons_json)) {
        $keyboard = json_decode($buttons_json, true);
    }

    // 保存记录
    $stmt = $pdo->prepare("INSERT INTO broadcasts (message, keyboard) VALUES (?, ?)");
    $stmt->execute([$text, $buttons_json ?: null]);

    $users = $pdo->query("SELECT id FROM users WHERE is_blocked = 0")->fetchAll(PDO::FETCH_COLUMN);
    $sent = 0;

    foreach ($users as $chat_id) {
        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        $result = sendTelegram('sendMessage', $data);
        if (isset($result['ok']) && $result['ok']) {
            $sent++;
        }
        usleep(300000); // 限流
    }

    echo "<h2>✅ 群发完成！共发送给 $sent 个用户</h2>";
    echo '<p><a href="dashboard.php">返回后台</a></p>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>群发消息</title>
</head>
<body>
    <h2>群发消息 + 自定义按钮</h2>
    <form method="post">
        <textarea name="text" rows="10" cols="80" placeholder="输入要群发的消息内容，支持 HTML" required></textarea><br><br>
        
        <h3>自定义 Inline 按钮 (JSON格式，可选)</h3>
        <textarea name="buttons" rows="8" cols="80" placeholder='[
  [{"text":"👍 点赞","callback_data":"like"}],
  [{"text":"🔗 访问官网","url":"https://example.com"}]
]'></textarea><br><br>
        
        <button type="submit" style="padding:10px 20px;font-size:16px;">开始群发</button>
    </form>
    <p><strong>提示：</strong>按钮支持 callback_data（点击回调）和 url（直接打开链接）</p>
</body>
</html>