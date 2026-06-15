# Dreamland full localhost walkthrough — one command starts everything
$ErrorActionPreference = "Stop"
$dreamland = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $dreamland "backend\sayhi_v1.6_code"
$compose = Join-Path $backend "docker-compose.mysql-local.yml"

Write-Host ""
Write-Host "=== Dreamland Local Walkthrough ===" -ForegroundColor Magenta
Write-Host ""

# 1. MySQL
Write-Host "[1/5] MySQL (port 3309)..." -ForegroundColor Cyan
$mysqlRunning = docker ps --filter "name=dreamland-mysql" --format "{{.Names}}" 2>$null
if (-not $mysqlRunning) {
  Push-Location $backend
  docker compose -f docker-compose.mysql-local.yml up -d 2>&1 | Out-Host
  Pop-Location
  Write-Host "Waiting for MySQL..."
  $ready = $false
  for ($i = 0; $i -lt 40; $i++) {
    Start-Sleep -Seconds 2
    try {
      docker exec dreamland-mysql mysqladmin ping -h localhost -uyii2advanced -psecret 2>$null | Out-Null
      if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    } catch { }
  }
  if (-not $ready) { Write-Warning "MySQL may still be starting — continuing anyway" }
} else {
  Write-Host "MySQL already running"
}

# 2. Seed data
Write-Host "[2/5] Seeding walkthrough data..." -ForegroundColor Cyan
Push-Location $backend
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3309"
$env:DB_NAME = "yii2advanced"
$env:DB_USER = "yii2advanced"
$env:DB_PASSWORD = "secret"
php scripts/apply-dreamland-v2-migration.php 2>&1 | Out-Null
php scripts/apply-dreamland-v3-migration.php 2>&1 | Out-Null
php scripts/apply-dreamland-moderation-migration.php 2>&1 | Out-Null
php scripts/apply-dreamland-creator-approval-migration.php 2>&1 | Out-Host
php scripts/apply-dreamland-walkthrough-seed.php 2>&1 | Out-Host
php scripts/seed-demo-data.php 2>&1 | Out-Host
Pop-Location

# 3. Start services
Write-Host "[3/5] Starting API (8080), Admin (8081), PWA (3000)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backend'; Write-Host 'Dreamland API http://localhost:8080/v1' -ForegroundColor Green; php -d upload_max_filesize=128M -d post_max_size=128M -d memory_limit=256M -S localhost:8080 -t api/web api/web/router.php"
Start-Sleep -Seconds 1
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backend'; Write-Host 'Dreamland Admin http://localhost:8081' -ForegroundColor Green; php -S localhost:8081 -t backend/web backend/web/router.php"
Start-Sleep -Seconds 1
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$dreamland\web'; Write-Host 'Dreamland PWA http://localhost:3000' -ForegroundColor Green; npx --yes serve -l 3000"

Write-Host "[4/5] Starting Live SFU (4443) + AI Moderation (4444)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$dreamland'; .\start-live-server.ps1"
Start-Sleep -Seconds 1
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$dreamland'; .\start-moderation-agent.ps1"

# 4. Health check
Write-Host "[5/5] Waiting for API..." -ForegroundColor Cyan
Start-Sleep -Seconds 4
try {
  $health = Invoke-RestMethod -Uri "http://localhost:8080/v1/health" -TimeoutSec 8
  Write-Host "API status: $($health.status)" -ForegroundColor Green
} catch {
  Write-Warning "API not ready yet — wait a few seconds and refresh http://localhost:3000"
}

Write-Host ""
Write-Host "=== OPEN IN BROWSER ===" -ForegroundColor Magenta
Write-Host "  PWA (start here):  http://localhost:3000"
Write-Host "  Admin panel:       http://localhost:8081  (admin / demo123)"
Write-Host "  API health:        http://localhost:8080/v1/health"
Write-Host ""
Write-Host "=== WALKTHROUGH STEPS ===" -ForegroundColor Yellow
Write-Host "  1. Register as VIEWER at http://localhost:3000 (Sign in > Create account)"
Write-Host "  2. Register as CREATOR (new account, choose Creator) — or use creator@dreamland.app / demo123"
Write-Host '  3. Creator: Studio - pick genre - upload a FREE reel (appears in feed after ~5s AI scan)'
Write-Host '  4. Viewer: Feed tab - watch reels - unlock premium with credits'
Write-Host '  5. Viewer: Wallet - tap a package - instant demo credits (no Paystack on localhost)'
Write-Host '  6. Creator: Go Live (needs camera) | Viewer: Live tab - Watch'
Write-Host '  7. Admin: Approve paid reels at Dreamland - Appraisal Workspace'
Write-Host ""
Write-Host "Demo accounts: viewer@dreamland.app / demo123 | creator@dreamland.app / demo123"
Write-Host ""
