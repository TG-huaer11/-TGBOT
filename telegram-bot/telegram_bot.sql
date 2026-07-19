CREATE DATABASE IF NOT EXISTS telegram_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telegram_bot;

-- 成员表
CREATE TABLE IF NOT EXISTS users (
    id BIGINT PRIMARY KEY COMMENT 'Telegram User ID',
    username VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    is_blocked TINYINT(1) DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP NULL
);

-- 群发记录
CREATE TABLE IF NOT EXISTS broadcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    keyboard JSON NULL,
    sent_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 管理员表
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 插入一个管理员（用户名: admin，密码: admin123）
INSERT INTO admins (username, password) VALUES 
('root', 'admin123'); -- password_hash('admin123')