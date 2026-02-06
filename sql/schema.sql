-- SQL schema for WhatsApp-style chat application

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    fingerprint CHAR(64) NOT NULL UNIQUE,
    is_blocked TINYINT(1) DEFAULT 0,
    is_muted TINYINT(1) DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    last_typing TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    status ENUM('online','offline','busy') DEFAULT 'online',
    working_hours VARCHAR(120) DEFAULT '09:00-18:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    sender_role ENUM('user','admin') NOT NULL,
    receiver_role ENUM('user','admin') NOT NULL,
    message TEXT,
    type ENUM('text','image','video','audio','document') DEFAULT 'text',
    media_url VARCHAR(255) DEFAULT NULL,
    is_seen TINYINT(1) DEFAULT 0,
    status ENUM('sent','delivered','seen') DEFAULT 'sent',
    reply_to INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auto_reply TEXT DEFAULT NULL,
    working_hours VARCHAR(120) DEFAULT '09:00-18:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quick_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password_hash, name, status)
VALUES ('admin', '$2y$12$JHSaJ2/CPCDbdc11ikpZL.CKZe2P.A7vuo9dwmDWm2DQBVgDcnv4m', 'Admin', 'online')
ON DUPLICATE KEY UPDATE username = username;
