# Start Dreamland admin panel (http://localhost:8081)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$backend = Join-Path $root "backend\sayhi_v1.6_code"

function Test-DreamlandDb {
    $test = php (Join-Path $backend "scripts\test-db-port.php") 2>$null
    return ($test -match "yii2advanced@127.0.0.1:3309/yii2advanced: OK")
}

function Start-DreamlandMysql {
    Write-Host "Starting MySQL on port 3309 (Docker)..."
    Push-Location $backend
    docker compose -f docker-compose.mysql-local.yml up -d
    Pop-Location

    $deadline = (Get-Date).AddMinutes(2)
    while ((Get-Date) -lt $deadline) {
        if (Test-DreamlandDb) { return $true }
        Start-Sleep -Seconds 3
    }
    return $false
}

if (-not (Test-Path (Join-Path $backend "vendor\almasaeed2010\adminlte\dist\css\AdminLTE.min.css"))) {
    Write-Host "Installing PHP dependencies..."
    Push-Location $backend
    composer install --no-interaction --prefer-dist
    Pop-Location
}

if (-not (Test-DreamlandDb)) {
    try {
        if (-not (Start-DreamlandMysql)) {
            Write-Warning "Database not ready on 127.0.0.1:3309. Start Docker Desktop, then run this script again."
        }
    } catch {
        Write-Warning "Could not start MySQL via Docker: $($_.Exception.Message)"
        Write-Warning "Start Docker Desktop, then run: cd backend\sayhi_v1.6_code; docker compose -f docker-compose.mysql-local.yml up -d"
    }
}

if (Test-DreamlandDb) {
    Write-Host "Seeding admin credentials..."
    Push-Location $root
    php (Join-Path $backend "scripts\seed-demo-data.php")
    Pop-Location
}

$existing = netstat -ano | Select-String ":8081" | Select-String "LISTENING"
if ($existing) {
    Write-Host "Admin panel already running on http://localhost:8081"
} else {
    Write-Host "Starting admin panel on http://localhost:8081"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backend'; php -S localhost:8081 -t backend/web backend/web/router.php"
    Start-Sleep -Seconds 2
}

Write-Host ""
Write-Host "Admin panel: http://localhost:8081"
Write-Host "Login:       username admin  |  password demo123"
Write-Host ""
