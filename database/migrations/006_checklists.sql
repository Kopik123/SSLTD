-- Add checklists + checklist_items for quote requests and projects (SQLite)

CREATE TABLE IF NOT EXISTS checklists (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  quote_request_id INTEGER NULL,
  project_id INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  title TEXT NULL,
  created_by_user_id INTEGER NULL,
  submitted_at TEXT NULL,
  decided_at TEXT NULL,
  decided_by_user_id INTEGER NULL,
  decision_note TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (quote_request_id) REFERENCES quote_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (decided_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_checklists_quote_request_id ON checklists(quote_request_id);
CREATE INDEX IF NOT EXISTS idx_checklists_project_id ON checklists(project_id);
CREATE INDEX IF NOT EXISTS idx_checklists_status ON checklists(status);

CREATE TABLE IF NOT EXISTS checklist_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  checklist_id INTEGER NOT NULL,
  position INTEGER NOT NULL DEFAULT 0,
  title TEXT NOT NULL,
  pricing_mode TEXT NOT NULL DEFAULT 'fixed',
  qty REAL NOT NULL DEFAULT 0,
  unit_cost_cents INTEGER NOT NULL DEFAULT 0,
  fixed_cost_cents INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'todo',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (checklist_id) REFERENCES checklists(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_checklist_items_checklist_id ON checklist_items(checklist_id);
CREATE INDEX IF NOT EXISTS idx_checklist_items_status ON checklist_items(status);

