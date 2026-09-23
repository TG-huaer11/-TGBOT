<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

$task_id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM broadcast_tasks WHERE id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    die("任务不存在");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>群发进度</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-3xl mx-auto p-6 py-12">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-8 text-white">
            <h1 id="title" class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-spinner fa-spin" id="icon"></i> 
                <span id="statusText">正在群发中，请不要关闭页面...</span>
            </h1>
            <p class="mt-2 opacity-90">共需发送 <strong><?= $task['total'] ?></strong> 人 · 任务ID: <?= $task_id ?></p>
        </div>
        
        <div class="p-8">
            <div class="mb-6">
                <div class="flex justify-between text-sm mb-2">
                    <span>进度</span>
                    <span><span id="sent">0</span> / <?= $task['total'] ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-5 overflow-hidden">
                    <div id="progress" class="bg-gradient-to-r from-indigo-500 to-purple-500 h-5 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-green-50 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-green-600" id="successCount">0</div>
                    <div class="text-sm text-green-700">成功</div>
                </div>
                <div class="bg-red-50 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-red-600" id="failCount">0</div>
                    <div class="text-sm text-red-700">失败</div>
                </div>
            </div>

            <div id="log" class="h-72 overflow-y-auto bg-gray-50 border border-gray-200 rounded-2xl p-4 text-sm font-mono space-y-1"></div>
            
            <div id="finishBtns" class="hidden mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="broadcast.php" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-2xl text-center">
                    <i class="fas fa-paper-plane mr-2"></i>继续群发
                </a>
                <a href="dashboard.php" class="px-8 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-2xl text-center">
                    <i class="fas fa-home mr-2"></i>返回首页
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const taskId = <?= $task_id ?>;
let lastLogId = 0;

function updateUI(data) {
    document.getElementById('sent').textContent = data.sent;
    document.getElementById('progress').style.width = (data.total > 0 ? (data.sent / data.total * 100) : 0) + '%';
    document.getElementById('successCount').textContent = data.sent - data.failed;
    document.getElementById('failCount').textContent = data.failed;

    if (data.logs && data.logs.length) {
        const logDiv = document.getElementById('log');
        data.logs.forEach(log => {
            const div = document.createElement('div');
            if (log.status === 'success') {
                div.innerHTML = `<span class="text-green-600">✅ ${log.chat_id}</span>`;
            } else {
                div.innerHTML = `<span class="text-red-600">❌ ${log.chat_id} (${log.error || '失败'})</span>`;
            }
            logDiv.appendChild(div);
            logDiv.scrollTop = logDiv.scrollHeight;
        });
        lastLogId = data.logs[data.logs.length - 1].id;
    }

    if (data.status === 'finished' || data.status === 'failed') {
        document.getElementById('icon').className = 'fas fa-check-circle';
        document.getElementById('statusText').textContent = '群发完成！';
        document.getElementById('title').classList.add('text-green-300');
        document.getElementById('finishBtns').classList.remove('hidden');
        clearInterval(timer);
    }
}

function poll() {
    fetch('broadcast_status.php?id=' + taskId + '&last_id=' + lastLogId)
        .then(r => r.json())
        .then(data => {
            if (data.ok) updateUI(data);
        })
        .catch(console.error);
}

const timer = setInterval(poll, 1500);
poll();
</script>
</body>
</html>