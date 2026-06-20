<?php

use yii\db\Migration;

class m250617_000021_dreamland_upload_limits extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('dreamland_settings', true);
        if (!$schema) {
            return;
        }
        if (!$schema->getColumn('max_reel_duration_seconds')) {
            $this->addColumn('dreamland_settings', 'max_reel_duration_seconds', $this->integer()->notNull()->defaultValue(60));
        }
        if (!$schema->getColumn('max_reel_upload_mb')) {
            $this->addColumn('dreamland_settings', 'max_reel_upload_mb', $this->integer()->notNull()->defaultValue(128));
        }
        if (!$schema->getColumn('max_live_duration_seconds')) {
            $this->addColumn('dreamland_settings', 'max_live_duration_seconds', $this->integer()->notNull()->defaultValue(3600));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('dreamland_settings', true);
        if (!$schema) {
            return;
        }
        foreach (['max_live_duration_seconds', 'max_reel_upload_mb', 'max_reel_duration_seconds'] as $col) {
            if ($schema->getColumn($col)) {
                $this->dropColumn('dreamland_settings', $col);
            }
        }
    }
}
