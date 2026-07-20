<?php
// ====================== 配置 ======================
define('BOT_TOKEN', '8927749922:AAFrBvqm6y_rTZWPA_fXJ4yQkGwqNeWdByI');           
define('BOT_NAME', 'huahuahuahuahuabot');         

$DB = [
    'host'     => 'localhost',
    'dbname'   => 'telegram_bot',
    'user'     => 'telegram_bot',     // 建议用这个用户
    'pass'     => '4L8zsypZeAAD2jFx', // 你设置的数据库密码
];

$ADMIN_USERNAME = 'root';
$ADMIN_PASSWORD = 'admin123';     // ← 明文密码

// PDO 连接
try {
    $pdo = new PDO("mysql:host={$DB['host']};dbname={$DB['dbname']};charset=utf8mb4", 
                   $DB['user'], $DB['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("数据库连接失败: " . $e->getMessage());
}
// ====================== 菜单管理函数 ======================
// 获取底部菜单按钮
function getMenuButtons($pdo) {
    return $pdo->query("SELECT * FROM menu_buttons WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
