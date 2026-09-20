CREATE DATABASE IF NOT EXISTS cloudops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cloudops;

CREATE TABLE IF NOT EXISTS incidents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    affected_service VARCHAR(120) NOT NULL,
    severity ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL,
    status ENUM('OPEN','INVESTIGATING','RESOLVED') NOT NULL DEFAULT 'OPEN',
    description TEXT NOT NULL,
    resolution TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    INDEX idx_status_created (status, created_at),
    INDEX idx_severity_created (severity, created_at)
) ENGINE=InnoDB;

-- For a manual Kali installation, execute this as MariaDB root after choosing a
-- strong password. Docker Compose creates the same limited user via MARIADB_USER.
-- CREATE USER 'cloudops_app'@'localhost' IDENTIFIED BY 'REPLACE_WITH_SECRET';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON cloudops.* TO 'cloudops_app'@'localhost';
