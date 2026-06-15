# Seed Dreamland demo users + reels + walkthrough genres/packages
$ErrorActionPreference = "Stop"
$backend = Join-Path (Split-Path -Parent $MyInvocation.MyCommand.Path) "backend\sayhi_v1.6_code"
Push-Location $backend
$env:DB_PORT = "3309"
php scripts/apply-dreamland-walkthrough-seed.php
php scripts/seed-demo-data.php
Pop-Location
