<?php
$current = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!-- 顶部统一导航栏 -->
<nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-14">
            <!-- Logo -->
            <a href="dashboard.php" class="flex items-center gap-2 text-indigo-600 font-bold text-lg shrink-0">
                <i class="fas fa-robot"></i>
                <span class="hidden sm:inline">TG Bot 后台</span>
            </a>

            <!-- 导航链接 -->
            <div class="flex items-center gap-1 overflow-x-auto text-sm">
                <a href="dashboard.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'dashboard.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-home mr-1"></i>首页
                </a>
                <a href="members.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'members.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-users mr-1"></i>成员
                </a>
                <a href="broadcast.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'broadcast.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-paper-plane mr-1"></i>群发
                </a>
                <a href="menu.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'menu.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-bars mr-1"></i>菜单
                </a>
                <a href="webhook.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'webhook.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-cog mr-1"></i>Webhook
                </a>
                <a href="sync.php"
                   class="px-3 py-2 rounded-lg whitespace-nowrap <?= $current === 'sync.php' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                    <i class="fas fa-sync mr-1"></i>同步
                </a>
            </div>

            <!-- 退出 -->
            <a href="logout.php" class="px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 text-sm whitespace-nowrap shrink-0">
                <i class="fas fa-sign-out-alt mr-1"></i>退出
            </a>
        </div>
    </div>
</nav>