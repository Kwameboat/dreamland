# Dreamland smoke test - local or CI
# Usage: .\scripts\smoke-test.ps1

$ErrorActionPreference = 'Stop'
$passed = 0
$failed = 0
$warnings = 0

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Url,
        [int[]]$ExpectStatus = @(200),
        [switch]$Optional
    )
    try {
        $r = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 15
        if ($ExpectStatus -contains $r.StatusCode) {
            Write-Host "[PASS] $Name ($($r.StatusCode)) $Url" -ForegroundColor Green
            $script:passed++
            return $r
        }
        Write-Host "[FAIL] $Name - expected $($ExpectStatus -join '|'), got $($r.StatusCode)" -ForegroundColor Red
        $script:failed++
        return $null
    } catch {
        if ($Optional) {
            Write-Host "[WARN] $Name - $($_.Exception.Message)" -ForegroundColor Yellow
            $script:warnings++
            return $null
        }
        Write-Host "[FAIL] $Name - $($_.Exception.Message)" -ForegroundColor Red
        $script:failed++
        return $null
    }
}

$apiBase = if ($env:DL_API_BASE) { $env:DL_API_BASE.TrimEnd('/') } else { 'http://localhost:8080/v1' }
$pwaUrl = if ($env:DL_PWA_URL) { $env:DL_PWA_URL } else { 'http://localhost:3000' }
$adminUrl = if ($env:DL_ADMIN_URL) { $env:DL_ADMIN_URL } else { 'http://localhost:8081' }

Write-Host ""
Write-Host "=== Dreamland smoke test ===" -ForegroundColor Cyan
Write-Host "API:   $apiBase"
Write-Host "PWA:   $pwaUrl"
Write-Host "Admin: $adminUrl"
Write-Host ""

Test-Endpoint -Name 'PWA index' -Url $pwaUrl | Out-Null
Test-Endpoint -Name 'Admin panel' -Url $adminUrl -Optional | Out-Null

$healthResp = Test-Endpoint -Name 'API health' -Url "$apiBase/health"
if ($healthResp) {
    try {
        $health = $healthResp.Content | ConvertFrom-Json
        $payload = if ($health.data) { $health.data } else { $health }
        $statusOk = ($payload.status -eq 'ok') -or ($health.status -eq 'ok')
        if ($statusOk) {
            Write-Host "[PASS] Health status ok" -ForegroundColor Green
            $passed++
        } else {
            Write-Host "[FAIL] Health status: $($payload.status)" -ForegroundColor Red
            $failed++
        }
        $dbOk = $payload.checks.database -eq $true
        if ($dbOk) {
            Write-Host "[PASS] Database connected" -ForegroundColor Green
            $passed++
        } else {
            Write-Host "[FAIL] Database not connected" -ForegroundColor Red
            $failed++
        }
        if ($payload.checks.moderation_agent -eq $false) {
            Write-Host "[WARN] Moderation agent offline (optional locally)" -ForegroundColor Yellow
            $warnings++
        }
        if ($payload.checks.live_server -eq $false) {
            Write-Host "[WARN] Live server offline (optional locally)" -ForegroundColor Yellow
            $warnings++
        }
    } catch {
        Write-Host "[FAIL] Could not parse health JSON" -ForegroundColor Red
        $failed++
    }
}

try {
    $loginBody = @{
        email = 'viewer@dreamland.app'
        password = 'demo123'
        device_type = 3
        login_ip = '127.0.0.1'
    } | ConvertTo-Json
    $login = Invoke-RestMethod -Uri "$apiBase/users/login" -Method Post -Body $loginBody -ContentType 'application/json' -TimeoutSec 15
    $loginData = if ($login.data) { $login.data } else { $login }
    if ($loginData.user -or $loginData.access_token -or $loginData.token -or $login.user) {
        Write-Host "[PASS] Viewer demo login" -ForegroundColor Green
        $passed++
    } else {
        Write-Host "[WARN] Login responded but no user/token (run seed-demo-data.php)" -ForegroundColor Yellow
        $warnings++
    }
} catch {
    Write-Host "[WARN] Viewer login - $($_.Exception.Message)" -ForegroundColor Yellow
    $warnings++
}

$uploadBase = $apiBase -replace '/v1$', ''
$uploadResp = Test-Endpoint -Name 'Uploads route' -Url "$uploadBase/uploads/image/__smoke_test__.mp4" -ExpectStatus @(404) -Optional
if ($uploadResp) {
    Write-Host "[PASS] Uploads routing returns 404 for missing file" -ForegroundColor Green
    $passed++
}

Write-Host ""
Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "Passed:   $passed"
Write-Host "Failed:   $failed"
Write-Host "Warnings: $warnings"

if ($failed -gt 0) {
    Write-Host ""
    Write-Host "Smoke test FAILED" -ForegroundColor Red
    exit 1
}
Write-Host ""
Write-Host "Smoke test PASSED" -ForegroundColor Green
exit 0
