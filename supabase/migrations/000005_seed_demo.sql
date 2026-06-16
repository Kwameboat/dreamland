-- Settings rebrand for Dreamland (optional)
-- Demo users: run `php scripts/seed-supabase-demo.php` after migrations.

INSERT INTO dreamland_settings (id) VALUES (1) ON CONFLICT (id) DO NOTHING;

UPDATE setting SET site_name = 'Dreamland', email = 'support@dreamland.app'
WHERE id = 1;
