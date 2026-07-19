<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// 批量导入
if (isset($_POST['batch_import'])) {
    $lines = explode("\n", trim($_POST['batch_data']));
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // 支持格式： user_id,username,first_name
        $parts = array_map('trim', explode(',', $line));
        $user_id = (int)$parts[0];
        
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

// 封禁/解封
if (isset($_GET['block'])) {
    $pdo->prepare("UPDATE users SET is_blocked=1 WHERE id=?")->execute([$_GET['block']]);
}
if (isset($_GET['unblock'])) {
    $pdo->prepare("UPDATE users SET is_blocked=0 WHERE id=?")->execute([$_GET['unblock']]);
}

$users = $pdo->query("SELECT * FROM users ORDER BY joined_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>成员管理 - 批量导入</title>
</head>
<body>
    <h2>成员管理 (<?= count($users) ?> 人)</h2>

    <?php if (isset($success)): ?>
        <p style="color:green; font-size:18px;"><?= $success ?></p>
    <?php endif; ?>

    <!-- 批量导入 -->
    <h3>批量导入成员（推荐）</h3>
    <form method="post" style="background:#f0f0f0; padding:20px; border-radius:8px;">
        <p>格式：每行一个用户，格式为 <strong>user_id,username,姓名</strong></p>
        <textarea name="batch_data" rows="10" cols="80" placeholder="123456789,@zhangsan,张三
987654321,@lisi,李四
111222333,,王五（没有用户名也行）"></textarea><br><br>
        <button type="submit" name="batch_import" value="1" style="padding:12px 30px; font-size:16px;">
            批量导入成员
        </button>
    </form>

    <hr>

    <!-- 成员列表 -->
    <h3>当前成员列表</h3>
    <table border="1" cellpadding="10" width="100%">
        <tr>
            <th>ID</th><th>用户名</th><th>姓名</th><th>加入时间</th><th>状态</th><th>操作</th>
        </tr>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td>@<?= htmlspecialchars($u['username'] ?? '') ?></td>
            <td><?= htmlspecialchars($u['first_name'] ?? '') ?></td>
            <td><?= $u['joined_at'] ?></td>
            <td><?= $u['is_blocked'] ? '🚫 已封禁' : '正常' ?></td>
            <td>
                <?php if($u['is_blocked']): ?>
                    <a href="?unblock=<?= $u['id'] ?>">解封</a>
                <?php else: ?>
                    <a href="?block=<?= $u['id'] ?>" onclick="return confirm('封禁？')">封禁</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <br>
    <a href="dashboard.php">返回首页</a>
</body>
</html>