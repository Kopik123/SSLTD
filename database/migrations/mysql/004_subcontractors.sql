-- 004_subcontractors.sql (mysql)
-- Adds subcontractor entities and worker mapping.

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

