-- Schedule proposals + events (MySQL/MariaDB)

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
