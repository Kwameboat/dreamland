# Dreamland one-command bootstrap (Windows)
$dreamland = $PSScriptRoot
$backend = Join-Path $dreamland "backend\sayhi_v1.6_code"

Write-Host "Installing PHP dependencies (AdminLTE, Yii debug, etc.)..."
Push-Location $backend
composer install --no-interaction --prefer-dist 2>&1 | Out-Host
Pop-Location

Write-Host "Applying Dreamland migrations..."
php (Join-Path $backend "scripts\apply-dreamland-v2-migration.php")
php (Join-Path $backend "scripts\apply-dreamland-v3-migration.php")
Write-Host "Disabling legacy SayHi features and cleaning irrelevant data..."
php (Join-Path $backend "scripts\dreamland-disable-legacy.php")
Write-Host "Applying Dreamland push notification schema..."
php (Join-Path $backend "scripts\apply-dreamland-push-migration.php")
Write-Host "Applying Dreamland audience targeting schema..."
php (Join-Path $backend "scripts\apply-dreamland-audience-migration.php")
Write-Host "Applying Dreamland creator approval schema..."
php (Join-Path $backend "scripts\apply-dreamland-creator-approval-migration.php")
Write-Host "Seeding Ghana AI moderation keywords..."
php (Join-Path $backend "scripts\apply-dreamland-moderation-migration.php")
Write-Host "Seeding walkthrough genres + credit packages..."
php (Join-Path $backend "scripts\apply-dreamland-walkthrough-seed.php")
if (Test-Path (Join-Path $backend "yii")) {
  Push-Location $backend
  php yii migrate --migrationPath=@console/migrations --interactive=0 2>$null
  Pop-Location
}
Write-Host "Installing moderation-agent dependencies..."
Push-Location (Join-Path $PSScriptRoot "moderation-agent")
npm install --no-fund --no-audit 2>&1 | Out-Host
Pop-Location
Write-Host "Installing live-server dependencies..."
Push-Location (Join-Path $PSScriptRoot "live-server")
npm install --no-fund --no-audit 2>&1 | Out-Host
Pop-Location

Write-Host "Bootstrap complete."
Write-Host "Full walkthrough: .\start-walkthrough.ps1"
Write-Host "Start API: cd backend && php -S localhost:8080 -t api/web api/web/router.php"
Write-Host "Start Moderation Agent: .\start-moderation-agent.ps1"
Write-Host "Start Live SFU: .\start-live-server.ps1"
Write-Host "Start PWA: cd web && npx --yes serve -l 3000"
