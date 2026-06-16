-- Social / moderation tables required by API post serialization

CREATE TABLE IF NOT EXISTS reported_post (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  post_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_post_post ON reported_post (post_id);
CREATE INDEX IF NOT EXISTS idx_reported_post_user ON reported_post (user_id);

CREATE TABLE IF NOT EXISTS reported_post_comment (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  post_comment_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE TABLE IF NOT EXISTS mention_user (
  id SERIAL PRIMARY KEY,
  post_id INTEGER NOT NULL,
  user_id INTEGER NULL,
  username VARCHAR(256) NULL
);

CREATE INDEX IF NOT EXISTS idx_mention_user_post ON mention_user (post_id);
