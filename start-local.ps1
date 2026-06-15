# Dreamland local dev — start API + PWA
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root "backend\sayhi_v1.6_code"

Write-Host "Starting Dreamland API on http://localhost:8080"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backend'; php -d upload_max_filesize=128M -d post_max_size=128M -d memory_limit=256M -S localhost:8080 -t api/web api/web/router.php"

Write-Host "Starting Dreamland Admin on http://localhost:8081"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backend'; php -S localhost:8081 -t backend/web backend/web/router.php"

Write-Host "Starting Dreamland PWA on http://localhost:3000"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root\web'; npx --yes serve -l 3000"

Write-Host "Starting Dreamland Live SFU on http://localhost:4443"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root'; .\start-live-server.ps1"

Write-Host "Starting Dreamland AI Moderation on http://localhost:4444"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$root'; .\start-moderation-agent.ps1"

Write-Host "Open http://localhost:3000 in your browser."
Write-Host "Full walkthrough: .\start-walkthrough.ps1"
Write-Host "API base: http://localhost:8080/v1 (override via localStorage.dreamland_api)"
Write-Host "Demo data: run .\seed-demo.ps1"
Write-Host "  Admin:   http://localhost:8081  username admin / password demo123"
Write-Host "  PWA:     viewer@dreamland.app or creator@dreamland.app / demo123"
