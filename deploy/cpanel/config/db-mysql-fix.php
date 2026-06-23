<?php
/**
 * cPanel MySQL: restore Yii schema driver when main-local.php sets schemaMap to [].
 * Merged last in public_html entrypoints (admin/index.php, api/index.php).
 */
return [
    'components' => [
        'db' => [
            'schemaMap' => [
                'mysql' => 'yii\db\mysql\Schema',
                'mysqli' => 'yii\db\mysql\Schema',
            ],
        ],
    ],
];
