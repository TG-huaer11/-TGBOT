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
            $error = "❌ 新增失败";
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>底部菜单管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .menu-card { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        .action-btn {
            transition: all 0.2s;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="max-w-7xl mx-auto p-6">

    <!-- 头部导航 -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">底部菜单管理</h1>
            <p class="text-gray-500 mt-1">共 <strong class="text-indigo-600"><?= count($menus) ?></strong> 个菜单按钮</p>
        </div>
        
        <div class="flex gap-3">
            <!-- 添加新菜单 -->
            <a href="menu.php" 
               class="action-btn flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-medium shadow-sm">
                <i class="fas fa-plus"></i> 添加新菜单
            </a>
            
            <!-- 返回后台 -->
            <a href="dashboard.php" 
               class="action-btn flex items-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 rounded-2xl font-medium shadow-sm text-gray-700 hover:text-gray-900">
                <i class="fas fa-arrow-left"></i> 返回后台
            </a>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-2xl mb-6"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-6"><?= $error ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">

        <!-- 左侧：当前菜单列表 -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-3xl shadow h-full">
                <div class="px-8 py-5 bg-gray-50 border-b rounded-t-3xl">
                    <h2 class="text-xl font-semibold">当前菜单按钮</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach($menus as $m): ?>
                    <div class="menu-card px-8 py-6 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 flex items-center justify-center text-3xl bg-gradient-to-br from-indigo-100 to-blue-100 rounded-2xl">
                                <?= htmlspecialchars($m['emoji'] ?? '🔘') ?>
                            </div>
                            <div>
                                <div class="font-semibold text-lg"><?= htmlspecialchars($m['button_name']) ?></div>
                                <div class="text-sm text-gray-500 font-mono"><?= htmlspecialchars($m['command']) ?></div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <a href="?edit=<?= $m['id'] ?>" 
                               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-medium flex items-center gap-2 transition">
                                <i class="fas fa-edit"></i> 编辑
                            </a>
                            <a href="?delete=<?= $m['id'] ?>" 
                               onclick="return confirm('确定删除此按钮吗？')"
                               class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-2xl text-sm font-medium flex items-center gap-2 transition">
                                <i class="fas fa-trash"></i> 删除
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 右侧：添加/编辑表单 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-3xl shadow p-8 sticky top-6">
                <!-- 表单内容保持不变 -->
                <h2 class="text-2xl font-bold mb-8 text-gray-800">
                    <?= isset($_GET['edit']) ? '编辑菜单按钮' : '添加新菜单按钮' ?>
                </h2>

                <?php 
                $edit = null;
                if (isset($_GET['edit'])) {
                    $stmt = $pdo->prepare("SELECT * FROM menu_buttons WHERE id = ?");
                    $stmt->execute([(int)$_GET['edit']]);
                    $edit = $stmt->fetch();
                }
                ?>

                <form method="post" class="space-y-6">
                    <?php if($edit): ?>
                        <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                        <input type="hidden" name="update" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add" value="1">
                    <?php endif; ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">按钮名称</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($edit['button_name'] ?? '') ?>" 
                               class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">指令标识</label>
                        <input type="text" name="command" value="<?= htmlspecialchars($edit['command'] ?? '') ?>" 
                               class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">图片链接（可选）</label>
                        <input type="url" name="photo_url" value="<?= htmlspecialchars($edit['photo_url'] ?? '') ?>" 
                               class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">回复内容</label>
                        <textarea name="content" rows="5" 
                                  class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($edit['reply_text'] ?? '') ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">排序序号</label>
                            <input type="number" name="sort" value="<?= $edit['sort_order'] ?? 0 ?>" 
                                   class="w-full px-5 py-4 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center gap-3 pt-8">
                            <input type="checkbox" id="enabled" name="enabled" <?= ($edit && $edit['is_active']) || !$edit ? 'checked' : '' ?> class="w-5 h-5">
                            <label for="enabled" class="text-gray-700 font-medium">启用该按钮</label>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-6">
                        <button type="submit" 
                                class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-2xl transition">
                            <?= $edit ? '💾 保存修改' : '✅ 添加按钮' ?>
                        </button>
                        <?php if($edit): ?>
                        <a href="menu.php" class="flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-2xl text-center transition">取消</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>