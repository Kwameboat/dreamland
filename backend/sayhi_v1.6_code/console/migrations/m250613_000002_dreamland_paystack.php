<?php

use yii\db\Migration;

class m250613_000002_dreamland_paystack extends Migration
{
    public function safeUp()
    {
        $sql = file_get_contents(__DIR__ . '/../../doc/db/dreamland_v1_1_paystack.sql');
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($statements as $statement) {
            if ($statement === '') {
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
        $this->dropTable('credit_package_transactions');
    }
}
