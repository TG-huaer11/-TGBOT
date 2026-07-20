
USE telegram_bot;



-- 1. 如果表不存在则创建

CREATE TABLE IF NOT EXISTS menu_buttons (

    id INT AUTO_INCREMENT PRIMARY KEY,

    button_name VARCHAR(50) NOT NULL,

    command VARCHAR(100) NOT NULL,

    reply_text TEXT,

    sort_order INT DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- 2. 手动检查并添加新字段

DELIMITER //



DROP PROCEDURE IF EXISTS AddMenuColumns;

CREATE PROCEDURE AddMenuColumns()

BEGIN

    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'reply_type') THEN

        ALTER TABLE menu_buttons ADD COLUMN reply_type ENUM('text', 'photo') DEFAULT 'text';

    END IF;



    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'photo_url') THEN

        ALTER TABLE menu_buttons ADD COLUMN photo_url VARCHAR(500) NULL;

    END IF;



    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 

                   WHERE TABLE_SCHEMA = 'telegram_bot' 

                   AND TABLE_NAME = 'menu_buttons' 

                   AND COLUMN_NAME = 'inline_buttons') THEN

        ALTER TABLE menu_buttons ADD COLUMN inline_buttons JSON NULL;

    END IF;

END //



DELIMITER ;



CALL AddMenuColumns();



-- 3. 转换编码

ALTER TABLE menu_buttons CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



-- 4. 显示结果

SELECT '✅ 表升级完成！' AS 状态;

SELECT COUNT(*) AS 总记录数 FROM menu_buttons;

SELECT id, button_name, command, reply_type, photo_url FROM menu_buttons LIMIT 8;

