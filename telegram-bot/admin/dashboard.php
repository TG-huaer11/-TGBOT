<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php"); exit;
}
require '../config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Telegram Bot 后台管理系统</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 { margin: 0; font-size: 28px; }
        .nav {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px 25px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .card h3 {
            margin: 15px 0 10px;
            color: #2c3e50;
            font-size: 22px;
        }
        .card p {
            color: #666;
            font-size: 15px;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        a { text-decoration: none; color: inherit; }
        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🚀 Telegram Bot 后台管理系统</h1>
            <a href="logout.php" style="color:white; background:#e74c3c; padding:10px 20px; border-radius:8px; text-decoration:none;">退出登录</a>
        </header>

        <div class="nav">
            
            <a href="members.php">
                <div class="card">
                    <div class="icon">👥</div>
                    <h3>成员管理</h3>
                    <p>查看和管理机器人用户</p>
                </div>
            </a>

            <a href="broadcast.php">
                <div class="card">
                    <div class="icon">📢</div>
                    <h3>群发消息</h3>
                    <p>向所有用户推送消息</p>
                </div>
            </a>

            <a href="menu.php">
                <div class="card">
                    <div class="icon">🌟</div>
                    <h3>底部菜单管理</h3>
                    <p>自定义按钮及回复内容</p>
                </div>
            </a>

            <a href="webhook.php">
                <div class="card">
                    <div class="icon">⚙️</div>
                    <h3>Webhook 设置</h3>
                    <p>管理机器人 Webhook</p>
                </div>
            </a>

        </div>

        <footer>
            Powered by TG Bot Management System © 2026
        </footer>
    </div>
</body>
</html>