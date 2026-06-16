-- Login IP blocklist + Paystack + PWA preview settings used by API wallet/meta endpoints

CREATE TABLE IF NOT EXISTS blocked_ip (
  id SERIAL PRIMARY KEY,
  ip_address VARCHAR(100),
  description TEXT,
  created_at INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_blocked_ip_address ON blocked_ip (ip_address);

ALTER TABLE dreamland_settings
  ADD COLUMN IF NOT EXISTS preview_seconds INTEGER NOT NULL DEFAULT 3,
  ADD COLUMN IF NOT EXISTS paystack_public_key VARCHAR(128),
  ADD COLUMN IF NOT EXISTS paystack_secret_key VARCHAR(128);
