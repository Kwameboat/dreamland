-- Dreamland rebrand: update persisted site settings after import
UPDATE `setting` SET
  `website_name` = 'Dreamland',
  `site_name` = 'Dreamland',
  `email` = 'support@dreamland.app'
WHERE `id` = 1;

UPDATE `package` SET
  `in_app_purchase_id_ios` = REPLACE(`in_app_purchase_id_ios`, 'com.sayhi.', 'com.dreamland.'),
  `in_app_purchase_id_android` = REPLACE(`in_app_purchase_id_android`, 'com.sayhi.', 'com.dreamland.')
WHERE `in_app_purchase_id_ios` LIKE '%sayhi%' OR `in_app_purchase_id_android` LIKE '%sayhi%';
