<?php

use yii\db\Migration;

class m250613_000003_dreamland_v2_v3_settings extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('user', true) && !$this->db->schema->getTableSchema('user')->getColumn('dreamland_account_type')) {
            $this->execute("ALTER TABLE `user` ADD COLUMN `dreamland_account_type` ENUM('viewer','creator') NOT NULL DEFAULT 'viewer' AFTER `role`");
        }

        $liveCols = ['live_title', 'is_monetized', 'price_credits', 'total_comment'];
        $liveSchema = $this->db->schema->getTableSchema('user_live_history', true);
        if ($liveSchema) {
            if (!$liveSchema->getColumn('live_title')) {
                $this->addColumn('user_live_history', 'live_title', $this->string(255)->null()->after('token'));
            }
            if (!$liveSchema->getColumn('is_monetized')) {
                $this->addColumn('user_live_history', 'is_monetized', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('live_title'));
            }
            if (!$liveSchema->getColumn('price_credits')) {
                $this->addColumn('user_live_history', 'price_credits', $this->integer()->null()->after('is_monetized'));
            }
            if (!$liveSchema->getColumn('total_comment')) {
                $this->addColumn('user_live_history', 'total_comment', $this->integer()->notNull()->defaultValue(0)->after('price_credits'));
            }
        }

        if (!$this->db->schema->getTableSchema('purchased_lives', true)) {
            $this->execute(file_get_contents(__DIR__ . '/../../doc/db/dreamland_v3_live.sql'));
        }

        $settingsSchema = $this->db->schema->getTableSchema('dreamland_settings', true);
        if ($settingsSchema && !$settingsSchema->getColumn('preview_seconds')) {
            $this->addColumn('dreamland_settings', 'preview_seconds', $this->tinyInteger()->notNull()->defaultValue(3)->after('streak_game_score_threshold'));
        }

        $this->execute("UPDATE `user` SET `dreamland_account_type` = 'creator', `role` = 4 WHERE `email` = 'creator@dreamland.app'");
        $this->execute("UPDATE `user` SET `dreamland_account_type` = 'viewer', `role` = 3 WHERE `email` = 'viewer@dreamland.app'");
    }

    public function safeDown()
    {
        if ($this->db->schema->getTableSchema('dreamland_settings', true)?->getColumn('preview_seconds')) {
            $this->dropColumn('dreamland_settings', 'preview_seconds');
        }
    }
}
