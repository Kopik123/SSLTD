-- Change Requests (SQLite)

CREATE TABLE IF NOT EXISTS change_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft', -- draft|submitted|approved|rejected|cancelled|implemented
  title TEXT NOT NULL,
  body TEXT NULL,
  cost_delta_cents INTEGER NOT NULL DEFAULT 0,
  schedule_delta_days INTEGER NOT NULL DEFAULT 0,
  created_by_user_id INTEGER NULL,
  submitted_at TEXT NULL,
  decided_by_user_id INTEGER NULL,
  decided_at TEXT NULL,
  decision_note TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (decided_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_change_requests_project_id ON change_requests(project_id);
CREATE INDEX IF NOT EXISTS idx_change_requests_status ON change_requests(status);
CREATE INDEX IF NOT EXISTS idx_change_requests_created_at ON change_requests(created_at);

