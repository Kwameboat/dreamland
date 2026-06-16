-- Feed/search support tables missing from core schema (SayHi social graph)

CREATE TABLE IF NOT EXISTS hash_tag (
  id SERIAL PRIMARY KEY,
  post_id INTEGER NOT NULL,
  hashtag VARCHAR(256) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_hash_tag_post_id ON hash_tag (post_id);
CREATE INDEX IF NOT EXISTS idx_hash_tag_hashtag ON hash_tag (hashtag);

CREATE TABLE IF NOT EXISTS collaborate (
  id SERIAL PRIMARY KEY,
  reference_id INTEGER NOT NULL,
  type INTEGER NOT NULL DEFAULT 1,
  collaborator_id INTEGER NOT NULL,
  author_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_collaborate_reference ON collaborate (reference_id, type);
CREATE INDEX IF NOT EXISTS idx_collaborate_collaborator ON collaborate (collaborator_id);

CREATE TABLE IF NOT EXISTS blocked_user (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  blocked_user_id INTEGER NOT NULL,
  created_at INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_blocked_user_user ON blocked_user (user_id);
CREATE INDEX IF NOT EXISTS idx_blocked_user_blocked ON blocked_user (blocked_user_id);

CREATE TABLE IF NOT EXISTS pin (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  reference_id INTEGER NOT NULL,
  type INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_pin_reference ON pin (reference_id, type);

CREATE TABLE IF NOT EXISTS user_favorite (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  reference_id INTEGER NOT NULL,
  type INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_user_favorite_user ON user_favorite (user_id);
