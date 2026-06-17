-- Admin dashboard support widget

CREATE TABLE IF NOT EXISTS support_request (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL DEFAULT 0,
  name VARCHAR(256),
  email VARCHAR(200),
  phone VARCHAR(100),
  request_message TEXT,
  reply_message TEXT,
  is_reply INTEGER NOT NULL DEFAULT 0,
  status INTEGER NOT NULL DEFAULT 10,
  created_at INTEGER,
  created_by INTEGER,
  updated_at INTEGER,
  updated_by INTEGER
);
