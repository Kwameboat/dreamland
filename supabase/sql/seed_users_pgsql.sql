-- Demo users for Dreamland (password: demo123)

INSERT INTO setting (site_name, email, ads_auto_approve)
SELECT 'Dreamland', 'support@dreamland.app', 0
WHERE NOT EXISTS (SELECT 1 FROM setting LIMIT 1);

UPDATE setting SET site_name = 'Dreamland', email = 'support@dreamland.app' WHERE id = (SELECT id FROM setting ORDER BY id LIMIT 1);

INSERT INTO dreamland_settings (id) VALUES (1) ON CONFLICT (id) DO NOTHING;

INSERT INTO "user" (
  role, dreamland_account_type, dreamland_creator_status, username, name, email,
  password_hash, auth_key, status, is_email_verified, account_created_with,
  available_coin, created_at, unique_id
)
SELECT 1, 'viewer', 'none', 'admin', 'Admin', 'admin@gmail.com',
  '$2y$10$SrEO4PPG7HXGlGF7Pf3qL.j0FneQnUdBVjJXhqWTumKDbFJ7GSxOq', 'admin-demo-auth-key-32chars!!', 10, 1, 1,
  0, EXTRACT(EPOCH FROM NOW())::integer, 100000
WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'admin@gmail.com');

INSERT INTO "user" (
  role, dreamland_account_type, dreamland_creator_status, username, name, email,
  password_hash, auth_key, status, is_email_verified, account_created_with,
  available_coin, created_at, unique_id
)
SELECT 3, 'viewer', 'none', 'dreamviewer', 'Dream Viewer', 'viewer@dreamland.app',
  '$2y$10$SrEO4PPG7HXGlGF7Pf3qL.j0FneQnUdBVjJXhqWTumKDbFJ7GSxOq', 'viewer-demo-auth-key-32chars!!', 10, 1, 1,
  100, EXTRACT(EPOCH FROM NOW())::integer, 100001
WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'viewer@dreamland.app');

INSERT INTO "user" (
  role, dreamland_account_type, dreamland_creator_status, username, name, email,
  password_hash, auth_key, status, is_email_verified, account_created_with,
  available_coin, created_at, unique_id
)
SELECT 4, 'creator', 'approved', 'dreamcreator', 'Dream Creator', 'creator@dreamland.app',
  '$2y$10$SrEO4PPG7HXGlGF7Pf3qL.j0FneQnUdBVjJXhqWTumKDbFJ7GSxOq', 'creator-demo-auth-key-32chars!', 10, 1, 1,
  50, EXTRACT(EPOCH FROM NOW())::integer, 100002
WHERE NOT EXISTS (SELECT 1 FROM "user" WHERE email = 'creator@dreamland.app');
