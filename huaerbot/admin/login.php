<?php
session_start();
require '../config.php';

if ($_POST) {
    if ($_POST['username'] === $ADMIN_USERNAME && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "账号或密码错误";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理后台登录</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <i class="fas fa-robot text-6xl text-indigo-600 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">管理后台</h1>
            <p class="text-gray-500 mt-2">Telegram Bot 控制中心</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" name="username" value="" 
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="password" value=""
                           class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 rounded-xl transition duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                立即登录
            </button>
        </form>
    </div>
</body>
</html>