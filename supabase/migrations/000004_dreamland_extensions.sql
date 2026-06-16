-- Dreamland Supabase bootstrap
-- Extra columns on top of core schema + Dreamland v1–v3 migrations.

ALTER TABLE post ADD COLUMN IF NOT EXISTS rejection_reason TEXT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS rejected_at BIGINT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS rejected_by INTEGER;
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_status VARCHAR(32);
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_message TEXT;
ALTER TABLE post ADD COLUMN IF NOT EXISTS appeal_submitted_at BIGINT;

ALTER TABLE "user" ADD COLUMN IF NOT EXISTS dreamland_account_type VARCHAR(16) DEFAULT 'viewer';
ALTER TABLE "user" ADD COLUMN IF NOT EXISTS dreamland_creator_status VARCHAR(16) DEFAULT 'none';

COMMENT ON COLUMN post.rejection_reason IS 'Admin reason shown to creator on rejection';
