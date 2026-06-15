ALTER TABLE `setting` ADD `wasabi_access_key` VARCHAR(256) NULL DEFAULT NULL AFTER `aws_access_url`, ADD `wasabi_secret_key` VARCHAR(256) NULL DEFAULT NULL AFTER `wasabi_access_key`, ADD `wasabi_region` VARCHAR(256) NULL DEFAULT NULL AFTER `wasabi_secret_key`, ADD `wasabi_bucket` VARCHAR(256) NULL DEFAULT NULL AFTER `wasabi_region`, ADD `wasabi_access_url` VARCHAR(256) NULL DEFAULT NULL AFTER `wasabi_bucket`; 
ALTER TABLE `user` ADD `passionate` INT NULL DEFAULT NULL AFTER `following_status`, ADD `holistic_path` INT NULL DEFAULT NULL AFTER `passionate`;
ALTER TABLE `user` CHANGE `passionate` `passionate` INT NULL DEFAULT '0', CHANGE `holistic_path` `holistic_path` INT NULL DEFAULT '0';
ALTER TABLE `user_preference` ADD `passionate` INT NOT NULL DEFAULT '0' AFTER `height_to`, ADD `holistic_path_from` INT NOT NULL DEFAULT '0' AFTER `passionate`, ADD `holistic_path_to` INT NOT NULL DEFAULT '0' AFTER `holistic_path_from`;
ALTER TABLE `setting` ADD `vonage_api_key` VARCHAR(256) NULL DEFAULT NULL AFTER `msg91_sender_id`, ADD `vonage_api_secret` VARCHAR(256) NULL DEFAULT NULL AFTER `vonage_api_key`;
DROP TABLE IF EXISTS `user_reaction`;
CREATE TABLE IF NOT EXISTS `user_reaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` int NOT NULL DEFAULT '0',
  `reference_id` int NOT NULL,
  `reaction` int NOT NULL DEFAULT '0',
  `total_item` int NOT NULL DEFAULT '1',
  `created_at` int NOT NULL,
  `updated_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;