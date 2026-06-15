# Dreamland inbuilt AI moderation agent (Ghana languages + Google Gemini)
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$agentDir = Join-Path $root "moderation-agent"
$envFile = Join-Path $agentDir ".env"

if (-not (Test-Path $envFile)) {
  Copy-Item (Join-Path $agentDir ".env.example") $envFile -ErrorAction SilentlyContinue
  Write-Host "Created moderation-agent/.env — add your GEMINI_API_KEY from https://aistudio.google.com/apikey"
}

if (-not (Test-Path (Join-Path $agentDir "node_modules"))) {
  Write-Host "Installing moderation-agent dependencies (includes @google/generative-ai)..."
  Push-Location $agentDir
  npm install --no-fund --no-audit 2>&1 | Out-Host
  Pop-Location
}

Write-Host "Starting Dreamland Moderation Agent on http://localhost:4444 (Gemini multimodal + Ghana lexicons)"
Write-Host "Starting moderation queue worker..."
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3309"
$env:DB_USER = "yii2advanced"
$env:DB_PASSWORD = "secret"
$env:DB_NAME = "yii2advanced"
Push-Location $agentDir
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$agentDir'; node worker.js"
node server.js
