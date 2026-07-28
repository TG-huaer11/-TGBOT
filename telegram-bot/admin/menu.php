<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require '../config.php';

// ====================== 添加按钮 ======================
if (isset($_POST['add'])) {
    $name       = trim($_POST['name'] ?? '');
    $command    = trim($_POST['command'] ?? '');
    $reply_text = trim($_POST['content'] ?? '');
    $photo_url  = trim($_POST['photo_url'] ?? '');
    $sort_order = (int)($_POST['sort'] ?? 0);
    $is_active  = isset($_POST['enabled']) ? 1 : 0;

    if (!empty($name) && !empty($command)) {
        $stmt = $pdo->prepare("INSERT INTO menu_buttons 
            (button_name, command, reply_text, photo_url, sort_order, is_active) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $command, $reply_text, $photo_url, $sort_order, $is_active])) {
            $success = "✅ 新增按钮成功！";
        } else {
            $error = "❌ 新增失败：数据库错误";
        }
    } else {
        $error = "❌ 按钮名称和指令标识不能为空";
    }
}

// ====================== 更新按钮 ======================
if (isset($_POST['update'])) {
    $id         = (int)$_POST['id'];
    $name       = trim($_POST['name'] ?? '');
    $command    = trim($_POST['command'] ?? '');
    $reply_text = trim($_POST['content'] ?? '');
    $photo_url  = trim($_POST['photo_url'] ?? '');
    $sort_order = (int)($_POST['sort'] ?? 0);
    $is_active  = isset($_POST['enabled']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE menu_buttons SET button_name=?, command=?, reply_text=?, photo_url=?, sort_order=?, is_active=? WHERE id=?");
    $stmt->execute([$name, $command, $reply_text, $photo_url, $sort_order, $is_active, $id]);
    $success = "✅ 修改成功！";
}

// ====================== 删除按钮 ======================
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM menu_buttons WHERE id=?")->execute([(int)$_GET['delete']]);
    header("Location: menu.php");
    exit;
}

// 查询所有按钮
$menus = $pdo->query("SELECT * FROM menu_buttons ORDER BY sort_order ASC, id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>底部菜单管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .back-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
    </style>
</head>
<body class="bg-gray-50 p-8">
<div class="max-w-6xl mx-auto bg-white rounded-3xl shadow-xl p-8">

    <!-- 返回首页按钮 -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">底部菜单管理</h1>
        <a href="dashboard.php" 
           class="back-btn text-white px-8 py-3 rounded-2xl font-medium flex items-center gap-2 hover:shadow-lg transition">
            ← 返回首页
        </a>
    </div>

    <h2 class="text-2xl font-semibold mb-6">当前菜单按钮 (<?= count($menus) ?> 个)</h2>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-6"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6"><?= $error ?></div>
    <?php endif; ?>

    <?php foreach($menus as $m): ?>
    <div class="flex justify-between items-center p-6 border rounded-2xl mb-4 hover:bg-gray-50">
        <div class="flex-1">
            <span class="text-lg font-medium"><?= htmlspecialchars($m['button_name']) ?></span>
            <span class="ml-6 text-gray-500"><?= htmlspecialchars($m['command']) ?></span>
        </div>
        <div class="flex gap-3">
            <a href="?edit=<?= $m['id'] ?>" 
               class="bg-blue-600 text-white px-6 py-3 rounded-2xl hover:bg-blue-700">编辑</a>
            <a href="?delete=<?= $m['id'] ?>" 
               onclick="return confirm('确定删除此按钮？')" 
               class="bg-red-600 text-white px-6 py-3 rounded-2xl hover:bg-red-700">删除</a>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- 添加/编辑表单 -->
    <div class="mt-12 bg-gray-50 rounded-3xl p-8">
        <h2 class="text-2xl font-bold mb-6"><?= isset($_GET['edit']) ? '编辑按钮' : '添加新按钮' ?></h2>
        
        <?php 
        $edit = null;
        if (isset($_GET['edit'])) {
            $stmt = $pdo->prepare("SELECT * FROM menu_buttons WHERE id = ?");
            $stmt->execute([(int)$_GET['edit']]);
            $edit = $stmt->fetch();
        }
        ?>
        <form method="post">
            <?php if($edit): ?>
                <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <input type="hidden" name="update" value="1">
            <?php else: ?>
                <input type="hidden" name="add" value="1">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block mb-2 font-medium">按钮名称（显示在键盘上）</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($edit['button_name'] ?? '') ?>" 
                           class="w-full p-4 border rounded-2xl" required>
                </div>
                <div>
                    <label class="block mb-2 font-medium">指令标识（建议和按钮名称一致）</label>
                    <input type="text" name="command" value="<?= htmlspecialchars($edit['command'] ?? '') ?>" 
                           class="w-full p-4 border rounded-2xl" required>
                </div>
            </div>

            <div class="mt-6">
                <label class="block mb-2 font-medium">图片URL (可选)</label>
                <input type="url" name="photo_url" value="<?= htmlspecialchars($edit['photo_url'] ?? '') ?>" 
                       class="w-full p-4 border rounded-2xl">
            </div>

            <div class="mt-6">
                <label class="block mb-2 font-medium">回复内容</label>
                <textarea name="content" rows="5" class="w-full p-4 border rounded-2xl"><?= htmlspecialchars($edit['reply_text'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block mb-2 font-medium">排序（数字越小越靠前）</label>
                    <input type="number" name="sort" value="<?= $edit['sort_order'] ?? 0 ?>" 
                           class="w-full p-4 border rounded-2xl">
                </div>
                <div class="flex items-center pt-8">
                    <input type="checkbox" name="enabled" <?= ($edit && $edit['is_active']) || !$edit ? 'checked' : '' ?>>
                    <span class="ml-3 text-lg">启用该按钮</span>
                </div>
            </div>

            <div class="flex gap-4 mt-8">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-4 rounded-2xl text-lg font-medium">
                    <?= $edit ? '💾 保存修改' : '✅ 添加按钮' ?>
                </button>
                
                <a href="dashboard.php" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-10 py-4 rounded-2xl text-lg font-medium flex items-center">
                    ← 返回首页
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>