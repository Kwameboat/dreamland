-- Admin broadcast notifications (Dreamland → Broadcast Notifications)

CREATE TABLE IF NOT EXISTS broadcast_notification (
  id SERIAL PRIMARY KEY,
  title TEXT,
  message_body TEXT,
  audience_type VARCHAR(32) NOT NULL DEFAULT 'custom',
  total_user INTEGER NOT NULL DEFAULT 0,
  created_at INTEGER,
  created_by INTEGER
);

CREATE INDEX IF NOT EXISTS idx_broadcast_notification_created ON broadcast_notification (created_at DESC);

CREATE TABLE IF NOT EXISTS broadcast_notification_user (
  id SERIAL PRIMARY KEY,
  broadcast_notification_id INTEGER,
  user_id INTEGER
);

CREATE INDEX IF NOT EXISTS idx_broadcast_notification_user_broadcast ON broadcast_notification_user (broadcast_notification_id);
CREATE INDEX IF NOT EXISTS idx_broadcast_notification_user_user ON broadcast_notification_user (user_id);
