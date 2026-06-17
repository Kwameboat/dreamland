-- Creator genres / profile categories (admin: Users → Creator Genres)

CREATE TABLE IF NOT EXISTS profile_category_type (
  id SERIAL PRIMARY KEY,
  name VARCHAR(250),
  status INTEGER NOT NULL DEFAULT 10,
  image VARCHAR(256)
);

CREATE INDEX IF NOT EXISTS idx_profile_category_type_status ON profile_category_type (status);

INSERT INTO profile_category_type (name, status, image)
SELECT v.name, 10, NULL
FROM (VALUES
  ('Comedy'),
  ('Music & Dance'),
  ('Sports'),
  ('Food & Culture'),
  ('Fashion'),
  ('Tech & Gaming'),
  ('Education'),
  ('Lifestyle')
) AS v(name)
WHERE NOT EXISTS (
  SELECT 1 FROM profile_category_type p WHERE p.name = v.name
);
