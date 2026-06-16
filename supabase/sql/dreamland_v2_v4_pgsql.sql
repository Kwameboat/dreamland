-- Dreamland v2–v4 (PostgreSQL)

ALTER TABLE "user" ADD COLUMN IF NOT EXISTS dreamland_account_type VARCHAR(16) NOT NULL DEFAULT 'viewer';
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS dreamland_creator_status VARCHAR(16) NOT NULL DEFAULT 'none';

ALTER TABLE user_live_history ADD COLUMN IF NOT EXISTS live_title VARCHAR(255) NULL;
ALTER TABLE user_live_history ADD COLUMN IF NOT EXISTS is_monetized SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE user_live_history ADD COLUMN IF NOT EXISTS price_credits INTEGER NULL;
ALTER TABLE user_live_history ADD COLUMN IF NOT EXISTS total_comment INTEGER NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS purchased_lives (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  live_id INTEGER NOT NULL,
  credits_paid INTEGER NOT NULL DEFAULT 0,
  creator_credits INTEGER NOT NULL DEFAULT 0,
  platform_commission INTEGER NOT NULL DEFAULT 0,
  purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, live_id)
);
CREATE INDEX IF NOT EXISTS idx_purchased_lives_live ON purchased_lives (live_id);

CREATE TABLE IF NOT EXISTS post_watch_events (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  post_id INTEGER NOT NULL,
  watch_ms INTEGER NOT NULL DEFAULT 0,
  completion_pct SMALLINT NOT NULL DEFAULT 0,
  rewatched SMALLINT NOT NULL DEFAULT 0,
  created_at INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_pwe_user_created ON post_watch_events (user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_pwe_post_created ON post_watch_events (post_id, created_at);

CREATE TABLE IF NOT EXISTS web_push_subscription (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  endpoint TEXT NOT NULL,
  p256dh VARCHAR(255) NOT NULL,
  auth VARCHAR(255) NOT NULL,
  user_agent VARCHAR(512) NULL,
  is_active SMALLINT NOT NULL DEFAULT 1,
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_web_push_endpoint ON web_push_subscription (endpoint);

-- Rejection & appeals
ALTER TABLE post ADD COLUMN IF NOT EXISTS rejection_reason TEXT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS rejected_at BIGINT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS rejected_by INTEGER;
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_status VARCHAR(32);
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_message TEXT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_submitted_at BIGINT;
