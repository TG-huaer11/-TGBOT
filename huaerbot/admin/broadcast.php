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

// ====================== 处理群发 ======================
if ($_POST) {
    $text = trim($_POST['text'] ?? '');
    $photo_url = trim($_POST['photo_url'] ?? '');
    $buttons_json = trim($_POST['buttons'] ?? '');
    $selected_ids = $_POST['selected_users'] ?? [];

    // 必须选择至少一个成员
    if (empty($selected_ids) || !is_array($selected_ids)) {
        $error = "请至少选择一个要发送的成员！";
    } elseif (empty($text) && empty($photo_url)) {
        $error = "消息内容或图片至少填写一项！";
    } else {
        $keyboard = null;
        if (!empty($buttons_json)) {
            $keyboard = json_decode($buttons_json, true);
        }

        // 保存记录
        $stmt = $pdo->prepare("INSERT INTO broadcasts (message, keyboard) VALUES (?, ?)");
        $stmt->execute([$text, $buttons_json ?: null]);

        // 只发送给选中的用户（并过滤掉已封禁的）
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id IN ($placeholders) AND is_blocked = 0");
        $stmt->execute(array_map('intval', $selected_ids));
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sent = 0;
        foreach ($users as $chat_id) {
            if (!empty($photo_url)) {
                $data = [
                    'chat_id'    => $chat_id,
                    'photo'      => $photo_url,
                    'caption'    => $text,
                    'parse_mode' => 'HTML'
                ];
                if ($keyboard) $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
                $result = sendTelegram('sendPhoto', $data);
            } else {
                $data = [
                    'chat_id'    => $chat_id,
                    'text'       => $text,
                    'parse_mode' => 'HTML'
                ];
                if ($keyboard) $data['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
                $result = sendTelegram('sendMessage', $data);
            }

            if (isset($result['ok']) && $result['ok']) {
                $sent++;
            }
            usleep(300000); // 0.3 秒间隔
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
<body class="bg-gray-50 min-h-screen">

<?php include 'nav.php'; ?>

<div class="flex items-center justify-center py-16">
    <div class="max-w-lg w-full mx-auto bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 p-10 text-white text-center">
            <div class="w-20 h-20 mx-auto bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mb-6">
                <i class="fas fa-check text-5xl"></i>
            </div>
            <h1 class="text-3xl font-bold">群发完成！</h1>
            <p class="text-xl mt-3 opacity-90">共成功发送给 <strong class="text-4xl"><?= $sent ?></strong> 个用户</p>
            <p class="text-sm mt-2 opacity-80">（共选择 <?= count($selected_ids) ?> 人）</p>
        </div>
        <div class="p-10 space-y-8">
            <div class="text-center">
                <p class="text-gray-600">所选用户已接收到您的消息</p>
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
</div>
</body>
</html>
<?php
        exit;
    }
}

// 获取所有未封禁成员
$users = $pdo->query("SELECT id, username, first_name, last_name FROM users WHERE is_blocked = 0 ORDER BY joined_at DESC")->fetchAll();
?>
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

<?php include 'nav.php'; ?>

<div class="max-w-5xl mx-auto p-6">
    <div class="bg-white rounded-3xl shadow-xl p-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold">群发消息</h1>
            <p class="text-gray-500">支持文字 + 图片 + Inline 按钮 · 可指定成员</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-8" id="broadcastForm">
            <!-- 消息内容 -->
            <div>
                <label class="block text-lg font-medium mb-3">图片链接（可选）</label>
                <input type="url" name="photo_url" 
                       class="w-full p-5 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="https://example.com/image.jpg"
                       value="<?= htmlspecialchars($_POST['photo_url'] ?? '') ?>">
            </div>

            <div>
                <label class="block text-lg font-medium mb-3">消息内容 / 图片说明</label>
                <textarea name="text" rows="6" 
                          class="w-full p-6 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-lg"
                          placeholder="输入要群发的文字内容，支持 HTML"><?= htmlspecialchars($_POST['text'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-lg font-medium mb-3">Inline 按钮（JSON，可选）</label>
                <textarea name="buttons" rows="5"
                          class="w-full p-6 border border-gray-200 rounded-3xl font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder='[
  [{"text":"👍 点赞","callback_data":"like"}],
  [{"text":"🔗 访问官网","url":"https://example.com"}]
]'><?= htmlspecialchars($_POST['buttons'] ?? '') ?></textarea>
            </div>

            <!-- 成员选择区域 -->
            <div class="border border-gray-200 rounded-3xl overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-semibold">选择发送对象</h2>
                        <span class="text-sm text-gray-500">共 <?= count($users) ?> 名可用成员</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="selectAll(true)"
                                class="px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-xl text-sm font-medium">
                            <i class="fas fa-check-double mr-1"></i> 全选
                        </button>
                        <button type="button" onclick="selectAll(false)"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium">
                            <i class="fas fa-times mr-1"></i> 取消全选
                        </button>
                        <span id="selectedCount" class="text-sm font-medium text-indigo-600">已选 0 人</span>
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <?php if (empty($users)): ?>
                        <div class="p-10 text-center text-gray-500">暂无可用成员</div>
                    <?php else: ?>
                        <table class="w-full">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 text-left w-12">
                                        <input type="checkbox" id="checkAll" onclick="toggleAll(this)" class="w-5 h-5 rounded">
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">ID</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">用户名</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">姓名</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($users as $u): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="selected_users[]" value="<?= $u['id'] ?>"
                                               class="user-checkbox w-5 h-5 rounded" onchange="updateCount()">
                                    </td>
                                    <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($u['id']) ?></td>
                                    <td class="px-6 py-4">@<?= htmlspecialchars($u['username'] ?? '-') ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xl font-semibold rounded-3xl hover:brightness-110 transition flex items-center justify-center gap-3"
                    onclick="return confirmSend()">
                <i class="fas fa-paper-plane"></i>
                开始群发（发送给选中成员）
            </button>
        </form>
    </div>
</div>

<script>
function selectAll(checked) {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = checked);
    document.getElementById('checkAll').checked = checked;
    updateCount();
}

function toggleAll(source) {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = source.checked);
    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = '已选 ' + count + ' 人';
    
    const total = document.querySelectorAll('.user-checkbox').length;
    document.getElementById('checkAll').checked = (count === total && total > 0);
    document.getElementById('checkAll').indeterminate = (count > 0 && count < total);
}

function confirmSend() {
    const count = document.querySelectorAll('.user-checkbox:checked').length;
    if (count === 0) {
        alert('请至少选择一个要发送的成员！');
        return false;
    }
    return confirm('确定要向选中的 ' + count + ' 个成员发送消息吗？');
}

// 页面加载时初始化计数
updateCount();
</script>
</body>
</html>