-- Dreamland content rejection reasons + creator appeals
ALTER TABLE `post`
  ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT NULL DEFAULT NULL AFTER `appraisal_status`,
  ADD COLUMN IF NOT EXISTS `rejected_at` INT NULL DEFAULT NULL AFTER `rejection_reason`,
  ADD COLUMN IF NOT EXISTS `rejected_by` INT NULL DEFAULT NULL AFTER `rejected_at`,
  ADD COLUMN IF NOT EXISTS `appeal_status` VARCHAR(32) NULL DEFAULT NULL AFTER `rejected_by`,
  ADD COLUMN IF NOT EXISTS `appeal_message` TEXT NULL DEFAULT NULL AFTER `appeal_status`,
  ADD COLUMN IF NOT EXISTS `appeal_submitted_at` INT NULL DEFAULT NULL AFTER `appeal_message`;
