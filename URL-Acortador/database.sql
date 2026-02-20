-- ============================================
-- API Acortador de URLs - Schema de Base de Datos
-- ============================================

CREATE DATABASE IF NOT EXISTS url_shortener CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE url_shortener;

-- Tabla principal de URLs acortadas
CREATE TABLE IF NOT EXISTS short_urls (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20) NOT NULL UNIQUE,
    original_url TEXT NOT NULL,
    creator_ip  VARCHAR(45) NOT NULL,
    max_uses    INT UNSIGNED DEFAULT NULL,       -- NULL = sin límite
    expires_at  DATETIME DEFAULT NULL,           -- NULL = nunca expira
    visit_count INT UNSIGNED DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Tabla de visitas/estadísticas
CREATE TABLE IF NOT EXISTS visits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url_id      INT UNSIGNED NOT NULL,
    visitor_ip  VARCHAR(45) NOT NULL,
    user_agent  VARCHAR(512) DEFAULT NULL,
    referer     VARCHAR(2048) DEFAULT NULL,
    visited_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (url_id) REFERENCES short_urls(id) ON DELETE CASCADE,
    INDEX idx_url_id (url_id),
    INDEX idx_visited_at (visited_at)
) ENGINE=InnoDB;

-- Tabla de rate limiting por IP
CREATE TABLE IF NOT EXISTS rate_limits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip          VARCHAR(45) NOT NULL,
    requests    INT UNSIGNED DEFAULT 1,
    window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ip (ip)
) ENGINE=InnoDB;
