-- Project progress reports + issues (MySQL/MariaDB)

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

