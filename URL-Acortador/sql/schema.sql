CREATE DATABASE IF NOT EXISTS urlshortener CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE urlshortener;

CREATE TABLE urls (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    short_code  VARCHAR(20) NOT NULL UNIQUE,
    original_url TEXT NOT NULL,
    creator_ip  VARCHAR(45),
    expires_at  DATETIME DEFAULT NULL,
    max_uses    INT UNSIGNED DEFAULT NULL,
    use_count   INT UNSIGNED DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_short_code (short_code),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE visits (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url_id      INT UNSIGNED NOT NULL,
    visited_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    visitor_ip  VARCHAR(45),
    user_agent  TEXT,
    FOREIGN KEY (url_id) REFERENCES urls(id) ON DELETE CASCADE,
    INDEX idx_url_id_date (url_id, visited_at)
) ENGINE=InnoDB;

-- Rate limiting table
CREATE TABLE rate_limits (
    ip          VARCHAR(45) NOT NULL,
    requests    INT UNSIGNED DEFAULT 1,
    window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ip)
) ENGINE=InnoDB;
