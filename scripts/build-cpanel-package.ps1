# Dreamland cPanel package builder
# Output: dist/dreamland-cpanel.zip (upload via cPanel File Manager or deploy-cpanel-ftp.ps1)

param(
    [string]$Domain = "dreamlandgh.app",
    [switch]$SkipComposer
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$backend = Join-Path $root "backend\sayhi_v1.6_code"
$web = Join-Path $root "web"
$deploy = Join-Path $root "deploy\cpanel"
$dist = Join-Path $root "dist\cpanel-package"
$zipPath = Join-Path $root "dist\dreamland-cpanel.zip"

Write-Host "Dreamland cPanel build — $Domain"

if (-not $SkipComposer) {
    Write-Host 'composer install production...'
    Push-Location $backend
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | Out-Host
    Pop-Location
}

Write-Host "PWA build..."
$env:DREAMLAND_ROOT_DOMAIN = $Domain
$env:DREAMLAND_PWA_URL = "https://$Domain"
$env:DREAMLAND_API_URL = "https://$Domain/api/v1"
Push-Location $root
npm run build:web 2>&1 | Out-Host
Pop-Location

if (Test-Path $dist) { Remove-Item $dist -Recurse -Force }
New-Item -ItemType Directory -Path $dist | Out-Null
$yiiDest = Join-Path $dist "dreamland"
New-Item -ItemType Directory -Path $yiiDest | Out-Null

Write-Host "Copying Yii app..."
$exclude = @('node_modules', '.git', 'tests', 'runtime', 'chat')
robocopy $backend $yiiDest /E /XD $exclude /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null

# deploy/cpanel inside dreamland for entrypoint config paths
$deployDest = Join-Path $yiiDest "deploy\cpanel"
New-Item -ItemType Directory -Path $deployDest -Force | Out-Null
Copy-Item (Join-Path $deploy "config") (Join-Path $deployDest "config") -Recurse -Force
Copy-Item (Join-Path $deploy "env.template") (Join-Path $yiiDest ".env.template") -Force

# Production config from templates
Copy-Item (Join-Path $backend "common\config\main-local.example.php") (Join-Path $yiiDest "common\config\main-local.php") -Force
Copy-Item (Join-Path $backend "common\config\params-supabase.example.php") (Join-Path $yiiDest "common\config\params-local.php") -Force
Copy-Item (Join-Path $backend "backend\config\main-local.example.php") (Join-Path $yiiDest "backend\config\main-local.php") -Force
Copy-Item (Join-Path $backend "api\config\main-local.example.php") (Join-Path $yiiDest "api\config\main-local.php") -Force

# Writable runtime dirs
@(
    "api\runtime", "api\runtime\uploads\user", "api\runtime\uploads\image",
    "backend\runtime", "common\runtime", "backend\web\assets"
) | ForEach-Object {
    $p = Join-Path $yiiDest $_
    New-Item -ItemType Directory -Path $p -Force | Out-Null
}

# .env from template or local supabase env
$envOut = Join-Path $yiiDest ".env"
if (Test-Path (Join-Path $root ".env.supabase")) {
    Copy-Item (Join-Path $root ".env.supabase") $envOut -Force
    Add-Content $envOut "`nDREAMLAND_ROOT_DOMAIN=$Domain"
    Add-Content $envOut "DREAMLAND_PWA_URL=https://$Domain"
    Add-Content $envOut "SITE_URL=https://$Domain"
    Add-Content $envOut "DREAMLAND_ADMIN_URL=https://$Domain/admin"
    Add-Content $envOut "DREAMLAND_API_URL=https://$Domain/api/v1"
    Add-Content $envOut "DREAMLAND_STORAGE=wasabi"
} else {
    Copy-Item (Join-Path $deploy "env.template") $envOut -Force
}

# public_html — PWA + admin + api entrypoints
$pub = Join-Path $dist "public_html"
New-Item -ItemType Directory -Path $pub | Out-Null
robocopy $web $pub /E /XD node_modules /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null

$adminDir = Join-Path $pub "admin"
$apiDir = Join-Path $pub "api"
New-Item -ItemType Directory -Path $adminDir -Force | Out-Null
New-Item -ItemType Directory -Path $apiDir -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $adminDir "assets") -Force | Out-Null

Copy-Item (Join-Path $deploy "entrypoints\admin-index.php") (Join-Path $adminDir "index.php") -Force
Copy-Item (Join-Path $deploy "entrypoints\admin-htaccess") (Join-Path $adminDir ".htaccess") -Force
Copy-Item (Join-Path $deploy "entrypoints\api-index.php") (Join-Path $apiDir "index.php") -Force
Copy-Item (Join-Path $deploy "entrypoints\api-htaccess") (Join-Path $apiDir ".htaccess") -Force
Copy-Item (Join-Path $deploy "entrypoints\api-user.ini") (Join-Path $apiDir ".user.ini") -Force
Copy-Item (Join-Path $deploy "entrypoints\api-user.ini") (Join-Path $adminDir ".user.ini") -Force

# Zip
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
New-Item -ItemType Directory -Path (Split-Path $zipPath) -Force | Out-Null
Compress-Archive -Path (Join-Path $dist "*") -DestinationPath $zipPath -Force

$sizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 1)
Write-Host ""
Write-Host "Done: $zipPath ($sizeMb MB)"
Write-Host "Upload dreamland/ next to public_html/ on cPanel, then extract public_html contents into public_html."
Write-Host "Admin: https://$Domain/admin/site/login"
Write-Host "API:   https://$Domain/api/v1/health"
