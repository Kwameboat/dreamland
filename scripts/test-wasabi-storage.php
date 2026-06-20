<?php
/**
 * Quick Wasabi connectivity check using WASABI_* env vars.
 *
 *   cd dreamland
 *   php scripts/test-wasabi-storage.php
 *
 * Loads dreamland/.env.supabase if present (same vars as cPanel / API server).
 */

$root = dirname(__DIR__);
$envFile = $root . '/.env.supabase';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

require $root . '/backend/sayhi_v1.6_code/vendor/autoload.php';

use Aws\S3\S3Client;

$bucket = trim((string) (getenv('WASABI_BUCKET') ?: ''));
$region = trim((string) (getenv('WASABI_REGION') ?: 'us-east-1'));
$key = trim((string) (getenv('WASABI_ACCESS_KEY') ?: ''));
$secret = trim((string) (getenv('WASABI_SECRET_KEY') ?: ''));
$endpoint = rtrim(trim((string) (getenv('WASABI_ENDPOINT') ?: '')), '/');
if ($endpoint === '') {
    $endpoint = 'https://s3.' . $region . '.wasabisys.com';
}

if ($bucket === '' || $key === '' || $secret === '') {
    fwrite(STDERR, "Missing WASABI_BUCKET, WASABI_ACCESS_KEY, or WASABI_SECRET_KEY.\n");
    exit(1);
}

echo "Endpoint: {$endpoint}\n";
echo "Bucket:   {$bucket}\n";
echo "Region:   {$region}\n";

try {
    $client = new S3Client([
        'version' => 'latest',
        'region' => $region,
        'endpoint' => $endpoint,
        'use_path_style_endpoint' => true,
        'credentials' => ['key' => $key, 'secret' => $secret],
    ]);
    $client->headBucket(['Bucket' => $bucket]);
    echo "OK: bucket reachable.\n";
    echo "Public image URL base: {$endpoint}/{$bucket}/image\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
