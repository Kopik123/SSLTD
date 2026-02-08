-- Change Requests (MySQL/MariaDB)

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

