-- Dreamland v1 tables/columns (PostgreSQL)

CREATE TABLE IF NOT EXISTS credit_packages (
  id CHAR(36) NOT NULL PRIMARY KEY,
  credit_amount INTEGER NOT NULL,
  fiat_cost DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) NOT NULL DEFAULT 'GHS',
  is_active SMALLINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_credit_packages_active ON credit_packages (is_active);

INSERT INTO credit_packages (id, credit_amount, fiat_cost, currency, is_active) VALUES
(gen_random_uuid()::text, 50, 5.00, 'GHS', 1),
(gen_random_uuid()::text, 120, 10.00, 'GHS', 1),
(gen_random_uuid()::text, 300, 25.00, 'GHS', 1),
(gen_random_uuid()::text, 650, 50.00, 'GHS', 1)
ON CONFLICT DO NOTHING;

ALTER TABLE post ADD COLUMN IF NOT EXISTS is_paid SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE post ADD COLUMN IF NOT EXISTS price_credits INTEGER NULL;
ALTER TABLE post ADD COLUMN IF NOT EXISTS appraisal_status VARCHAR(32) NOT NULL DEFAULT 'pending_safety';
ALTER TABLE post ADD COLUMN IF NOT EXISTS category_id INTEGER NULL;
ALTER TABLE post ADD COLUMN IF NOT EXISTS safety_scan_job_id VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS purchased_videos (
  id BIGSERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  video_id INTEGER NOT NULL,
  credits_paid INTEGER NOT NULL,
  creator_credits INTEGER NOT NULL,
  platform_commission INTEGER NOT NULL DEFAULT 0,
  purchased_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, video_id)
);
CREATE INDEX IF NOT EXISTS idx_purchased_videos_video ON purchased_videos (video_id);

CREATE TABLE IF NOT EXISTS local_blacklist_keywords (
  id SERIAL PRIMARY KEY,
  keyword VARCHAR(128) NOT NULL UNIQUE,
  locale VARCHAR(16) NOT NULL DEFAULT 'gh',
  severity SMALLINT NOT NULL DEFAULT 1,
  is_active SMALLINT NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS idx_blacklist_active ON local_blacklist_keywords (is_active);

CREATE TABLE IF NOT EXISTS safety_scan_queue (
  id BIGSERIAL PRIMARY KEY,
  video_id INTEGER NOT NULL,
  media_url VARCHAR(512) NOT NULL,
  text_payload TEXT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'queued',
  result_status VARCHAR(32) NULL,
  failure_reason TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed_at TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS idx_safety_queue_status ON safety_scan_queue (status);
CREATE INDEX IF NOT EXISTS idx_safety_queue_video ON safety_scan_queue (video_id);

ALTER TABLE "user" ADD COLUMN IF NOT EXISTS current_streak INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS last_active_date DATE NULL;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS daily_watch_seconds INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS daily_game_score INTEGER NOT NULL DEFAULT 0;
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS streak_frozen_until DATE NULL;

CREATE TABLE IF NOT EXISTS streak_milestone_rewards (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  milestone_day INTEGER NOT NULL,
  credits_awarded DECIMAL(10,2) NOT NULL DEFAULT 0,
  awarded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, milestone_day)
);

CREATE TABLE IF NOT EXISTS group_watch_pots (
  id CHAR(36) NOT NULL PRIMARY KEY,
  video_id INTEGER NOT NULL UNIQUE,
  target_unlocks INTEGER NOT NULL DEFAULT 100,
  current_unlocks INTEGER NOT NULL DEFAULT 0,
  bonus_pool_credits INTEGER NOT NULL DEFAULT 0,
  expires_at TIMESTAMP NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_group_watch_pot_status ON group_watch_pots (status);

CREATE TABLE IF NOT EXISTS group_watch_pot_contributions (
  id BIGSERIAL PRIMARY KEY,
  pot_id CHAR(36) NOT NULL,
  user_id INTEGER NOT NULL,
  video_id INTEGER NOT NULL,
  credits_contributed INTEGER NOT NULL DEFAULT 0,
  bonus_received INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (pot_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_pot_contribution_user ON group_watch_pot_contributions (user_id);

CREATE TABLE IF NOT EXISTS video_predictions (
  id CHAR(36) NOT NULL PRIMARY KEY,
  video_id INTEGER NOT NULL,
  target_metric VARCHAR(64) NOT NULL,
  target_value INTEGER NOT NULL,
  timer_expires_at TIMESTAMP NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'open',
  outcome VARCHAR(16) NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_video_predictions_status ON video_predictions (status);
CREATE INDEX IF NOT EXISTS idx_video_predictions_video ON video_predictions (video_id);

CREATE TABLE IF NOT EXISTS video_prediction_stakes (
  id BIGSERIAL PRIMARY KEY,
  prediction_id CHAR(36) NOT NULL,
  user_id INTEGER NOT NULL,
  stake_credits SMALLINT NOT NULL,
  prediction_side VARCHAR(8) NOT NULL DEFAULT 'yes',
  payout_credits INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (prediction_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_prediction_stakes_user ON video_prediction_stakes (user_id);

CREATE TABLE IF NOT EXISTS dreamland_settings (
  id SMALLINT NOT NULL DEFAULT 1 PRIMARY KEY,
  platform_commission_percent SMALLINT NOT NULL DEFAULT 20,
  streak_freeze_cost INTEGER NOT NULL DEFAULT 5,
  streak_watch_threshold_seconds INTEGER NOT NULL DEFAULT 300,
  streak_game_score_threshold INTEGER NOT NULL DEFAULT 100,
  vapid_public_key VARCHAR(255) NULL,
  vapid_private_key TEXT NULL
);
INSERT INTO dreamland_settings (id) VALUES (1) ON CONFLICT (id) DO NOTHING;
