-- Admin-configurable reel / live upload limits (Dreamland Settings)
ALTER TABLE dreamland_settings
  ADD COLUMN IF NOT EXISTS max_reel_duration_seconds INTEGER NOT NULL DEFAULT 60,
  ADD COLUMN IF NOT EXISTS max_reel_upload_mb INTEGER NOT NULL DEFAULT 128,
  ADD COLUMN IF NOT EXISTS max_live_duration_seconds INTEGER NOT NULL DEFAULT 3600;

COMMENT ON COLUMN dreamland_settings.max_reel_duration_seconds IS 'Max reel clip length in seconds';
COMMENT ON COLUMN dreamland_settings.max_reel_upload_mb IS 'Max reel upload file size in megabytes';
COMMENT ON COLUMN dreamland_settings.max_live_duration_seconds IS 'Max live broadcast length in seconds';
