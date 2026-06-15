<?php

use yii\db\Migration;

class m250613_000001_dreamland_init extends Migration
{
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/../../doc/db/dreamland_v1_migration.sql');
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($statements as $statement) {
            if ($statement === '' || stripos($statement, 'SET ') === 0) {
                continue;
            }
            try {
                $this->db->createCommand($statement)->execute();
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate column') === false
                    && stripos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }

    public function safeDown()
    {
        $this->dropTable('video_prediction_stakes');
        $this->dropTable('video_predictions');
        $this->dropTable('group_watch_pot_contributions');
        $this->dropTable('group_watch_pots');
        $this->dropTable('streak_milestone_rewards');
        $this->dropTable('safety_scan_queue');
        $this->dropTable('local_blacklist_keywords');
        $this->dropTable('purchased_videos');
        $this->dropTable('credit_packages');
        $this->dropTable('dreamland_settings');
    }
}
