<?php
/**
 * Disable legacy SayHi features and remove irrelevant content for Dreamland workflow.
 *
 * Usage: php scripts/dreamland-disable-legacy.php
 */

function dreamlandTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function dreamlandTruncateTable(PDO $pdo, string $table, bool $verbose = true): void
{
    if (!dreamlandTableExists($pdo, $table)) {
        return;
    }
    $pdo->exec("TRUNCATE TABLE `{$table}`");
    if ($verbose) {
        echo "  truncated {$table}\n";
    }
}

function dreamlandClearTable(PDO $pdo, string $table, bool $verbose = true): void
{
    if (!dreamlandTableExists($pdo, $table)) {
        return;
    }
    $pdo->exec("DELETE FROM `{$table}`");
    if ($verbose) {
        echo "  cleared {$table}\n";
    }
}

function dreamlandDisableLegacy(PDO $pdo, bool $verbose = true): void
{
    if ($verbose) {
        echo "Disabling legacy feature flags...\n";
    }

    $pdo->exec("UPDATE setting SET
        is_photo_post = 0,
        is_stories = 0,
        is_story_highlights = 0,
        is_chat = 0,
        is_audio_calling = 0,
        is_video_calling = 0,
        is_clubs = 0,
        is_competitions = 0,
        is_events = 0,
        is_staranger_chat = 0,
        is_watch_tv = 0,
        is_podcasts = 0,
        is_gift_sending = 0,
        is_polls = 0,
        is_dating = 0,
        is_fund_raising = 0,
        is_family_link_setup = 0,
        is_post_promotion = 0,
        is_chat_gpt = 0,
        is_coupon = 0,
        is_job = 0,
        is_shop = 0,
        is_live_user = 0,
        is_offer = 0,
        is_contact_sharing = 0,
        is_location_sharing = 0,
        is_photo_share = 0,
        is_video_share = 0,
        is_files_share = 0,
        is_gift_share = 0,
        is_audio_share = 0,
        is_drawing_share = 0,
        is_user_profile_share = 0,
        is_club_share = 0,
        is_events_share = 0,
        is_reply = 0,
        is_forward = 0,
        is_star_message = 0,
        is_two_factor_auth = 0,
        is_reel = 1,
        is_video_post = 1,
        is_live = 1,
        is_profile_verification = 1
    WHERE id = 1");

    $keepFeatureKeys = [
        'reel',
        'enable_video_post',
        'enable_live',
        'enable_profile_verification',
    ];

    if (dreamlandTableExists($pdo, 'feature_enabled') && dreamlandTableExists($pdo, 'feature_list')) {
        $pdo->exec('DELETE FROM feature_enabled WHERE type = 1');
        $placeholders = implode(',', array_fill(0, count($keepFeatureKeys), '?'));
        $stmt = $pdo->prepare(
            "INSERT INTO feature_enabled (feature_id, type, is_enabled)
             SELECT id, 1, CASE WHEN feature_key IN ({$placeholders}) THEN 1 ELSE 0 END
             FROM feature_list WHERE status = 10"
        );
        $stmt->execute($keepFeatureKeys);
        if ($verbose) {
            echo "  synced feature_enabled\n";
        }
    }

    if ($verbose) {
        echo "Removing legacy content data...\n";
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $legacyTables = [
        'dating_daily_profile_view',
        'dating_match_profile',
        'dating_profile_view_action',
        'dating_subscription',
        'dating_user_subscription',
        'interest',
        'club_invitation_request',
        'club_user',
        'club',
        'club_category',
        'event_ticket_booking',
        'event_coupon',
        'event_ticket',
        'event_organisor',
        'event',
        'campaign',
        'poll_question_answer',
        'poll_option',
        'poll',
        'job_application',
        'job',
        'ad_image',
        'ad',
        'ad_package',
        'promotional_ad',
        'promotional_banner',
        'categorysub',
        'live_tv',
        'live_tv_category',
        'tv_show_episode',
        'tv_show',
        'tv_banner',
        'podcast_show_episode',
        'podcast_show',
        'podcast_banner',
        'podcast',
        'podcast_category',
        'gift_timeline',
        'gift',
        'gift_category',
        'competition',
        'coupon',
        'business',
        'story',
        'highlight',
        'post_promotion',
        'pickleball_team_player',
        'pickleball_match_team',
        'pickleball_match',
        'pickleball_court',
        'orginazition',
        'orginazition_type',
        'package',
        'reported_story',
    'reported_ad',
    'reported_highlight',
];

    foreach ($legacyTables as $table) {
        dreamlandTruncateTable($pdo, $table, $verbose);
    }

    if (dreamlandTableExists($pdo, 'post')) {
        $nonReelIds = $pdo->query('SELECT id FROM post WHERE type <> 4')->fetchAll(PDO::FETCH_COLUMN);
        if ($nonReelIds) {
            $idList = implode(',', array_map('intval', $nonReelIds));
            foreach (['post_gallary', 'post_like', 'post_view', 'post_comment', 'reported_post', 'mention_user'] as $rel) {
                if (dreamlandTableExists($pdo, $rel)) {
                    $pdo->exec("DELETE FROM `{$rel}` WHERE post_id IN ({$idList})");
                }
            }
            $pdo->exec('DELETE FROM post WHERE type <> 4');
            if ($verbose) {
                echo '  removed ' . count($nonReelIds) . " non-reel posts\n";
            }
        }
    }

    if (dreamlandTableExists($pdo, 'category')) {
        $deleted = $pdo->exec('DELETE FROM category WHERE type <> 4');
        if ($verbose) {
            echo "  removed {$deleted} non-reel categories\n";
        }
    }

    dreamlandClearTable($pdo, 'audio', $verbose);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    if ($verbose) {
        echo "Done. Dreamland features enabled: reels, live, wallet, appraisal, safety, gamification.\n";
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbPort = getenv('DB_PORT') ?: '3309';
    $dbName = getenv('DB_NAME') ?: 'yii2advanced';
    $dbUser = getenv('DB_USER') ?: 'yii2advanced';
    $dbPass = getenv('DB_PASSWORD') ?: 'secret';

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    dreamlandDisableLegacy($pdo);
}
