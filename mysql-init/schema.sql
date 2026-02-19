-- Base de datos para la API de contraseñas
CREATE DATABASE IF NOT EXISTS password_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE password_api;

-- Tabla de historial de generación (opcional, para auditoría)
CREATE TABLE IF NOT EXISTS password_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    generated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    length        TINYINT      NOT NULL,
    has_upper     TINYINT(1)   NOT NULL DEFAULT 1,
    has_lower     TINYINT(1)   NOT NULL DEFAULT 1,
    has_digits    TINYINT(1)   NOT NULL DEFAULT 1,
    has_symbols   TINYINT(1)   NOT NULL DEFAULT 0,
    avoid_ambiguous TINYINT(1) NOT NULL DEFAULT 1,
    count_generated SMALLINT   NOT NULL DEFAULT 1,
    ip_address    VARCHAR(45)  NULL,    -- para rate-limiting futuro
    INDEX idx_generated_at (generated_at),
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB;

-- Tabla de validaciones (opcional)
CREATE TABLE IF NOT EXISTS validation_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    validated_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    password_length TINYINT   NOT NULL,
    score         TINYINT     NOT NULL,
    strength      VARCHAR(10) NOT NULL,
    passed        TINYINT(1)  NOT NULL,
    ip_address    VARCHAR(45) NULL,
    INDEX idx_validated_at (validated_at)
) ENGINE=InnoDB;