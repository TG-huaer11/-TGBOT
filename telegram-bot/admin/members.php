<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// ==================== 处理逻辑 ====================

// 批量导入
if (isset($_POST['batch_import'])) {
    $lines = explode("\n", trim($_POST['batch_data']));
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = array_map('trim', explode(',', $line));
        $user_id = (int)($parts[0] ?? 0);
        
        if ($user_id > 0) {
            $username = $parts[1] ?? '';
            $first_name = $parts[2] ?? '';
            
            $stmt = $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, joined_at) 
                VALUES (?, ?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE username=VALUES(username), first_name=VALUES(first_name)");
            $stmt->execute([$user_id, $username, $first_name, '']);
            $count++;
        }
    }
    $success = "✅ 成功导入 $count 个成员！";
}

// 封禁 / 解封
if (isset($_GET['block'])) {
    $pdo->prepare("UPDATE users SET is_blocked=1 WHERE id=?")->execute([$_GET['block']]);
    header("Location: members.php");
    exit;
}
if (isset($_GET['unblock'])) {
    $pdo->prepare("UPDATE users SET is_blocked=0 WHERE id=?")->execute([$_GET['unblock']]);
    header("Location: members.php");
    exit;
}

// 获取成员列表
$users = $pdo->query("SELECT * FROM users ORDER BY joined_at DESC")->fetchAll();
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
<body class="bg-gray-50">
<div class="max-w-7xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">成员管理</h1>
            <p class="text-gray-500">共 <strong><?= count($users) ?></strong> 名成员</p>
        </div>
        <a href="dashboard.php" class="flex items-center gap-2 text-indigo-600 hover:text-indigo-700">
            <i class="fas fa-arrow-left"></i> 返回后台
        </a>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-8">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <!-- 批量导入 -->
    <div class="bg-white rounded-3xl shadow p-8 mb-10">
        <h2 class="text-2xl font-semibold mb-4 flex items-center gap-3">
            <i class="fas fa-upload text-indigo-600"></i> 批量导入成员
        </h2>
        <form method="post">
            <textarea name="batch_data" rows="8" 
                      class="w-full p-5 border border-gray-200 rounded-2xl font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      placeholder="123456789,@zhangsan,张三&#10;987654321,@lisi,李四"></textarea>
            <button type="submit" name="batch_import" value="1"
                    class="mt-6 px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-medium flex items-center gap-2">
                <i class="fas fa-cloud-upload-alt"></i>
                批量导入成员
            </button>
        </form>
    </div>

    <!-- 成员列表 -->
    <div class="bg-white rounded-3xl shadow overflow-hidden">
        <div class="p-6 border-b bg-gray-50">
            <h2 class="text-2xl font-semibold">当前成员列表</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left">ID</th>
                        <th class="px-6 py-4 text-left">用户名</th>
                        <th class="px-6 py-4 text-left">姓名</th>
                        <th class="px-6 py-4 text-left">加入时间</th>
                        <th class="px-6 py-4 text-left">状态</th>
                        <th class="px-6 py-4 text-center">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-5 font-mono"><?= htmlspecialchars($u['id']) ?></td>
                        <td class="px-6 py-5">@<?= htmlspecialchars($u['username'] ?? '') ?></td>
                        <td class="px-6 py-5"><?= htmlspecialchars($u['first_name'] ?? '') ?></td>
                        <td class="px-6 py-5 text-gray-500"><?= $u['joined_at'] ?></td>
                        <td class="px-6 py-5">
                            <?php if(!empty($u['is_blocked'])): ?>
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">已封禁</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">正常</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <?php if(!empty($u['is_blocked'])): ?>
                                <a href="?unblock=<?= $u['id'] ?>" class="text-green-600 hover:text-green-700 font-medium">解封</a>
                            <?php else: ?>
                                <a href="?block=<?= $u['id'] ?>" onclick="return confirm('确定封禁此用户吗？')" 
                                   class="text-red-600 hover:text-red-700 font-medium">封禁</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>