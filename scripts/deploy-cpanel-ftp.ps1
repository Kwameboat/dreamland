# Upload dreamland-cpanel.zip via FTP (credentials NOT stored in repo).
#
# 1. Copy deploy/cpanel/ftp-credentials.example.json → deploy/cpanel/ftp-credentials.local.json
# 2. Fill host, user, password
# 3. Run: .\scripts\deploy-cpanel-ftp.ps1

param(
    [string]$CredentialsFile = "",
    [string]$ZipPath = ""
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
if (-not $ZipPath) { $ZipPath = Join-Path $root "dist\dreamland-cpanel.zip" }
if (-not $CredentialsFile) {
    $CredentialsFile = Join-Path $root "deploy\cpanel\ftp-credentials.local.json"
}

if (-not (Test-Path $ZipPath)) {
    Write-Error "Run scripts\build-cpanel-package.ps1 first. Missing: $ZipPath"
}
if (-not (Test-Path $CredentialsFile)) {
    Write-Error "Create $CredentialsFile from ftp-credentials.example.json"
}

$cred = Get-Content $CredentialsFile -Raw | ConvertFrom-Json
$hostName = $cred.host
$user = $cred.user
$pass = $cred.password

Write-Host "Uploading to ftp://$hostName/ ..."
$ftpUri = "ftp://$hostName/dreamland-cpanel.zip"
$request = [System.Net.FtpWebRequest]::Create($ftpUri)
$request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
$request.Credentials = New-Object System.Net.NetworkCredential($user, $pass)
$request.UseBinary = $true
$request.UsePassive = $true
$bytes = [System.IO.File]::ReadAllBytes($ZipPath)
$request.ContentLength = $bytes.Length
$stream = $request.GetRequestStream()
$stream.Write($bytes, 0, $bytes.Length)
$stream.Close()
$response = $request.GetResponse()
Write-Host "Uploaded: $($response.StatusDescription)"
$response.Close()
Write-Host ""
Write-Host "Next in cPanel File Manager:"
Write-Host "  1. Extract dreamland-cpanel.zip in /home/$user/"
Write-Host "  2. Move dreamland/ folder to sit beside public_html/"
Write-Host "  3. Merge public_html/ contents into your live public_html/"
Write-Host "  4. Edit dreamland/.env — add Wasabi keys + COOKIE_VALIDATION_KEY"
Write-Host "  5. cPanel → Select PHP Version → 8.2 for admin + api folders"
