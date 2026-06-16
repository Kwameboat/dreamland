-- Post title/description were stored as bytea from MySQL migration; expose as readable text in API.

DO $$
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'post' AND column_name = 'title' AND udt_name = 'bytea'
  ) THEN
    ALTER TABLE post
      ALTER COLUMN title TYPE text USING convert_from(title, 'UTF8'),
      ALTER COLUMN description TYPE text USING convert_from(description, 'UTF8');
  END IF;

  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name = 'post' AND column_name = 'share_comment' AND udt_name = 'bytea'
  ) THEN
    ALTER TABLE post
      ALTER COLUMN share_comment TYPE text USING convert_from(share_comment, 'UTF8');
  END IF;
END $$;

-- Re-seed readable titles if rows still look like binary placeholders
UPDATE post SET title = 'Welcome to Dreamland'
WHERE type = 4 AND (title IS NULL OR title = '' OR title LIKE '\\x%');

UPDATE post SET description = 'Play, Watch, Earn on Dreamland'
WHERE type = 4 AND (description IS NULL OR description = '' OR description LIKE '\\x%');
