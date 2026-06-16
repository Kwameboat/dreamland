-- Demo reels for production PWA feed (creator@dreamland.app must exist)

DO $$
DECLARE
  creator_id INTEGER;
  post_id INTEGER;
  now_ts INTEGER := EXTRACT(EPOCH FROM NOW())::INTEGER;
BEGIN
  SELECT id INTO creator_id FROM "user" WHERE email = 'creator@dreamland.app' LIMIT 1;
  IF creator_id IS NULL THEN
    RAISE NOTICE 'Skip reel seed: creator@dreamland.app not found';
    RETURN;
  END IF;

  IF EXISTS (SELECT 1 FROM post WHERE type = 4 AND status = 10 AND appraisal_status = 'active' LIMIT 1) THEN
    RAISE NOTICE 'Reels already present';
    RETURN;
  END IF;

  INSERT INTO post (
    user_id, type, post_content_type, title, description, status, appraisal_status,
    is_paid, price_credits, created_at, created_by, total_view, total_like, total_share
  ) VALUES (
    creator_id, 4, 1,
    convert_to('Welcome to Dreamland', 'UTF8'),
    convert_to('Play, Watch, Earn — Ghana''s premium short-video network', 'UTF8'),
    10, 'active', 1, 15, now_ts, creator_id, 1200, 48, 12
  ) RETURNING id INTO post_id;

  INSERT INTO post_gallary (post_id, filename, media_type, type, is_default, status, created_at, width, height)
  VALUES (
    post_id,
    'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
    4, 1, 1, 10, now_ts, 720, 1280
  );

  INSERT INTO post (
    user_id, type, post_content_type, title, description, status, appraisal_status,
    is_paid, price_credits, created_at, created_by, total_view, total_like, total_share
  ) VALUES (
    creator_id, 4, 1,
    convert_to('Accra nights — free reel', 'UTF8'),
    convert_to('Sample free content on Dreamland', 'UTF8'),
    10, 'active', 0, 0, now_ts, creator_id, 840, 22, 5
  ) RETURNING id INTO post_id;

  INSERT INTO post_gallary (post_id, filename, media_type, type, is_default, status, created_at, width, height)
  VALUES (
    post_id,
    'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
    4, 1, 1, 10, now_ts, 720, 1280
  );
END $$;
