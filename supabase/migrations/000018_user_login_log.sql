-- Login audit trail written on every successful API login (UserLoginLog model)

CREATE TABLE IF NOT EXISTS user_login_log (
  id SERIAL PRIMARY KEY,
  user_id INTEGER,
  login_mode INTEGER NOT NULL DEFAULT 1,
  device_type INTEGER NOT NULL DEFAULT 1,
  device_model VARCHAR(256),
  device_os_version VARCHAR(200),
  device_app_release_version VARCHAR(200),
  release_version VARCHAR(100),
  login_ip VARCHAR(100),
  login_location TEXT,
  created_at INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_user_login_log_user_id ON user_login_log (user_id);
