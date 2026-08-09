<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// ====================== 保存转发接收人 ID ======================
$save_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_owner_id'])) {
    $new_id = trim($_POST['owner_chat_id'] ?? '');
    
    if (!preg_match('/^\d{5,20}$/', $new_id)) {
        $save_msg = ['type' => 'error', 'text' => '请输入正确的 Telegram 数字 ID'];
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO settings (name, value) VALUES ('owner_chat_id', ?) 
                                   ON DUPLICATE KEY UPDATE value = VALUES(value)");
            $stmt->execute([$new_id]);
            $save_msg = ['type' => 'success', 'text' => '✅ 已自动保存'];
        } catch (Exception $e) {
            $save_msg = ['type' => 'error', 'text' => '保存失败'];
        }
    }
}

// 读取当前 owner_chat_id
$current_owner_id = '';
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'owner_chat_id' LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_owner_id = $row['value'] ?? '';
} catch (Exception $e) {
    $current_owner_id = '';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telegram Bot 后台管理系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">

<?php include 'nav.php'; ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <!-- 标题 -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-robot text-indigo-600 mr-2"></i>
            Telegram Bot 后台管理系统
        </h1>
        <p class="text-gray-500 text-sm">欢迎回来，管理员</p>
    </div>

    <!-- 保存提示 -->
    <?php if ($save_msg): ?>
        <div class="mb-6 max-w-sm mx-auto p-3 rounded-xl border text-sm text-center
            <?= $save_msg['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <?= $save_msg['text'] ?>
        </div>
    <?php endif; ?>

    <!-- 功能卡片 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <!-- 1. 成员管理 -->
        <a href="members.php" class="group">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 h-full">
                <div class="w-12 h-12 mx-auto bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">成员管理</h3>
                <p class="text-gray-500 text-xs">查看和管理机器人用户</p>
            </div>
        </a>

        <!-- 2. 群发消息 -->
        <a href="broadcast.php" class="group">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 h-full">
                <div class="w-12 h-12 mx-auto bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">群发消息</h3>
                <p class="text-gray-500 text-xs">向指定或全部用户推送消息</p>
            </div>
        </a>

        <!-- 3. 底部菜单管理 -->
        <a href="menu.php" class="group">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 h-full">
                <div class="w-12 h-12 mx-auto bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-bars"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">底部菜单管理</h3>
                <p class="text-gray-500 text-xs">自定义按钮及回复内容</p>
            </div>
        </a>

        <!-- 4. Webhook 设置 -->
        <a href="webhook.php" class="group">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 h-full">
                <div class="w-12 h-12 mx-auto bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-amber-600 group-hover:text-white transition">
                    <i class="fas fa-cog"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">Webhook 设置</h3>
                <p class="text-gray-500 text-xs">管理机器人 Webhook</p>
            </div>
        </a>

        <!-- 5. 手动同步 -->
        <a href="sync.php" class="group">
            <div class="bg-white rounded-2xl shadow-md p-6 text-center transition-all duration-300 hover:-translate-y-1 hover:shadow-lg border border-gray-100 h-full">
                <div class="w-12 h-12 mx-auto bg-cyan-100 text-cyan-600 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:bg-cyan-600 group-hover:text-white transition">
                    <i class="fas fa-sync"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-1">手动同步用户</h3>
                <p class="text-gray-500 text-xs">从 Telegram 同步最新用户数据</p>
            </div>
        </a>

        <!-- 6. 消息转发接收人（已加上相同悬停效果） -->
        <div class="group">
            <div class="bg-white rounded-2xl shadow-md p-5 border border-gray-100 h-full flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                <div class="text-center mb-3">
                    <div class="w-12 h-12 mx-auto bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center text-2xl mb-3 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <i class="fas fa-share"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">消息转发接收人</h3>
                    <p class="text-gray-500 text-xs">用户消息将转发到此 ID</p>
                </div>

                <form method="post" id="ownerForm" class="mt-auto">
                    <input type="hidden" name="save_owner_id" value="1">
                    <input type="text" 
                           name="owner_chat_id" 
                           id="ownerChatId"
                           value="<?= htmlspecialchars($current_owner_id) ?>"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono text-center"
                           placeholder="输入 Telegram ID"
                           autocomplete="off">
                </form>
                <p class="text-xs text-gray-400 mt-2 text-center">
                    修改后点击其他地方自动保存
                </p>
            </div>
        </div>

    </div>

    <div class="text-center mt-12 text-gray-400 text-xs">
        TG @HuaerTRX
    </div>
</div>

<script>
const input = document.getElementById('ownerChatId');
const originalValue = input.value;

input.addEventListener('blur', function () {
    const newValue = this.value.trim();
    if (newValue !== originalValue && newValue !== '' && /^\d{5,20}$/.test(newValue)) {
        document.getElementById('ownerForm').submit();
    }
});

input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.blur();
    }
});
</script>

</body>
</html>