-- Dreamland v1.0 Migration — Play, Watch, Earn
-- Run after sayhi_v1_6.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- A. Admin-controlled credit packages (replaces user-defined coin inputs)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `credit_packages` (
  `id` CHAR(36) NOT NULL,
  `credit_amount` INT NOT NULL,
  `fiat_cost` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'GHS',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_credit_packages_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `credit_packages` (`id`, `credit_amount`, `fiat_cost`, `currency`, `is_active`) VALUES
(UUID(), 50, 5.00, 'GHS', 1),
(UUID(), 120, 10.00, 'GHS', 1),
(UUID(), 300, 25.00, 'GHS', 1),
(UUID(), 650, 50.00, 'GHS', 1);

-- ---------------------------------------------------------------------------
-- B. Video/reel appraisal & paid content columns on post table
-- ---------------------------------------------------------------------------
ALTER TABLE `post`
  ADD COLUMN `is_paid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `display_whose`,
  ADD COLUMN `price_credits` INT NULL DEFAULT NULL AFTER `is_paid`,
  ADD COLUMN `appraisal_status` ENUM('pending_safety','pending_review','active','rejected') NOT NULL DEFAULT 'pending_safety' AFTER `price_credits`,
  ADD COLUMN `category_id` INT NULL DEFAULT NULL AFTER `appraisal_status`,
  ADD COLUMN `safety_scan_job_id` VARCHAR(64) NULL DEFAULT NULL AFTER `category_id`;

-- Backfill existing posts as active free content
UPDATE `post` SET `appraisal_status` = 'active' WHERE `appraisal_status` = 'pending_safety' AND `status` = 10;

-- ---------------------------------------------------------------------------
-- C. Permanent video purchase ledger
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchased_videos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `credits_paid` INT NOT NULL,
  `creator_credits` INT NOT NULL,
  `platform_commission` INT NOT NULL DEFAULT 0,
  `purchased_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_purchased_videos_user_video` (`user_id`, `video_id`),
  KEY `idx_purchased_videos_video` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- D. Ghanaian / localized profanity blacklist
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `local_blacklist_keywords` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(128) NOT NULL,
  `locale` VARCHAR(16) NOT NULL DEFAULT 'gh',
  `severity` TINYINT NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blacklist_keyword` (`keyword`),
  KEY `idx_blacklist_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `local_blacklist_keywords` (`keyword`, `locale`, `severity`) VALUES
('chale fool', 'gh', 2),
('kwasia', 'gh', 3),
('kwasia man', 'gh', 3),
('gyimii', 'gh', 3),
('gyimi', 'gh', 3),
('kwasia', 'gh', 3),
('obolo', 'gh', 2),
('kooko', 'gh', 2),
('momo thief', 'gh', 2),
('sakora head', 'gh', 2),
('ashawo', 'gh', 3),
('ashaw', 'gh', 3),
('prostitute', 'gh', 2),
('foolish bar', 'gh', 2),
('stupid fool', 'gh', 2),
('nonsense talk', 'gh', 1),
('shut up fool', 'gh', 2),
('you be fool', 'gh', 2),
('chale nonsense', 'gh', 1),
('twi insult', 'gh', 2),
('ga insult', 'gh', 2),
('ewei', 'gh', 2),
('aboa', 'gh', 3),
('aboa ba', 'gh', 3),
('kwasea', 'gh', 3),
('gyimii papa', 'gh', 3),
('dead body', 'gh', 2),
('kill yourself', 'gh', 3),
('go die', 'gh', 3);

-- Safety scan queue for async worker pipeline
CREATE TABLE IF NOT EXISTS `safety_scan_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `video_id` INT NOT NULL,
  `media_url` VARCHAR(512) NOT NULL,
  `text_payload` TEXT NULL,
  `status` ENUM('queued','processing','completed','failed') NOT NULL DEFAULT 'queued',
  `result_status` ENUM('pending_safety','pending_review','active','rejected') NULL DEFAULT NULL,
  `failure_reason` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_safety_queue_status` (`status`),
  KEY `idx_safety_queue_video` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- E. Main Character streak tracking on user
-- ---------------------------------------------------------------------------
ALTER TABLE `user`
  ADD COLUMN `current_streak` INT NOT NULL DEFAULT 0 AFTER `available_coin`,
  ADD COLUMN `last_active_date` DATE NULL DEFAULT NULL AFTER `current_streak`,
  ADD COLUMN `daily_watch_seconds` INT NOT NULL DEFAULT 0 AFTER `last_active_date`,
  ADD COLUMN `daily_game_score` INT NOT NULL DEFAULT 0 AFTER `daily_watch_seconds`,
  ADD COLUMN `streak_frozen_until` DATE NULL DEFAULT NULL AFTER `daily_game_score`;

CREATE TABLE IF NOT EXISTS `streak_milestone_rewards` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `milestone_day` INT NOT NULL,
  `credits_awarded` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `awarded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_streak_milestone` (`user_id`, `milestone_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- F. Co-Op Watch Pots (Social Hunt)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `group_watch_pots` (
  `id` CHAR(36) NOT NULL,
  `video_id` INT NOT NULL,
  `target_unlocks` INT NOT NULL DEFAULT 100,
  `current_unlocks` INT NOT NULL DEFAULT 0,
  `bonus_pool_credits` INT NOT NULL DEFAULT 0,
  `expires_at` TIMESTAMP NOT NULL,
  `status` ENUM('open','completed','expired') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_group_watch_pot_video` (`video_id`),
  KEY `idx_group_watch_pot_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `group_watch_pot_contributions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pot_id` CHAR(36) NOT NULL,
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `credits_contributed` INT NOT NULL DEFAULT 0,
  `bonus_received` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pot_contribution` (`pot_id`, `user_id`),
  KEY `idx_pot_contribution_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- G. Micro-betting / trend speculation market
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `video_predictions` (
  `id` CHAR(36) NOT NULL,
  `video_id` INT NOT NULL,
  `target_metric` VARCHAR(64) NOT NULL,
  `target_value` INT NOT NULL,
  `timer_expires_at` TIMESTAMP NOT NULL,
  `status` ENUM('open','resolved') NOT NULL DEFAULT 'open',
  `outcome` ENUM('hit','miss','pending') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_video_predictions_status` (`status`),
  KEY `idx_video_predictions_video` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `video_prediction_stakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prediction_id` CHAR(36) NOT NULL,
  `user_id` INT NOT NULL,
  `stake_credits` TINYINT NOT NULL,
  `prediction_side` ENUM('yes','no') NOT NULL DEFAULT 'yes',
  `payout_credits` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prediction_stake` (`prediction_id`, `user_id`),
  KEY `idx_prediction_stakes_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dreamland platform settings
CREATE TABLE IF NOT EXISTS `dreamland_settings` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `platform_commission_percent` TINYINT NOT NULL DEFAULT 20,
  `streak_freeze_cost` INT NOT NULL DEFAULT 5,
  `streak_watch_threshold_seconds` INT NOT NULL DEFAULT 300,
  `streak_game_score_threshold` INT NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `dreamland_settings` (`id`) VALUES (1);

SET FOREIGN_KEY_CHECKS = 1;
