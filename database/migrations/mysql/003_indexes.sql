-- 003_indexes.sql (mysql)
-- Add a few indexes used by list screens and pagination.

SET @__idx_qr_created := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'quote_requests'
    AND index_name = 'idx_quote_requests_created_at'
);
SET @__sql_qr_created := IF(
  @__idx_qr_created = 0,
  'CREATE INDEX `idx_quote_requests_created_at` ON `quote_requests` (`created_at`)',
  'SELECT 1'
);
PREPARE stmt_qr_created FROM @__sql_qr_created;
EXECUTE stmt_qr_created;
DEALLOCATE PREPARE stmt_qr_created;

SET @__idx_projects_created := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'projects'
    AND index_name = 'idx_projects_created_at'
);
SET @__sql_projects_created := IF(
  @__idx_projects_created = 0,
  'CREATE INDEX `idx_projects_created_at` ON `projects` (`created_at`)',
  'SELECT 1'
);
PREPARE stmt_projects_created FROM @__sql_projects_created;
EXECUTE stmt_projects_created;
DEALLOCATE PREPARE stmt_projects_created;

SET @__idx_uploads_owner_created := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'uploads'
    AND index_name = 'idx_uploads_owner_created_at'
);
SET @__sql_uploads_owner_created := IF(
  @__idx_uploads_owner_created = 0,
  'CREATE INDEX `idx_uploads_owner_created_at` ON `uploads` (`owner_type`, `owner_id`, `created_at`)',
  'SELECT 1'
);
PREPARE stmt_uploads_owner_created FROM @__sql_uploads_owner_created;
EXECUTE stmt_uploads_owner_created;
DEALLOCATE PREPARE stmt_uploads_owner_created;

SET @__idx_audit_created := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'audit_log'
    AND index_name = 'idx_audit_log_created_at'
);
SET @__sql_audit_created := IF(
  @__idx_audit_created = 0,
  'CREATE INDEX `idx_audit_log_created_at` ON `audit_log` (`created_at`)',
  'SELECT 1'
);
PREPARE stmt_audit_created FROM @__sql_audit_created;
EXECUTE stmt_audit_created;
DEALLOCATE PREPARE stmt_audit_created;

