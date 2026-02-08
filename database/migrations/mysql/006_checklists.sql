-- Add checklists + checklist_items for quote requests and projects (MySQL/MariaDB)

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

