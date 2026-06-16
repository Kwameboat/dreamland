# Start Dreamland API + Admin using Supabase PostgreSQL (loads .env.supabase)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root "backend\sayhi_v1.6_code"
$envFile = Join-Path $root ".env.supabase"

if (-not (Test-Path $envFile)) {
    Write-Host "Missing .env.supabase — copy .env.supabase.example and fill in your Supabase credentials." -ForegroundColor Red
    exit 1
}

Get-Content $envFile | ForEach-Object {
    $line = $_.Trim()
    if ($line -eq '' -or $line.StartsWith('#')) { return }
    if ($line -match '^([^=]+)=(.*)$') {
        $key = $matches[1].Trim()
        $val = $matches[2].Trim().Trim('"').Trim("'")
        [Environment]::SetEnvironmentVariable($key, $val, 'Process')
    }
}

if (-not (php -m 2>$null | Select-String -Pattern 'pdo_pgsql' -Quiet)) {
    Write-Host "pdo_pgsql is not enabled in PHP." -ForegroundColor Red
    Write-Host "Edit php.ini and uncomment: extension=pdo_pgsql and extension=pgsql"
    Write-Host "Then restart this script."
    exit 1
}

Write-Host "Using Supabase: $($env:DB_HOST):$($env:DB_PORT) / $($env:DB_NAME)" -ForegroundColor Cyan
Write-Host "Starting API  http://localhost:8080/v1/health"
Start-Process powershell -ArgumentList "-NoExit", "-Command", @"
cd '$backend'
`$env:DB_DRIVER='$($env:DB_DRIVER)'
`$env:DB_HOST='$($env:DB_HOST)'
`$env:DB_PORT='$($env:DB_PORT)'
`$env:DB_NAME='$($env:DB_NAME)'
`$env:DB_USER='$($env:DB_USER)'
`$env:DB_PASSWORD='$($env:DB_PASSWORD)'
php -d upload_max_filesize=128M -d post_max_size=128M -d memory_limit=256M -S localhost:8080 -t api/web api/web/router.php
"@

Write-Host "Starting Admin http://localhost:8081"
Start-Process powershell -ArgumentList "-NoExit", "-Command", @"
cd '$backend'
`$env:DB_DRIVER='$($env:DB_DRIVER)'
`$env:DB_HOST='$($env:DB_HOST)'
`$env:DB_PORT='$($env:DB_PORT)'
`$env:DB_NAME='$($env:DB_NAME)'
`$env:DB_USER='$($env:DB_USER)'
`$env:DB_PASSWORD='$($env:DB_PASSWORD)'
php -S localhost:8081 -t backend/web backend/web/router.php
"@

Start-Sleep -Seconds 3
try {
    $health = Invoke-RestMethod -Uri "http://localhost:8080/v1/health" -TimeoutSec 10
    if ($health.data.checks.database) {
        Write-Host "Database connected to Supabase!" -ForegroundColor Green
    } else {
        Write-Host "API up but database check failed — see API window for errors." -ForegroundColor Yellow
    }
} catch {
    Write-Host "Health check pending — open http://localhost:8080/v1/health in a few seconds." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "PWA:   http://localhost:3000  (run start-local.ps1 for PWA, or npx serve web -l 3000)"
Write-Host "Admin: http://localhost:8081  admin / demo123"
Write-Host "Viewer: viewer@dreamland.app / demo123"
