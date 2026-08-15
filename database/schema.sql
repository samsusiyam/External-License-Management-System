-- ================================================================
-- ELMS - External License Management System
-- Database schema (MySQL / MariaDB, utf8mb4)
-- ================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------
-- admin_users : panel login accounts (session auth)
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(100) NOT NULL,
    `email`          VARCHAR(190) NOT NULL,
    `username`       VARCHAR(60)  NOT NULL,
    `password_hash`  VARCHAR(255) NOT NULL,
    `role`           ENUM('admin','manager') NOT NULL DEFAULT 'admin',
    `status`         ENUM('active','disabled') NOT NULL DEFAULT 'active',
    `last_login_at`  DATETIME NULL DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_username` (`username`),
    UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- products : software products that issue licenses
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_name`   VARCHAR(150) NOT NULL,
    `product_key`    VARCHAR(80)  NOT NULL,
    `description`    TEXT NULL,
    `latest_version` VARCHAR(30)  NULL,
    `download_url`   VARCHAR(255) NULL,
    `update_notes`   TEXT NULL,
    `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_key` (`product_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- licenses : issued licenses
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `license_key`      VARCHAR(64)  NOT NULL,
    `product_id`       INT UNSIGNED NOT NULL,
    `customer_name`    VARCHAR(150) NULL,
    `customer_email`   VARCHAR(190) NULL,
    `whmcs_service_id` INT UNSIGNED NULL,
    `domain`           VARCHAR(190) NULL,
    `ip_address`       VARCHAR(45)  NULL,
    `install_path`     VARCHAR(255) NULL,
    `activation_limit` INT UNSIGNED NOT NULL DEFAULT 1,
    `activation_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `domain_lock`      TINYINT(1) NOT NULL DEFAULT 0,
    `ip_lock`          TINYINT(1) NOT NULL DEFAULT 0,
    `expiry_date`      DATE NULL,
    `status`           ENUM('active','suspended','expired','terminated') NOT NULL DEFAULT 'active',
    `notes`            TEXT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_license_key` (`license_key`),
    KEY `idx_license_product` (`product_id`),
    KEY `idx_license_status` (`status`),
    KEY `idx_license_expiry` (`expiry_date`),
    KEY `idx_license_whmcs` (`whmcs_service_id`),
    CONSTRAINT `fk_license_product` FOREIGN KEY (`product_id`)
        REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- activations : per-install activation records
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activations` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `license_id`      INT UNSIGNED NOT NULL,
    `domain`          VARCHAR(190) NULL,
    `ip`              VARCHAR(45)  NULL,
    `server_hostname` VARCHAR(190) NULL,
    `install_path`    VARCHAR(255) NULL,
    `status`          ENUM('active','deactivated') NOT NULL DEFAULT 'active',
    `activated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_check`      DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_activation_license` (`license_id`),
    KEY `idx_activation_status` (`status`),
    CONSTRAINT `fk_activation_license` FOREIGN KEY (`license_id`)
        REFERENCES `licenses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- api_keys : credentials for machine-to-machine API access
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(120) NOT NULL,
    `api_key`      VARCHAR(64)  NOT NULL,
    `secret_key`   VARCHAR(128) NOT NULL,
    `status`       ENUM('active','revoked') NOT NULL DEFAULT 'active',
    `last_used_at` DATETIME NULL DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- audit_logs : high-level activity trail
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_type`  ENUM('admin','api','system') NOT NULL DEFAULT 'system',
    `actor_id`    VARCHAR(120) NULL,
    `action`      VARCHAR(120) NOT NULL,
    `entity_type` VARCHAR(60)  NULL,
    `entity_id`   VARCHAR(60)  NULL,
    `details`     TEXT NULL,
    `ip`          VARCHAR(45)  NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- api_logs : raw API request/response log
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `endpoint`      VARCHAR(190) NOT NULL,
    `method`        VARCHAR(10)  NOT NULL,
    `api_key_id`    INT UNSIGNED NULL,
    `request_body`  MEDIUMTEXT NULL,
    `response_body` MEDIUMTEXT NULL,
    `status_code`   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `success`       TINYINT(1) NOT NULL DEFAULT 0,
    `ip`            VARCHAR(45)  NULL,
    `duration_ms`   INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_apilog_endpoint` (`endpoint`),
    KEY `idx_apilog_success` (`success`),
    KEY `idx_apilog_created` (`created_at`),
    KEY `idx_apilog_key` (`api_key_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- rate_limits : sliding-window request counters
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier`    VARCHAR(120) NOT NULL,
    `window_start`  INT UNSIGNED NOT NULL,
    `request_count` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rate_identifier_window` (`identifier`, `window_start`),
    KEY `idx_rate_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
