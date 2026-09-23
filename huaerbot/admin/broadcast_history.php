<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

define('PAGE_SIZE', 15);

// 查看详情
$detail_id = intval($_GET['detail'] ?? 0);
if ($detail_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM broadcast_tasks WHERE id = ?");
    $stmt->execute([$detail_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        die("任务不存在");
    }

    // 获取日志
    $logs = $pdo->prepare("SELECT * FROM broadcast_logs WHERE task_id = ? ORDER BY id ASC");
    $logs->execute([$detail_id]);
    $logs = $logs->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>群发详情 #<?= $detail_id ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    <body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="max-w-5xl mx-auto p-6">
        <div class="mb-6">
            <a href="broadcast_history.php" class="text-indigo-600 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>返回历史列表
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-8 text-white">
                <h1 class="text-2xl font-bold">群发任务 #<?= $task['id'] ?></h1>
                <p class="mt-2 opacity-90">创建时间：<?= $task['created_at'] ?></p>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gray-50 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-gray-800"><?= $task['total'] ?></div>
                        <div class="text-sm text-gray-500">总人数</div>
                    </div>
                    <div class="bg-green-50 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-green-600"><?= $task['sent'] - $task['failed'] ?></div>
                        <div class="text-sm text-green-700">成功</div>
                    </div>
                    <div class="bg-red-50 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-red-600"><?= $task['failed'] ?></div>
                        <div class="text-sm text-red-700">失败</div>
                    </div>
                    <div class="bg-blue-50 rounded-2xl p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            <?php
                            $statusMap = [
                                'pending'  => '等待中',
                                'running'  => '发送中',
                                'finished' => '已完成',
                                'failed'   => '失败'
                            ];
                            echo $statusMap[$task['status']] ?? $task['status'];
                            ?>
                        </div>
                        <div class="text-sm text-blue-700">状态</div>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">消息内容</h3>
                    <div class="bg-gray-50 rounded-2xl p-5 whitespace-pre-wrap"><?= htmlspecialchars($task['message'] ?: '(无文字)') ?></div>
                </div>

                <?php if ($task['photo_url']): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">图片</h3>
                    <a href="<?= htmlspecialchars($task['photo_url']) ?>" target="_blank" class="text-indigo-600 hover:underline">
                        <?= htmlspecialchars($task['photo_url']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <div>
                    <h3 class="font-semibold text-lg mb-4">发送明细（共 <?= count($logs) ?> 条）</h3>
                    <div class="max-h-96 overflow-y-auto border rounded-2xl">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left">用户ID</th>
                                    <th class="px-4 py-3 text-left">状态</th>
                                    <th class="px-4 py-3 text-left">错误信息</th>
                                    <th class="px-4 py-3 text-left">时间</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="px-4 py-3 font-mono"><?= $log['chat_id'] ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($log['status'] === 'success'): ?>
                                            <span class="text-green-600">✅ 成功</span>
                                        <?php else: ?>
                                            <span class="text-red-600">❌ 失败</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($log['error'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-gray-500"><?= $log['created_at'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-gray-400">暂无明细</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ====================== 列表页 ======================
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * PAGE_SIZE;

$total = $pdo->query("SELECT COUNT(*) FROM broadcast_tasks")->fetchColumn();
$totalPages = max(1, ceil($total / PAGE_SIZE));

$tasks = $pdo->query("SELECT * FROM broadcast_tasks ORDER BY id DESC LIMIT $offset, " . PAGE_SIZE)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>群发历史</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100">

<?php include 'nav.php'; ?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">群发历史</h1>
            <p class="text-gray-500 mt-1">共 <?= $total ?> 条群发记录</p>
        </div>
        <a href="broadcast.php" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-medium">
            <i class="fas fa-paper-plane mr-2"></i>新建群发
        </a>
    </div>

    <div class="bg-white rounded-3xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">ID</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">消息预览</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">总人数</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">成功</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">失败</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">状态</th>
                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-600">时间</th>
                        <th class="px-6 py-4 text-center text-sm font-medium text-gray-600">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-gray-400">暂无群发记录</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $t): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono text-sm">#<?= $t['id'] ?></td>
                            <td class="px-6 py-4 max-w-xs">
                                <div class="truncate text-sm">
                                    <?= htmlspecialchars(mb_substr($t['message'] ?: '(图片消息)', 0, 40)) ?>
                                    <?= mb_strlen($t['message'] ?? '') > 40 ? '...' : '' ?>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?= $t['total'] ?></td>
                            <td class="px-6 py-4 text-green-600 font-medium"><?= $t['sent'] - $t['failed'] ?></td>
                            <td class="px-6 py-4 text-red-600 font-medium"><?= $t['failed'] ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClass = [
                                    'pending'  => 'bg-yellow-100 text-yellow-700',
                                    'running'  => 'bg-blue-100 text-blue-700',
                                    'finished' => 'bg-green-100 text-green-700',
                                    'failed'   => 'bg-red-100 text-red-700'
                                ];
                                $statusText = [
                                    'pending'  => '等待中',
                                    'running'  => '发送中',
                                    'finished' => '已完成',
                                    'failed'   => '失败'
                                ];
                                $cls = $statusClass[$t['status']] ?? 'bg-gray-100 text-gray-700';
                                $txt = $statusText[$t['status']] ?? $t['status'];
                                ?>
                                <span class="px-3 py-1 text-xs rounded-full <?= $cls ?>"><?= $txt ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?= $t['created_at'] ?></td>
                            <td class="px-6 py-4 text-center">
                                <a href="?detail=<?= $t['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    查看详情
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between">
            <a href="?page=<?= max(1, $page-1) ?>" 
               class="px-4 py-2 border rounded-xl hover:bg-white <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                ← 上一页
            </a>
            <div class="flex gap-2">
                <?php for($i = max(1, $page-3); $i <= min($totalPages, $page+3); $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-3 py-2 rounded-xl <?= $i == $page ? 'bg-indigo-600 text-white' : 'hover:bg-gray-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <a href="?page=<?= min($totalPages, $page+1) ?>" 
               class="px-4 py-2 border rounded-xl hover:bg-white <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">
                下一页 →
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>