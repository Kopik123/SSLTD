-- 002_project_members.sql (mysql)
-- Adds project membership (required for field-app ACL) and a few indexes used by messaging/timesheets.

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

-- MySQL has no portable CREATE INDEX IF NOT EXISTS, so do it via information_schema.
SET @__idx_messages := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'messages'
    AND index_name = 'idx_messages_thread_created_at'
);
SET @__sql_messages := IF(
  @__idx_messages = 0,
  'CREATE INDEX `idx_messages_thread_created_at` ON `messages` (`thread_id`, `created_at`)',
  'SELECT 1'
);
PREPARE stmt_messages FROM @__sql_messages;
EXECUTE stmt_messages;
DEALLOCATE PREPARE stmt_messages;

SET @__idx_timesheets := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'timesheets'
    AND index_name = 'idx_timesheets_user_started_at'
);
SET @__sql_timesheets := IF(
  @__idx_timesheets = 0,
  'CREATE INDEX `idx_timesheets_user_started_at` ON `timesheets` (`user_id`, `started_at`)',
  'SELECT 1'
);
PREPARE stmt_timesheets FROM @__sql_timesheets;
EXECUTE stmt_timesheets;
DEALLOCATE PREPARE stmt_timesheets;
