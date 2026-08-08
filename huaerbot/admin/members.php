<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

define('OWNER_CHAT_ID', 8295042957);   // ← 改成你的 Telegram ID
define('PAGE_SIZE', 20);

// 发送 Telegram 消息函数
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

// ====================== 导出 CSV ======================
if (isset($_GET['export'])) {
    $stmt = $pdo->query("SELECT id, username, first_name, last_name, joined_at, is_blocked FROM users ORDER BY joined_at DESC");
    $members = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="members_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', '用户名', '姓名', '加入时间', '状态']);
    
    foreach ($members as $m) {
        $status = !empty($m['is_blocked']) ? '已封禁' : '正常';
        fputcsv($output, [
            $m['id'],
            '@' . ($m['username'] ?? ''),
            trim($m['first_name'] . ' ' . ($m['last_name'] ?? '')),
            $m['joined_at'],
            $status
        ]);
    }
    exit;
}

// ====================== 处理私信 ======================
if (isset($_POST['send_private'])) {
    $chat_id = (int)$_POST['chat_id'];
    $text = trim($_POST['text'] ?? '');
    $photo_url = trim($_POST['photo_url'] ?? '');

    if ($chat_id > 0 && !empty($text)) {
        if (!empty($photo_url)) {
            $result = sendTelegram('sendPhoto', ['chat_id' => $chat_id, 'photo' => $photo_url, 'caption' => $text, 'parse_mode' => 'HTML']);
        } else {
            $result = sendTelegram('sendMessage', ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML']);
        }
        $success = isset($result['ok']) && $result['ok'] ? "✅ 私信发送成功" : "❌ 发送失败";
    } else {
        $error = "❌ 消息内容不能为空";
    }
}

// 封禁 / 解封
if (isset($_GET['block'])) {
    $pdo->prepare("UPDATE users SET is_blocked=1 WHERE id=?")->execute([$_GET['block']]);
    header("Location: members.php"); exit;
}
if (isset($_GET['unblock'])) {
    $pdo->prepare("UPDATE users SET is_blocked=0 WHERE id=?")->execute([$_GET['unblock']]);
    header("Location: members.php"); exit;
}

// ====================== 分页逻辑 ======================
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PAGE_SIZE;

// 获取总数和当前页数据
$totalStmt = $pdo->query("SELECT COUNT(*) FROM users");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / PAGE_SIZE);

$users = $pdo->query("SELECT * FROM users ORDER BY joined_at DESC LIMIT $offset, " . PAGE_SIZE)->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>成员管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<?php include 'nav.php'; ?>

<div class="max-w-7xl mx-auto p-6">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">成员管理</h1>
            <p class="text-gray-500 mt-1">共 <strong class="text-indigo-600"><?= $total ?></strong> 名成员</p>
        </div>
        <div class="flex gap-3">
            <a href="?export=1" class="flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl shadow-sm transition">
                <i class="fas fa-download"></i> 导出 CSV
            </a>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-6"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6"><?= $error ?></div>
    <?php endif; ?>

    <!-- 批量导入 -->
    <div class="bg-white rounded-3xl shadow mb-8 p-6">
        <h2 class="text-xl font-semibold mb-4 flex items-center gap-3">
            <i class="fas fa-upload text-indigo-600"></i> 批量导入成员
        </h2>
        <form method="post">
            <textarea name="batch_data" rows="4" class="w-full p-4 border border-gray-200 rounded-2xl font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                      placeholder="123456789,@username,张三&#10;987654321,@lisi,李四"></textarea>
            <button type="submit" name="batch_import" value="1"
                    class="mt-4 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-medium flex items-center gap-2">
                <i class="fas fa-cloud-upload-alt"></i> 导入成员
            </button>
        </form>
    </div>

    <!-- 成员列表 -->
    <div class="bg-white rounded-3xl shadow overflow-hidden">
        <div class="px-8 py-5 border-b bg-gray-50 flex justify-between items-center">
            <h2 class="text-2xl font-semibold">当前成员列表</h2>
            <span class="text-sm text-gray-500">第 <?= $page ?> 页 / 共 <?= $totalPages ?> 页</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-8 py-4 text-left text-sm font-medium text-gray-600">ID</th>
                        <th class="px-8 py-4 text-left text-sm font-medium text-gray-600">用户名</th>
                        <th class="px-8 py-4 text-left text-sm font-medium text-gray-600">姓名</th>
                        <th class="px-8 py-4 text-left text-sm font-medium text-gray-600">加入时间</th>
                        <th class="px-8 py-4 text-left text-sm font-medium text-gray-600">状态</th>
                        <th class="px-8 py-4 text-center text-sm font-medium text-gray-600">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-8 py-5 font-mono"><?= htmlspecialchars($u['id']) ?></td>
                        <td class="px-8 py-5">@<?= htmlspecialchars($u['username'] ?? '-') ?></td>
                        <td class="px-8 py-5"><?= htmlspecialchars($u['first_name'] ?? '') ?></td>
                        <td class="px-8 py-5 text-sm text-gray-500"><?= $u['joined_at'] ?></td>
                        <td class="px-8 py-5">
                            <?php if(!empty($u['is_blocked'])): ?>
                                <span class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full">已封禁</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full">正常</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <div class="flex items-center justify-center gap-5 text-sm">
                                <button onclick="openPrivateMsg(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name'] ?? $u['id']) ?>')" 
                                        class="text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                                    <i class="fas fa-paper-plane"></i> 私信
                                </button>
                                <?php if(!empty($u['is_blocked'])): ?>
                                    <a href="?unblock=<?= $u['id'] ?>" class="text-green-600 hover:text-green-700">解封</a>
                                <?php else: ?>
                                    <a href="?block=<?= $u['id'] ?>" onclick="return confirm('确定封禁？')" class="text-red-600 hover:text-red-700">封禁</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页导航 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-8 py-5 bg-gray-50 border-t flex items-center justify-between">
            <div>
                <a href="?page=<?= max(1, $page-1) ?>" class="px-5 py-2 border rounded-xl hover:bg-white <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                    ← 上一页
                </a>
            </div>
            <div class="flex gap-2">
                <?php for($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-4 py-2 rounded-xl <?= $i == $page ? 'bg-indigo-600 text-white' : 'hover:bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <div>
                <a href="?page=<?= min($totalPages, $page+1) ?>" class="px-5 py-2 border rounded-xl hover:bg-white <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">
                    下一页 →
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 私信弹窗 -->
<div id="privateMsgModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4">
        <div class="p-6 border-b">
            <h3 class="text-xl font-semibold">发送私信给 <span id="modalUser" class="text-indigo-600"></span></h3>
        </div>
        <form method="post" class="p-6 space-y-5">
            <input type="hidden" name="chat_id" id="modalChatId">
            <div>
                <label class="block text-sm font-medium mb-2">图片链接（可选）</label>
                <input type="url" name="photo_url" class="w-full px-4 py-3 border rounded-2xl focus:ring-2 focus:ring-indigo-500" placeholder="https://...">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">消息内容 <span class="text-red-500">*</span></label>
                <textarea name="text" rows="5" required class="w-full px-4 py-3 border rounded-2xl focus:ring-2 focus:ring-indigo-500" placeholder="输入消息..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 py-4 border rounded-2xl">取消</button>
                <button type="submit" name="send_private" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl">发送</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPrivateMsg(chatId, name) {
    document.getElementById('modalChatId').value = chatId;
    document.getElementById('modalUser').textContent = name;
    document.getElementById('privateMsgModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('privateMsgModal').classList.add('hidden');
}
</script>
</body>
</html>