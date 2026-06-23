-- Admin RBAC tables required by AuthPermission (backend sidebar + dashboard)

CREATE TABLE IF NOT EXISTS module_auth (
  id SERIAL PRIMARY KEY,
  name VARCHAR(256),
  alias VARCHAR(256),
  level INTEGER NOT NULL DEFAULT 1,
  parent_id INTEGER,
  action_list TEXT
);

CREATE TABLE IF NOT EXISTS module_auth_user (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  module_auth_id INTEGER NOT NULL,
  is_enabled INTEGER NOT NULL DEFAULT 1
);

INSERT INTO module_auth (id, name, alias, level, parent_id, action_list) VALUES
  (1, 'Administrator', 'administrator', 1, NULL, NULL),
  (2, 'User', 'user', 1, NULL, NULL),
  (3, 'Post', 'post', 1, NULL, NULL),
  (4, 'Competition', 'competition', 1, NULL, NULL),
  (5, 'Club', 'club', 1, NULL, NULL),
  (6, 'Support Request', 'supportRequest', 1, NULL, NULL),
  (7, 'Payment', 'payment', 1, NULL, NULL),
  (8, 'Package', 'package', 1, NULL, NULL),
  (9, 'Tv Channel', 'tvChannel', 1, NULL, NULL),
  (10, 'Podcast', 'podcast', 1, NULL, NULL),
  (11, 'Gift', 'gift', 1, NULL, NULL),
  (12, 'Faq', 'faq', 1, NULL, NULL),
  (13, 'Organization', 'organization', 1, NULL, NULL),
  (14, 'Event', 'event', 1, NULL, NULL),
  (15, 'Fund Raising', 'fundRaising', 1, NULL, NULL),
  (16, 'Reel', 'reel', 1, NULL, NULL),
  (17, 'Poll', 'poll', 1, NULL, NULL),
  (18, 'Broadcast Notifications', 'broadcastNotifications', 1, NULL, NULL),
  (19, 'Coupon', 'coupon', 1, NULL, NULL),
  (20, 'Dating', 'dating', 1, NULL, NULL),
  (21, 'Story', 'story', 1, NULL, NULL),
  (22, 'Job', 'job', 1, NULL, NULL),
  (23, 'Ad', 'ad', 1, NULL, NULL),
  (24, 'Report', 'report', 1, NULL, NULL),
  (25, 'Setting', 'setting', 1, NULL, NULL),
  (26, 'Live History', 'liveHistory', 1, NULL, NULL),
  (27, 'Post Promotion', 'promotion', 1, NULL, NULL),
  (28, 'Dreamland Appraisal', 'dreamlandAppraisal', 1, NULL, NULL),
  (29, 'Dreamland AI Moderation', 'dreamlandModeration', 1, NULL, NULL),
  (30, 'Dreamland Safety Queue', 'dreamlandSafety', 1, NULL, NULL),
  (31, 'Dreamland Platform Settings', 'dreamlandSettings', 1, NULL, NULL),
  (32, 'Credit Packages', 'creditPackage', 1, NULL, NULL)
ON CONFLICT (id) DO NOTHING;

SELECT setval(
  pg_get_serial_sequence('module_auth', 'id'),
  GREATEST((SELECT COALESCE(MAX(id), 1) FROM module_auth), 32)
);
