-- 005_thread_reads.sql (sqlite)
-- Unread tracking for threads (web MVP).

CREATE TABLE IF NOT EXISTS thread_reads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  thread_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  last_read_at TEXT NOT NULL,
  UNIQUE(thread_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_thread_reads_user_id ON thread_reads (user_id);

