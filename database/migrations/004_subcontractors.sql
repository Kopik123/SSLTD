-- 004_subcontractors.sql (sqlite)
-- Adds subcontractor entities and worker mapping.

CREATE TABLE IF NOT EXISTS subcontractors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  company_name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL,
  UNIQUE(user_id)
);

CREATE TABLE IF NOT EXISTS subcontractor_workers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  subcontractor_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL,
  UNIQUE(subcontractor_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_subcontractor_workers_user_id ON subcontractor_workers (user_id);

