CREATE TABLE IF NOT EXISTS `group_watch_pots` (
  `id` CHAR(36) NOT NULL,
  `video_id` INT NOT NULL,
  `target_unlocks` INT NOT NULL DEFAULT 100,
  `current_unlocks` INT NOT NULL DEFAULT 0,
  `bonus_pool_credits` INT NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_watch_pot_video` (`video_id`),
  KEY `idx_group_watch_pot_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `group_watch_pot_contributions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pot_id` CHAR(36) NOT NULL,
  `user_id` INT NOT NULL,
  `video_id` INT NOT NULL,
  `credits_contributed` INT NOT NULL DEFAULT 0,
  `bonus_received` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pot_contribution_user` (`pot_id`, `user_id`),
  KEY `idx_pot_contribution_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `video_predictions` (
  `id` CHAR(36) NOT NULL,
  `video_id` INT NOT NULL,
  `target_metric` VARCHAR(64) NOT NULL,
  `target_value` INT NOT NULL,
  `timer_expires_at` DATETIME NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'open',
  `outcome` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_video_predictions_status` (`status`),
  KEY `idx_video_predictions_video` (`video_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `video_prediction_stakes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prediction_id` CHAR(36) NOT NULL,
  `user_id` INT NOT NULL,
  `stake_credits` SMALLINT NOT NULL,
  `prediction_side` VARCHAR(8) NOT NULL DEFAULT 'yes',
  `payout_credits` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prediction_stake_user` (`prediction_id`, `user_id`),
  KEY `idx_prediction_stake_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
