-- Dreamland v1.1 — Paystack wallet checkout ledger
CREATE TABLE IF NOT EXISTS `credit_package_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `credit_package_id` CHAR(36) NOT NULL,
  `paystack_reference` VARCHAR(128) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'GHS',
  `credits_to_grant` INT NOT NULL,
  `status` ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paystack_reference` (`paystack_reference`),
  KEY `idx_credit_pkg_tx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `dreamland_settings`
  ADD COLUMN `paystack_public_key` VARCHAR(128) NULL DEFAULT NULL,
  ADD COLUMN `paystack_secret_key` VARCHAR(128) NULL DEFAULT NULL;
