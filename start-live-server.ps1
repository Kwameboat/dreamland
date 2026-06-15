# Dreamland self-hosted live SFU (WebRTC, no Agora)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$liveDir = Join-Path $root "live-server"

if (-not (Test-Path (Join-Path $liveDir "node_modules"))) {
  Write-Host "Installing live-server dependencies (mediasoup)..."
  Push-Location $liveDir
  npm install 2>&1 | Out-Host
  Pop-Location
}

Write-Host "Starting Dreamland Live Server on http://localhost:4443"
Push-Location $liveDir
node server.js
