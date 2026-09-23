<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

$error = '';

// ====================== 处理提交任务 ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');
    $photo_url = trim($_POST['photo_url'] ?? '');
    $buttons_json = trim($_POST['buttons'] ?? '');
    $selected_ids = $_POST['selected_users'] ?? [];

    if (empty($selected_ids) || !is_array($selected_ids)) {
        $error = "请至少选择一个要发送的成员！";
    } elseif (empty($text) && empty($photo_url)) {
        $error = "消息内容或图片至少填写一项！";
    } else {
        // 过滤已封禁用户
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id IN ($placeholders) AND is_blocked = 0");
        $stmt->execute(array_map('intval', $selected_ids));
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($users)) {
            $error = "没有可发送的用户！";
        } else {
            // 写入任务
            $stmt = $pdo->prepare("INSERT INTO broadcast_tasks 
                (message, photo_url, keyboard, selected_ids, total, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $text,
                $photo_url ?: null,
                $buttons_json ?: null,
                json_encode($users),
                count($users)
            ]);
            $task_id = $pdo->lastInsertId();

            // 跳转到进度页面
            header("Location: broadcast_progress.php?id=" . $task_id);
            exit;
        }
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
            <p class="text-gray-500">支持文字 + 图片 + Inline 按钮 · 可指定成员（后台队列发送，不会卡死）</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-8" id="broadcastForm">
            <!-- 图片链接 -->
            <div>
                <label class="block text-lg font-medium mb-3">图片链接（可选）</label>
                <input type="url" name="photo_url" 
                       class="w-full p-5 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="https://example.com/image.jpg"
                       value="<?= htmlspecialchars($_POST['photo_url'] ?? '') ?>">
            </div>

            <!-- 消息内容 -->
            <div>
                <label class="block text-lg font-medium mb-3">消息内容 / 图片说明</label>
                <textarea name="text" rows="6" 
                          class="w-full p-6 border border-gray-200 rounded-3xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-lg"
                          placeholder="输入要群发的文字内容，支持 HTML"><?= htmlspecialchars($_POST['text'] ?? '') ?></textarea>
            </div>

            <!-- Inline 按钮 -->
            <div>
                <label class="block text-lg font-medium mb-3">Inline 按钮（JSON，可选）</label>
                <textarea name="buttons" rows="5"
                          class="w-full p-6 border border-gray-200 rounded-3xl font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder='[
  [{"text":"👍 点赞","callback_data":"like"}],
  [{"text":"🔗 访问官网","url":"https://example.com"}]
]'><?= htmlspecialchars($_POST['buttons'] ?? '') ?></textarea>
            </div>

            <!-- 成员选择 -->
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
                                    <td class="px-6 py-3">
                                        <input type="checkbox" name="selected_users[]" value="<?= $u['id'] ?>" 
                                               class="user-check w-5 h-5 rounded" onchange="updateSelectedCount()">
                                    </td>
                                    <td class="px-6 py-3 text-sm"><?= $u['id'] ?></td>
                                    <td class="px-6 py-3 text-sm">
                                        <?= $u['username'] ? '@' . htmlspecialchars($u['username']) : '-' ?>
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" 
                        class="w-full py-5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xl font-bold rounded-3xl shadow-lg transition">
                    <i class="fas fa-paper-plane mr-3"></i>开始群发（发送给选中成员）
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSelectedCount() {
    const count = document.querySelectorAll('.user-check:checked').length;
    document.getElementById('selectedCount').textContent = '已选 ' + count + ' 人';
    document.getElementById('checkAll').checked = count === document.querySelectorAll('.user-check').length && count > 0;
}

function selectAll(checked) {
    document.querySelectorAll('.user-check').forEach(cb => cb.checked = checked);
    updateSelectedCount();
}

function toggleAll(el) {
    selectAll(el.checked);
}

updateSelectedCount();
</script>
</body>
</html>