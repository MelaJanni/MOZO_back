param(
  [string]$BaseUrl = "http://localhost",
  [string]$Token,
  [string]$Email,
  [string]$Password,
  [switch]$VerboseOutput
)

function Invoke-LoginIfNeeded([string]$BaseUrl, [ref]$TokenRef, [string]$Email, [string]$Password) {
  if (-not $TokenRef.Value -and $Email -and $Password) {
    Write-Host "[INFO] No TOKEN; intentando login con Email" -ForegroundColor Yellow
    $headers = @{ 'Accept' = 'application/json' ; 'Content-Type' = 'application/json' }
    $body = @{ email = $Email; password = $Password } | ConvertTo-Json
    try {
      $resp = Invoke-WebRequest -Uri "$BaseUrl/api/login" -Headers $headers -Method POST -Body $body -UseBasicParsing -TimeoutSec 20 -ErrorAction Stop
      $json = $resp.Content | ConvertFrom-Json
      if ($json.token) { $TokenRef.Value = $json.token; Write-Host "[INFO] Login OK; token obtenido" }
    } catch { Write-Host "[WARN] Login fallido: $_" -ForegroundColor Yellow }
  }
}

function Invoke-Check($Name, $Url, $Token, $VerboseOutput) {
  try {
    $headers = @{ 'Accept' = 'application/json' }
    if ($Token) { $headers['Authorization'] = "Bearer $Token" }
    $resp = Invoke-WebRequest -Uri $Url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop
    if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 300) {
      Write-Host "[PASS] $Name ($($resp.StatusCode))"
    } elseif ($resp.StatusCode -eq 401 -or $resp.StatusCode -eq 403) {
      Write-Host "[PASS-AUTH] $Name requiere auth ($($resp.StatusCode))" -ForegroundColor Yellow
    } elseif ($resp.StatusCode -eq 404) {
      if ($VerboseOutput) { Write-Host $resp.Content }
      throw "HTTP 404 (ruta no encontrada)"
    } elseif ($resp.StatusCode -ge 500) {
      if ($VerboseOutput) { Write-Host $resp.Content }
      throw "HTTP $($resp.StatusCode) (server error)"
    } else {
      if ($VerboseOutput) { Write-Host $resp.Content }
      Write-Host "[WARN] $Name -> HTTP $($resp.StatusCode)" -ForegroundColor Yellow
    }
  }
  catch {
    Write-Host "[FAIL] $Name -> $_" -ForegroundColor Red
    exit 1
  }
}

Invoke-LoginIfNeeded -BaseUrl $BaseUrl -TokenRef ([ref]$Token) -Email $Email -Password $Password

Invoke-Check "Health (public)" "$BaseUrl/api/health" $Token $VerboseOutput
Invoke-Check "Admin business" "$BaseUrl/api/admin/business" $Token $VerboseOutput
Invoke-Check "Admin businesses" "$BaseUrl/api/admin/businesses" $Token $VerboseOutput
Invoke-Check "Admin statistics" "$BaseUrl/api/admin/statistics" $Token $VerboseOutput
Invoke-Check "Staff pending" "$BaseUrl/api/admin/staff/requests" $Token $VerboseOutput
Invoke-Check "Staff archived" "$BaseUrl/api/admin/staff/requests/archived" $Token $VerboseOutput
Invoke-Check "Tables list" "$BaseUrl/api/admin/tables" $Token $VerboseOutput
Invoke-Check "Menus list" "$BaseUrl/api/admin/menus" $Token $VerboseOutput
Invoke-Check "QR web index" "$BaseUrl/qr" $Token $VerboseOutput

Write-Host "All smoke checks passed." -ForegroundColor Green
