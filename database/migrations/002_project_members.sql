-- 002_project_members.sql (sqlite)
-- Adds project membership (required for field-app ACL) and a few indexes used by messaging/timesheets.

CREATE TABLE IF NOT EXISTS project_members (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  role TEXT NOT NULL DEFAULT 'member',
  created_at TEXT NOT NULL,
  UNIQUE(project_id, user_id),
  FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_project_members_user_id ON project_members(user_id);
CREATE INDEX IF NOT EXISTS idx_messages_thread_created_at ON messages(thread_id, created_at);
CREATE INDEX IF NOT EXISTS idx_timesheets_user_started_at ON timesheets(user_id, started_at);

