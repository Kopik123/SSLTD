-- 005_thread_reads.sql (mysql)
-- Unread tracking for threads (web MVP).

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

