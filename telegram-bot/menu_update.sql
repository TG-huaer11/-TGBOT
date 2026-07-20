cd /www/wwwroot/mcy-shop-main/telegram-bot

cat > menu_update.sql << 'EOF'
USE telegram_bot;

DROP TABLE IF EXISTS menu_buttons;

CREATE TABLE menu_buttons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    button_name VARCHAR(50) NOT NULL,
    command VARCHAR(100) NOT NULL,
    reply_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    reply_type ENUM('text', 'photo') DEFAULT 'text',
    photo_url VARCHAR(500) NULL,
    inline_buttons JSON NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menu_buttons (button_name, command, reply_text, sort_order) VALUES 
('每天5次免费能量', 'free_energy', '✅ 已为您领取今日免费能量！\n剩余次数：5次', 1),
('U换TRX', 'u_to_trx', '💱 请发送您要兑换的 U 数量：', 2),
('TRX能量', 'trx_energy', '⚡ 请发送您的 TRON 地址查询能量：', 3),
('实时U价', 'price', '🔥 当前 USDT-TRX 实时价格：\n1 U = 2.924 TRX\n100U = 292.487 TRX', 4),
('监控地址', 'monitor', '👀 请发送要监控的 TRON 地址：', 5),
('查U统计U', 'statistics', '📊 请输入要查询的地址：', 6),
('联系客服', 'contact', '🧑‍💼 客服：@你的客服用户名\n工作时间 9:00-24:00', 7);

SELECT '菜单表升级完成！' AS message;
SELECT * FROM menu_buttons;
EOF