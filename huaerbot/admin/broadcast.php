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
    $text = trim($_POST['text'] ?? '');
    $photo_url = trim($_POST['photo_url'] ?? '');
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
        if (!empty($photo_url)) {
            $data = [
                'chat_id' => $chat_id,
                'photo'   => $photo_url,
                'caption' => $text,
                'parse_mode' => 'HTML'
            ];
            if ($keyboard) $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
            $result = sendTelegram('sendPhoto', $data);
        } else {
            $data = [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => 'HTML'
            ];
            if ($keyboard) $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
            $result = sendTelegram('sendMessage', $data);
        }

        if (isset($result['ok']) && $result['ok']) {
            $sent++;
        }
        usleep(300000);
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>群发完成</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-lg w-full mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden">
        <!-- 成功头部 -->
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-10 text-white text-center">
            <div class="w-20 h-20 mx-auto bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-check text-5xl"></i>
            </div>
            <h1 class="text-3xl font-bold">群发完成！</h1>
            <p class="text-xl mt-3 opacity-90">共成功发送给 <strong class="text-4xl"><?= $sent ?></strong> 个用户</p>
        </div>

        <!-- 内容区 -->
        <div class="p-10 space-y-8">
            <div class="text-center">
                <p class="text-gray-600">所有用户已接收到您的消息</p>
            </div>

            <div class="flex flex-col gap-4">
                <a href="broadcast.php" 
                   class="flex items-center justify-center gap-3 py-5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-2xl transition text-lg">
                    <i class="fas fa-paper-plane"></i>
                    继续群发
                </a>
                
                <a href="dashboard.php" 
                   class="flex items-center justify-center gap-3 py-5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-2xl transition text-lg">
                    <i class="fas fa-home"></i>
                    返回首页
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
?>

<!-- 原表单页面保持不变（以下为表单部分） -->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>群发消息</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50">
<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-3xl shadow-xl p-10">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold">群发消息</h1>
                <p class="text-gray-500">支持文字 + 图片 + Inline 按钮</p>
            </div>
            <a href="dashboard.php" class="flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-2xl text-gray-700 font-medium">
                <i class="fas fa-home"></i> 返回首页
            </a>
        </div>

        <form method="post" class="space-y-8">
            <div>
                <label class="block text-lg font-medium mb-3">图片链接（可选）</label>
                <input type="url" name="photo_url" 
                       class="w-full p-5 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="https://example.com/image.jpg">
            </div>

            <div>
                <label class="block text-lg font-medium mb-3">消息内容 / 图片说明</label>
                <textarea name="text" rows="8" 
                          class="w-full p-6 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-lg"
                          placeholder="输入要群发的文字内容，支持 HTML" required></textarea>
            </div>

            <div>
                <label class="block text-lg font-medium mb-3">Inline 按钮（JSON，可选）</label>
                <textarea name="buttons" rows="6"
                          class="w-full p-6 border border-gray-200 rounded-3xl font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder='[
  [{"text":"👍 点赞","callback_data":"like"}],
  [{"text":"🔗 访问官网","url":"https://example.com"}]
]'></textarea>
            </div>

            <button type="submit" 
                    class="w-full py-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xl font-semibold rounded-3xl hover:brightness-110 transition flex items-center justify-center gap-3">
                <i class="fas fa-paper-plane"></i>
                开始群发
            </button>
        </form>
    </div>
</div>
</body>
</html>