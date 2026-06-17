-- User verification requests (admin: Users → User Verification)

CREATE TABLE IF NOT EXISTS user_verification (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  document_type VARCHAR(256),
  user_message TEXT,
  admin_message TEXT,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER NOT NULL DEFAULT 0,
  updated_at INTEGER,
  updated_by INTEGER
);

CREATE INDEX IF NOT EXISTS idx_user_verification_user ON user_verification (user_id);
CREATE INDEX IF NOT EXISTS idx_user_verification_status ON user_verification (status);

CREATE TABLE IF NOT EXISTS user_verification_document (
  id SERIAL PRIMARY KEY,
  user_verification_id INTEGER NOT NULL,
  title VARCHAR(256),
  filename VARCHAR(256),
  media_type SMALLINT NOT NULL DEFAULT 1,
  created_at INTEGER
);

CREATE INDEX IF NOT EXISTS idx_user_verification_document_parent ON user_verification_document (user_verification_id);
