param(
  [string]$BaseUrl = "http://localhost",
  [string]$Token
)

function Invoke-Check($Name, $Url, $Token) {
  try {
    $headers = @{ 'Accept' = 'application/json' }
    if ($Token) { $headers['Authorization'] = "Bearer $Token" }
    $resp = Invoke-WebRequest -Uri $Url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop
    if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 300) {
      Write-Host "[PASS] $Name ($($resp.StatusCode))"
    } else {
      throw "HTTP $($resp.StatusCode)"
    }
  }
  catch {
    Write-Host "[FAIL] $Name -> $_" -ForegroundColor Red
    exit 1
  }
}

Invoke-Check "Health (public)" "$BaseUrl/api/health" $Token
Invoke-Check "Admin business" "$BaseUrl/api/admin/business" $Token
Invoke-Check "Admin businesses" "$BaseUrl/api/admin/businesses" $Token
Invoke-Check "Admin statistics" "$BaseUrl/api/admin/statistics" $Token
Invoke-Check "Staff pending" "$BaseUrl/api/admin/staff/requests" $Token
Invoke-Check "Staff archived" "$BaseUrl/api/admin/staff/requests/archived" $Token
Invoke-Check "Tables list" "$BaseUrl/api/admin/tables" $Token
Invoke-Check "Menus list" "$BaseUrl/api/admin/menus" $Token
Invoke-Check "QR web index" "$BaseUrl/qr" $Token

Write-Host "All smoke checks passed." -ForegroundColor Green
