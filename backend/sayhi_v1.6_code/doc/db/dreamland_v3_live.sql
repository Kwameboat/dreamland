-- Dreamland v3: monetized live unlock tracking
CREATE TABLE IF NOT EXISTS `purchased_lives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `live_id` int NOT NULL,
  `credits_paid` int NOT NULL DEFAULT 0,
  `creator_credits` int NOT NULL DEFAULT 0,
  `platform_commission` int NOT NULL DEFAULT 0,
  `purchased_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_live` (`user_id`, `live_id`),
  KEY `idx_live_id` (`live_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
