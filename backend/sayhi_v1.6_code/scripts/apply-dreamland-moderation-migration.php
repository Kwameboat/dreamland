<?php
/**
 * Seed expanded Ghana multilingual moderation keywords.
 * Run: php scripts/apply-dreamland-moderation-migration.php
 */
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/common/config/bootstrap.php';
require $root . '/console/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/console/config/main.php'
);
new yii\console\Application($config);

$db = Yii::$app->db;

$keywords = [
    ['sakawa', 'gh', 3],
    ['sakawa boy', 'gh', 3],
    ['double your money', 'en-gh', 3],
    ['send momo to claim', 'en-gh', 3],
    ['bitcoin doubling', 'en-gh', 3],
    ['you dey mad', 'pidgin', 2],
    ['make i beat you', 'pidgin', 2],
    ['gbe na wu', 'ee', 3],
    ['ma wu', 'tw', 3],
    ['kum wo', 'tw', 3],
    ['tafratchi', 'tw', 2],
    ['banza', 'ha', 2],
    ['bunsuru', 'dag', 2],
    ['nude video', 'en-gh', 3],
    ['sex tape', 'en-gh', 3],
    ['format boy', 'en-gh', 2],
    ['click here to claim', 'en-gh', 2],
    ['free airtime promo', 'en-gh', 2],
    ['i go slap you', 'pidgin', 2],
    ['chale you dey craze', 'pidgin', 2],
];

$inserted = 0;
foreach ($keywords as [$keyword, $locale, $severity]) {
    try {
        $db->createCommand()->insert('local_blacklist_keywords', [
            'keyword' => $keyword,
            'locale' => $locale,
            'severity' => $severity,
            'is_active' => 1,
        ])->execute();
        $inserted++;
    } catch (\Throwable $e) {
        // duplicate keyword — skip
    }
}

echo "Dreamland moderation migration: {$inserted} new keywords seeded.\n";
echo "Start agent: dreamland/start-moderation-agent.ps1\n";
