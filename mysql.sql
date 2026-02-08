-- mysql.sql
-- MySQL/MariaDB schema for XAMPP (based on `database/migrations/001_init.sql`)
--
-- Notes:
-- - We store timestamps as ISO-8601 strings (VARCHAR) to match the current PHP code (gmdate('c')).
-- - JSON fields are stored as LONGTEXT for broad MySQL/MariaDB compatibility.

CREATE DATABASE IF NOT EXISTS `ss_ltd`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ss_ltd`;

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `applied_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `idx_quote_requests_created_at` (`created_at`),
  KEY `idx_quote_requests_assigned_pm` (`assigned_pm_user_id`),
  KEY `idx_quote_requests_client_user` (`client_user_id`),
  CONSTRAINT `fk_quote_requests_client_user_id`
    FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_quote_requests_assigned_pm_user_id`
    FOREIGN KEY (`assigned_pm_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `idx_projects_created_at` (`created_at`),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role` VARCHAR(32) NOT NULL DEFAULT 'member',
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_members_project_user` (`project_id`, `user_id`),
  KEY `idx_project_members_user_id` (`user_id`),
  CONSTRAINT `fk_project_members_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_members_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklists` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_request_id` BIGINT UNSIGNED NULL,
  `project_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `title` VARCHAR(255) NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `submitted_at` VARCHAR(32) NULL,
  `decided_at` VARCHAR(32) NULL,
  `decided_by_user_id` BIGINT UNSIGNED NULL,
  `decision_note` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checklists_quote_request_id` (`quote_request_id`),
  KEY `idx_checklists_project_id` (`project_id`),
  KEY `idx_checklists_status` (`status`),
  CONSTRAINT `fk_checklists_quote_request_id`
    FOREIGN KEY (`quote_request_id`) REFERENCES `quote_requests` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_checklists_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_checklists_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_checklists_decided_by_user_id`
    FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `checklist_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `checklist_id` BIGINT UNSIGNED NOT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `title` VARCHAR(512) NOT NULL,
  `pricing_mode` VARCHAR(16) NOT NULL DEFAULT 'fixed',
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit_cost_cents` BIGINT NOT NULL DEFAULT 0,
  `fixed_cost_cents` BIGINT NOT NULL DEFAULT 0,
  `status` VARCHAR(16) NOT NULL DEFAULT 'todo',
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checklist_items_checklist_id` (`checklist_id`),
  KEY `idx_checklist_items_status` (`status`),
  CONSTRAINT `fk_checklist_items_checklist_id`
    FOREIGN KEY (`checklist_id`) REFERENCES `checklists` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_proposals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'submitted',
  `starts_at` VARCHAR(32) NOT NULL,
  `ends_at` VARCHAR(32) NOT NULL,
  `note` LONGTEXT NULL,
  `decision_note` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `decided_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `decided_at` VARCHAR(32) NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_proposals_project_id` (`project_id`),
  KEY `idx_schedule_proposals_status` (`status`),
  KEY `idx_schedule_proposals_created_at` (`created_at`),
  CONSTRAINT `fk_schedule_proposals_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_schedule_proposals_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_schedule_proposals_decided_by_user_id`
    FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schedule_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `starts_at` VARCHAR(32) NOT NULL,
  `ends_at` VARCHAR(32) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'approved',
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_events_project_id` (`project_id`),
  KEY `idx_schedule_events_starts_at` (`starts_at`),
  CONSTRAINT `fk_schedule_events_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_schedule_events_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `name` VARCHAR(255) NOT NULL,
  `unit` VARCHAR(32) NOT NULL DEFAULT 'unit',
  `unit_cost_cents` BIGINT NOT NULL DEFAULT 0,
  `vendor` VARCHAR(255) NULL,
  `sku` VARCHAR(255) NULL,
  `notes` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_materials_status` (`status`),
  KEY `idx_materials_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `name` VARCHAR(255) NOT NULL,
  `serial` VARCHAR(255) NULL,
  `location` VARCHAR(255) NULL,
  `notes` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tools_status` (`status`),
  KEY `idx_tools_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_materials` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'required',
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `needed_by` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_materials_project_id` (`project_id`),
  KEY `idx_project_materials_status` (`status`),
  CONSTRAINT `fk_project_materials_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_materials_material_id`
    FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_project_materials_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_tools` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `tool_id` BIGINT UNSIGNED NOT NULL,
  `assigned_to_user_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'required',
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_tools_project_id` (`project_id`),
  KEY `idx_project_tools_status` (`status`),
  CONSTRAINT `fk_project_tools_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_tools_tool_id`
    FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_project_tools_assigned_to_user_id`
    FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_project_tools_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `material_id` BIGINT UNSIGNED NOT NULL,
  `qty` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `expected_at` VARCHAR(32) NULL,
  `delivered_at` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_deliveries_project_id` (`project_id`),
  KEY `idx_deliveries_status` (`status`),
  KEY `idx_deliveries_expected_at` (`expected_at`),
  CONSTRAINT `fk_deliveries_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_deliveries_material_id`
    FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_deliveries_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subcontractors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subcontractors_user_id` (`user_id`),
  CONSTRAINT `fk_subcontractors_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subcontractor_workers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subcontractor_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subcontractor_workers_sub_user` (`subcontractor_id`, `user_id`),
  KEY `idx_subcontractor_workers_user_id` (`user_id`),
  CONSTRAINT `fk_subcontractor_workers_subcontractor_id`
    FOREIGN KEY (`subcontractor_id`) REFERENCES `subcontractors` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_subcontractor_workers_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `idx_uploads_owner_created_at` (`owner_type`, `owner_id`, `created_at`),
  KEY `idx_uploads_uploaded_by` (`uploaded_by_user_id`),
  CONSTRAINT `fk_uploads_uploaded_by_user_id`
    FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `threads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope_type` VARCHAR(64) NOT NULL,
  `scope_id` BIGINT UNSIGNED NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_threads_scope` (`scope_type`, `scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `thread_reads` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `last_read_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_thread_reads_thread_user` (`thread_id`, `user_id`),
  KEY `idx_thread_reads_user_id` (`user_id`),
  CONSTRAINT `fk_thread_reads_thread_id`
    FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_thread_reads_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `thread_id` BIGINT UNSIGNED NOT NULL,
  `sender_user_id` BIGINT UNSIGNED NULL,
  `body` LONGTEXT NOT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_thread_id` (`thread_id`),
  KEY `idx_messages_thread_created_at` (`thread_id`, `created_at`),
  KEY `idx_messages_sender_user_id` (`sender_user_id`),
  CONSTRAINT `fk_messages_thread_id`
    FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender_user_id`
    FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timesheets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NULL,
  `started_at` VARCHAR(32) NOT NULL,
  `stopped_at` VARCHAR(32) NULL,
  `notes` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timesheets_user_id` (`user_id`),
  KEY `idx_timesheets_user_started_at` (`user_id`, `started_at`),
  KEY `idx_timesheets_project_id` (`project_id`),
  CONSTRAINT `fk_timesheets_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_timesheets_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(64) NOT NULL,
  `entity_id` BIGINT UNSIGNED NULL,
  `meta_json` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_log_created_at` (`created_at`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_actor_user_id` (`actor_user_id`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_audit_log_actor_user_id`
    FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `body` LONGTEXT NOT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `created_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_reports_project_id` (`project_id`),
  KEY `idx_project_reports_created_at` (`created_at`),
  CONSTRAINT `fk_project_reports_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_project_reports_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `issues` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'open',
  `severity` VARCHAR(16) NOT NULL DEFAULT 'medium',
  `title` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NULL,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `assigned_to_user_id` BIGINT UNSIGNED NULL,
  `resolved_at` VARCHAR(32) NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_issues_project_id` (`project_id`),
  KEY `idx_issues_status` (`status`),
  KEY `idx_issues_created_at` (`created_at`),
  CONSTRAINT `fk_issues_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_issues_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_issues_assigned_to_user_id`
    FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `change_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `title` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NULL,
  `cost_delta_cents` BIGINT NOT NULL DEFAULT 0,
  `schedule_delta_days` INT NOT NULL DEFAULT 0,
  `created_by_user_id` BIGINT UNSIGNED NULL,
  `submitted_at` VARCHAR(32) NULL,
  `decided_by_user_id` BIGINT UNSIGNED NULL,
  `decided_at` VARCHAR(32) NULL,
  `decision_note` LONGTEXT NULL,
  `created_at` VARCHAR(32) NOT NULL,
  `updated_at` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_change_requests_project_id` (`project_id`),
  KEY `idx_change_requests_status` (`status`),
  KEY `idx_change_requests_created_at` (`created_at`),
  CONSTRAINT `fk_change_requests_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_change_requests_created_by_user_id`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_change_requests_decided_by_user_id`
    FOREIGN KEY (`decided_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
