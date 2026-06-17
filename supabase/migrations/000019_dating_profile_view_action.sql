-- Dating profile swipe actions (referenced by User API serializers)

CREATE TABLE IF NOT EXISTS dating_profile_view_action (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  profile_user_id INTEGER NOT NULL,
  type INTEGER NOT NULL DEFAULT 0,
  created_by INTEGER NOT NULL DEFAULT 0,
  created_at INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_dating_profile_view_action_user ON dating_profile_view_action (user_id);
CREATE INDEX IF NOT EXISTS idx_dating_profile_view_action_profile ON dating_profile_view_action (profile_user_id);
