-- 底部菜单按钮管理表
CREATE TABLE IF NOT EXISTS menu_buttons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    button_name VARCHAR(50) NOT NULL COMMENT '按钮显示名称',
    command VARCHAR(100) NOT NULL COMMENT '对应指令/标识',
    sort_order INT DEFAULT 0 COMMENT '排序',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入默认菜单（和你图片一致）
INSERT INTO menu_buttons (button_name, command, sort_order) VALUES 
('每天5次免费能量', 'free_energy', 1),
('U换TRX', 'u_to_trx', 2),
('TRX能量', 'trx_energy', 3),
('实时U价', 'price', 4),
('监控地址', 'monitor', 5),
('查U统计U', 'statistics', 6),
('联系客服', 'contact', 7);