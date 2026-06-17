-- Align legacy creator rows: active creators with pending dreamland_creator_status stay pending
-- until admin approves via Content Creators (sets dreamland_creator_status = approved).
-- No automatic mass-approve here — use admin Approve button after deploy.

CREATE INDEX IF NOT EXISTS idx_user_dreamland_creator_status ON "user" (dreamland_creator_status)
  WHERE dreamland_creator_status IS NOT NULL;
