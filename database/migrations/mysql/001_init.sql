-- 001_init.sql (MySQL/MariaDB)
--
-- Notes:
-- - We store timestamps as ISO-8601 strings (VARCHAR) to match current PHP code (gmdate('c')).
-- - JSON payloads are stored as LONGTEXT for broad compatibility.

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `applied_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(32) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(64) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `last_login_at` VARCHAR(32) NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `expires_at` VARCHAR(32) NOT NULL,
  `last_used_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_tokens_token_hash` (`token_hash`),
  KEY `idx_api_tokens_user_id` (`user_id`),
  KEY `idx_api_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_api_tokens_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `expires_at` VARCHAR(32) NOT NULL,
  `used_at` VARCHAR(32) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token_hash` (`token_hash`),
  KEY `idx_password_resets_user_id` (`user_id`),
  KEY `idx_password_resets_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_resets_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL,
  `client_user_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(64) NULL,
  `address` VARCHAR(512) NOT NULL,
  `scope_json` LONGTEXT NOT NULL,
  `description` LONGTEXT NULL,
  `preferred_dates_json` LONGTEXT NULL,
  `assigned_pm_user_id` BIGINT UNSIGNED NULL,
  `service_area_ok` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_quote_requests_status` (`status`),
  KEY `idx_quote_requests_assigned_pm` (`assigned_pm_user_id`),
  KEY `idx_quote_requests_client_user` (`client_user_id`),
  CONSTRAINT `fk_quote_requests_client_user_id`
    FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_quote_requests_assigned_pm_user_id`
    FOREIGN KEY (`assigned_pm_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL,
  `quote_request_id` BIGINT UNSIGNED NULL,
  `client_user_id` BIGINT UNSIGNED NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` VARCHAR(512) NOT NULL,
  `budget_cents` BIGINT NOT NULL DEFAULT 0,
  `assigned_pm_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_assigned_pm` (`assigned_pm_user_id`),
  KEY `idx_projects_quote_request_id` (`quote_request_id`),
  CONSTRAINT `fk_projects_quote_request_id`
    FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_projects_client_user_id`
    FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_projects_assigned_pm_user_id`
    FOREIGN KEY (`assigned_pm_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `uploads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_type` VARCHAR(64) NOT NULL,
  `owner_id` BIGINT UNSIGNED NOT NULL,
  `storage_path` VARCHAR(1024) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(128) NOT NULL,
  `size_bytes` BIGINT NOT NULL,
  `uploaded_by_user_id` BIGINT UNSIGNED NULL,
  `stage` VARCHAR(32) NULL,
  `client_visible` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploads_owner` (`owner_type`, `owner_id`),
  KEY `idx_uploads_uploaded_by` (`uploaded_by_user_id`),
  CONSTRAINT `fk_uploads_uploaded_by_user_id`
    FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `threads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_type` VARCHAR(64) NOT NULL,
  `scope_id` BIGINT UNSIGNED NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_threads_scope` (`scope_type`, `scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` BIGINT UNSIGNED NOT NULL,
  `sender_user_id` BIGINT UNSIGNED NULL,
  `body` LONGTEXT NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_thread_id` (`thread_id`),
  KEY `idx_messages_sender_user_id` (`sender_user_id`),
  CONSTRAINT `fk_messages_thread_id`
    FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender_user_id`
    FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timesheets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NULL,
  `started_at` VARCHAR(32) NOT NULL,
  `stopped_at` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timesheets_user_id` (`user_id`),
  KEY `idx_timesheets_project_id` (`project_id`),
  CONSTRAINT `fk_timesheets_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_timesheets_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `meta_json` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_actor_user_id` (`actor_user_id`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_audit_log_actor_user_id`
    FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

