-- Dreamland v2 — Creator / viewer accounts + live monetization
SET NAMES utf8mb4;

ALTER TABLE `user`
  ADD COLUMN IF NOT EXISTS `dreamland_account_type` ENUM('viewer','creator') NOT NULL DEFAULT 'viewer' AFTER `role`;

ALTER TABLE `user_live_history`
  ADD COLUMN IF NOT EXISTS `live_title` VARCHAR(255) NULL DEFAULT NULL AFTER `token`,
  ADD COLUMN IF NOT EXISTS `is_monetized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `live_title`,
  ADD COLUMN IF NOT EXISTS `price_credits` INT NULL DEFAULT NULL AFTER `is_monetized`,
  ADD COLUMN IF NOT EXISTS `total_comment` INT NOT NULL DEFAULT 0 AFTER `price_credits`;

UPDATE `user` SET `dreamland_account_type` = 'creator', `role` = 4
WHERE `email` = 'creator@dreamland.app';

UPDATE `user` SET `dreamland_account_type` = 'viewer', `role` = 3
WHERE `email` = 'viewer@dreamland.app';
