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
      if ($json.token) {
        $TokenRef.Value = $json.token
        Write-Host "[INFO] Login OK; token obtenido (token)"
      } elseif ($json.access_token) {
        $TokenRef.Value = $json.access_token
        Write-Host "[INFO] Login OK; token obtenido (access_token)"
      }
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

function Get-Json($Url, $Token) {
  $headers = @{ 'Accept' = 'application/json' }
  if ($Token) { $headers['Authorization'] = "Bearer $Token" }
  $resp = Invoke-WebRequest -Uri $Url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 20 -ErrorAction Stop
  return ($resp.Content | ConvertFrom-Json)
}

function Post-Json($Url, $Token, $BodyObj) {
  $headers = @{ 'Accept' = 'application/json'; 'Content-Type' = 'application/json' }
  if ($Token) { $headers['Authorization'] = "Bearer $Token" }
  $body = ($BodyObj | ConvertTo-Json -Depth 5)
  $resp = Invoke-WebRequest -Uri $Url -Headers $headers -Method POST -Body $body -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
  return ($resp.Content | ConvertFrom-Json)
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

# Waiter endpoints checks (diagnóstico)
try {
  # 1) IPs bloqueadas (debe soportar cuando tabla no existe)
  $json = Get-Json "$BaseUrl/api/waiter/ip/blocked?active_only=true" $Token
  if ($null -ne $json.success -and $json.success -eq $true) {
    Write-Host "[PASS] Waiter IP blocked (active_only)" -ForegroundColor Green
  } else {
    Write-Host "[WARN] Waiter IP blocked -> respuesta inesperada" -ForegroundColor Yellow
    if ($VerboseOutput) { $json | ConvertTo-Json -Depth 5 }
  }

  # 2) Activar/desactivar múltiples mesas si hay disponibles
  $avail = Get-Json "$BaseUrl/api/waiter/tables/available" $Token
  if ($avail.success -and $avail.count -gt 0) {
    $ids = @()
    foreach ($t in $avail.available_tables) { if ($ids.Count -lt 2) { $ids += $t.id } }
    $payload = @{ table_ids = $ids }
    $act = Post-Json "$BaseUrl/api/waiter/tables/activate/multiple" $Token $payload
    if ($act.success) {
      Write-Host "[PASS] Waiter activate multiple (" $act.summary.activated ")" -ForegroundColor Green
      # Revertir
      $deact = Post-Json "$BaseUrl/api/waiter/tables/deactivate/multiple" $Token $payload
      if ($deact.success) {
        Write-Host "[PASS] Waiter deactivate multiple (" $deact.summary.deactivated ")" -ForegroundColor Green
      } else {
        Write-Host "[WARN] Waiter deactivate multiple -> respuesta inesperada" -ForegroundColor Yellow
      }
    } else {
      Write-Host "[WARN] Waiter activate multiple -> respuesta inesperada" -ForegroundColor Yellow
      if ($VerboseOutput) { $act | ConvertTo-Json -Depth 5 }
    }
  } else {
    Write-Host "[INFO] No hay mesas disponibles para activar (skip)" -ForegroundColor Yellow
  }
} catch {
  Write-Host "[WARN] Waiter diagnostics error: $_" -ForegroundColor Yellow
}

Write-Host "All smoke checks passed." -ForegroundColor Green
