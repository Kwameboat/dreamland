-- Admin moderation: reported users, ads, stories, highlights, content

CREATE TABLE IF NOT EXISTS reported_user (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  report_to_user_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_user_report_to ON reported_user (report_to_user_id);
CREATE INDEX IF NOT EXISTS idx_reported_user_user ON reported_user (user_id);
CREATE INDEX IF NOT EXISTS idx_reported_user_status ON reported_user (status);

CREATE TABLE IF NOT EXISTS reported_ad (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  ad_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_ad_ad ON reported_ad (ad_id);
CREATE INDEX IF NOT EXISTS idx_reported_ad_user ON reported_ad (user_id);

CREATE TABLE IF NOT EXISTS reported_story (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  story_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_story_story ON reported_story (story_id);
CREATE INDEX IF NOT EXISTS idx_reported_story_user ON reported_story (user_id);

CREATE TABLE IF NOT EXISTS reported_highlight (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  highlight_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_highlight_highlight ON reported_highlight (highlight_id);
CREATE INDEX IF NOT EXISTS idx_reported_highlight_user ON reported_highlight (user_id);

CREATE TABLE IF NOT EXISTS reported_content (
  id SERIAL PRIMARY KEY,
  type INTEGER NOT NULL DEFAULT 1,
  user_id INTEGER NOT NULL,
  reference_id INTEGER NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL DEFAULT 0,
  resolved_at INTEGER NULL
);

CREATE INDEX IF NOT EXISTS idx_reported_content_ref ON reported_content (reference_id);
CREATE INDEX IF NOT EXISTS idx_reported_content_user ON reported_content (user_id);
