-- Inventory: materials, tools, allocations, deliveries (SQLite)

CREATE TABLE IF NOT EXISTS materials (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  status TEXT NOT NULL DEFAULT 'active', -- active|inactive
  name TEXT NOT NULL,
  unit TEXT NOT NULL DEFAULT 'unit',
  unit_cost_cents INTEGER NOT NULL DEFAULT 0,
  vendor TEXT NULL,
  sku TEXT NULL,
  notes TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_materials_status ON materials(status);
CREATE INDEX IF NOT EXISTS idx_materials_name ON materials(name);

CREATE TABLE IF NOT EXISTS tools (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  status TEXT NOT NULL DEFAULT 'active', -- active|inactive
  name TEXT NOT NULL,
  serial TEXT NULL,
  location TEXT NULL,
  notes TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_tools_status ON tools(status);
CREATE INDEX IF NOT EXISTS idx_tools_name ON tools(name);

CREATE TABLE IF NOT EXISTS project_materials (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  material_id INTEGER NOT NULL,
  status TEXT NOT NULL DEFAULT 'required', -- required|ordered|delivered|cancelled
  qty REAL NOT NULL DEFAULT 0,
  needed_by TEXT NULL,
  notes TEXT NULL,
  created_by_user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_project_materials_project_id ON project_materials(project_id);
CREATE INDEX IF NOT EXISTS idx_project_materials_status ON project_materials(status);

CREATE TABLE IF NOT EXISTS project_tools (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  tool_id INTEGER NOT NULL,
  assigned_to_user_id INTEGER NULL,
  status TEXT NOT NULL DEFAULT 'required', -- required|assigned|on_site|in_storage|in_service|cancelled
  notes TEXT NULL,
  created_by_user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (tool_id) REFERENCES tools(id) ON DELETE RESTRICT,
  FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_project_tools_project_id ON project_tools(project_id);
CREATE INDEX IF NOT EXISTS idx_project_tools_status ON project_tools(status);

CREATE TABLE IF NOT EXISTS deliveries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL,
  material_id INTEGER NOT NULL,
  qty REAL NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'pending', -- pending|delivered|cancelled
  expected_at TEXT NULL,
  delivered_at TEXT NULL,
  notes TEXT NULL,
  created_by_user_id INTEGER NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_deliveries_project_id ON deliveries(project_id);
CREATE INDEX IF NOT EXISTS idx_deliveries_status ON deliveries(status);
CREATE INDEX IF NOT EXISTS idx_deliveries_expected_at ON deliveries(expected_at);

