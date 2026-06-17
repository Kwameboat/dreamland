<?php
$dir = __DIR__ . '/fixtures';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$path = $dir . '/smoke-reel.mp4';
// Minimal ISO BMFF shell — enough for upload acceptance tests (may not play in browser).
$bytes = "ftypisom\x00\x00\x00\x20isomiso2mp41";
$bytes .= str_repeat("\0", 4096);
file_put_contents($path, $bytes);
echo "Wrote " . filesize($path) . " bytes to $path\n";
