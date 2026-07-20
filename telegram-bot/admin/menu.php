<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php"); 
    exit;
}
require '../config.php';

$message = '';

// ==================== 处理表单 ====================
if (isset($_POST['add_button'])) {
    $name = trim($_POST['button_name']);
    $cmd = trim($_POST['command']);
    $reply = trim($_POST['reply_text']);
    $order = (int)$_POST['sort_order'];
    
    $pdo->prepare("INSERT INTO menu_buttons (button_name, command, reply_text, sort_order) VALUES (?,?,?,?)")
        ->execute([$name, $cmd, $reply, $order]);
    $message = "✅ 按钮添加成功！";
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM menu_buttons WHERE id=?")->execute([$_GET['delete']]);
    $message = "🗑️ 按钮已删除";
}

if (isset($_POST['update_button'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['button_name']);
    $cmd = trim($_POST['command']);
    $reply = trim($_POST['reply_text']);
    $order = (int)$_POST['sort_order'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    $pdo->prepare("UPDATE menu_buttons SET button_name=?, command=?, reply_text=?, sort_order=?, is_active=? WHERE id=?")
        ->execute([$name, $cmd, $reply, $order, $active, $id]);
    $message = "✅ 修改成功！";
}

$buttons = $pdo->query("SELECT * FROM menu_buttons ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>底部菜单管理</title>
    <link rel="stylesheet" href="style.css">
    <style>
        textarea { width: 100%; height: 140px; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-block; margin: 2px; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>🌟 底部菜单管理</h1>
        <div class="nav-links">
            <a href="dashboard.php">首页</a>
            <a href="members.php">成员管理</a>
            <a href="broadcast.php">群发消息</a>
            <a href="logout.php">退出</a>
        </div>
    </header>

    <div class="main-content">
        <?php if($message): ?>
            <p style="background:#d4edda; color:#155724; padding:15px; border-radius:8px;"><?= $message ?></p>
        <?php endif; ?>

        <h2>当前菜单按钮</h2>
        <table>
            <tr>
                <th>排序</th>
                <th>按钮名称</th>
                <th>指令</th>
                <th>回复内容</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php foreach($buttons as $b): ?>
            <tr>
                <td><?= $b['sort_order'] ?></td>
                <td><?= htmlspecialchars($b['button_name']) ?></td>
                <td><?= htmlspecialchars($b['command']) ?></td>
                <td><?= htmlspecialchars(mb_substr($b['reply_text'], 0, 80)) ?>...</td>
                <td><?= $b['is_active'] ? '✅ 启用' : '❌ 禁用' ?></td>
                <td>
                    <a href="?edit=<?= $b['id'] ?>" class="btn btn-edit">编辑</a>
                    <a href="?delete=<?= $b['id'] ?>" class="btn btn-delete" onclick="return confirm('确定删除此按钮？')">删除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2><?= isset($_GET['edit']) ? '编辑按钮' : '添加新按钮' ?></h2>
        <form method="post" class="card">
            <?php if(isset($_GET['edit'])): 
                $edit = $pdo->prepare("SELECT * FROM menu_buttons WHERE id=?");
                $edit->execute([$_GET['edit']]);
                $edit = $edit->fetch();
            ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <p>按钮名称：<input type="text" name="button_name" value="<?= htmlspecialchars($edit['button_name'] ?? '') ?>" required></p>
            <p>指令标识：<input type="text" name="command" value="<?= htmlspecialchars($edit['command'] ?? '') ?>" required></p>
            <p>回复内容：<br><textarea name="reply_text"><?= htmlspecialchars($edit['reply_text'] ?? '') ?></textarea></p>
            <p>排序：<input type="number" name="sort_order" value="<?= $edit['sort_order'] ?? 0 ?>"></p>
            
            <?php if(isset($edit)): ?>
                <p><input type="checkbox" name="is_active" <?= $edit['is_active'] ? 'checked' : '' ?>> 启用该按钮</p>
                <button type="submit" name="update_button" class="btn">保存修改</button>
            <?php else: ?>
                <button type="submit" name="add_button" class="btn">添加按钮</button>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>