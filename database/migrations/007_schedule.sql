-- Schedule proposals + events (SQLite)

CREATE TABLE IF NOT EXISTS schedule_proposals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'submitted', -- submitted|approved|rejected|cancelled
  starts_at TEXT NOT NULL,
  ends_at TEXT NOT NULL,
  note TEXT NULL,
  decision_note TEXT NULL,
  created_by_user_id INTEGER NULL,
  decided_by_user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  decided_at TEXT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (decided_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_schedule_proposals_project_id ON schedule_proposals(project_id);
CREATE INDEX IF NOT EXISTS idx_schedule_proposals_status ON schedule_proposals(status);
CREATE INDEX IF NOT EXISTS idx_schedule_proposals_created_at ON schedule_proposals(created_at);

CREATE TABLE IF NOT EXISTS schedule_events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  starts_at TEXT NOT NULL,
  ends_at TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'approved', -- approved|cancelled
  created_by_user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_schedule_events_project_id ON schedule_events(project_id);
CREATE INDEX IF NOT EXISTS idx_schedule_events_starts_at ON schedule_events(starts_at);
