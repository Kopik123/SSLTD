-- 003_indexes.sql (sqlite)
-- Add a few indexes used by list screens and pagination.

CREATE INDEX IF NOT EXISTS idx_quote_requests_created_at ON quote_requests (created_at);
CREATE INDEX IF NOT EXISTS idx_projects_created_at ON projects (created_at);
CREATE INDEX IF NOT EXISTS idx_uploads_owner_created_at ON uploads (owner_type, owner_id, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_log (created_at);

