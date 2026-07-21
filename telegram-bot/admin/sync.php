<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// 获取机器人所有已知用户（通过 getUpdates 方式）
function getAllUsers() {
    global $pdo;
    $count = 0;
    
    // 获取最近的更新（最多100条）
    $result = file_get_contents("https://api.telegram.org/bot" . BOT_TOKEN . "/getUpdates?limit=100&allowed_updates=[\"message\",\"callback_query\"]");
    $data = json_decode($result, true);
    
    if (isset($data['result'])) {
        foreach ($data['result'] as $update) {
            if (isset($update['message'])) {
                $msg = $update['message'];
                $user = $msg['from'];
                
                $stmt = $pdo->prepare("INSERT INTO users (id, username, first_name, last_name, last_active) 
                    VALUES (?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    username=VALUES(username), first_name=VALUES(first_name), 
                    last_name=VALUES(last_name), last_active=NOW()");
                
                $stmt->execute([
                    $user['id'],
                    $user['username'] ?? null,
                    $user['first_name'] ?? '',
                    $user['last_name'] ?? ''
                ]);
                $count++;
            }
        }
    }
    return $count;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>手动同步用户</title>
</head>
<body>
    <h2>手动同步用户</h2>
    
    <?php if (isset($_GET['action']) && $_GET['action'] == 'sync'): ?>
        <h3 style="color:green">
            ✅ 同步完成！本次同步 <?= getAllUsers(); ?> 个用户
        </h3>
    <?php endif; ?>
    
    <p>点击下方按钮可以尝试同步最近与机器人互动过的用户（包括老用户）</p>
    <a href="?action=sync" style="padding:15px 30px; background:#0066cc; color:white; text-decoration:none; font-size:18px; border-radius:5px;">
        开始手动同步用户
    </a>
    
    <br><br>
    <a href="members.php">→ 查看成员列表</a> | 
    <a href="dashboard.php">→ 返回后台</a>
</body>
</html>