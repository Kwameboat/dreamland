-- Dreamland v4: watch-time signals for smarter feed ranking
CREATE TABLE IF NOT EXISTS `post_watch_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `post_id` int unsigned NOT NULL,
  `watch_ms` int unsigned NOT NULL DEFAULT 0,
  `completion_pct` tinyint unsigned NOT NULL DEFAULT 0,
  `rewatched` tinyint unsigned NOT NULL DEFAULT 0,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pwe_user_created` (`user_id`, `created_at`),
  KEY `idx_pwe_post_created` (`post_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
