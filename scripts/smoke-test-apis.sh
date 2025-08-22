#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://localhost}"
TOKEN="${API_TOKEN:-}"
SMOKE_EMAIL="${SMOKE_EMAIL:-}"
SMOKE_PASSWORD="${SMOKE_PASSWORD:-}"
VERBOSE="${VERBOSE:-}"
HEADER_AUTH=()
if [[ -n "$TOKEN" ]]; then
  HEADER_AUTH=(-H "Authorization: Bearer $TOKEN")
fi

pass() { echo -e "[PASS] $1"; }
fail() { echo -e "[FAIL] $1"; exit 1; }

maybe_login() {
  if [[ -z "$TOKEN" && -n "$SMOKE_EMAIL" && -n "$SMOKE_PASSWORD" ]]; then
    echo "[INFO] No TOKEN; intentando login con SMOKE_EMAIL"
    login_resp=$(curl -sS -X POST -H "Accept: application/json" -H "Content-Type: application/json" \
      -d "{\"email\":\"$SMOKE_EMAIL\",\"password\":\"$SMOKE_PASSWORD\"}" "$BASE_URL/api/login") || true
    if command -v jq >/dev/null 2>&1; then
      TOKEN=$(echo "$login_resp" | jq -r '.token // empty')
    else
      TOKEN=$(echo "$login_resp" | sed -n 's/.*"token" *: *"\([^"]*\)".*/\1/p')
    fi
    if [[ -n "$TOKEN" ]]; then
      HEADER_AUTH=(-H "Authorization: Bearer $TOKEN")
      echo "[INFO] Login OK; token obtenido"
    else
      echo "[WARN] Login fallido o sin token; continuando sin auth"
    fi
  fi
}

check_json_ok() {
  local name="$1"; shift
  local url="$1"; shift
  local code
  if [[ -n "$VERBOSE" ]]; then
    echo "[DEBUG] GET $url"
  fi
  body=$(curl -sS -H "Accept: application/json" "${HEADER_AUTH[@]}" "$url" -w "\n%{http_code}") || true
  code=$(echo "$body" | tail -n1)
  resp=$(echo "$body" | sed '$d')
  if [[ "$code" =~ ^2 ]]; then
    pass "$name ($code)"
  elif [[ "$code" == "401" || "$code" == "403" ]]; then
    echo "[PASS-AUTH] $name requiere auth ($code)"
  elif [[ "$code" == "404" ]]; then
    echo "$resp"
    fail "$name -> HTTP 404 (ruta no encontrada) ($url)"
  elif [[ "$code" =~ ^5 ]]; then
    echo "$resp"
    fail "$name -> HTTP $code (server error) ($url)"
  else
    echo "$resp"
    echo "[WARN] $name -> HTTP $code ($url)"
  fi
}

# Login si hace falta
maybe_login

# Public basics
check_json_ok "Health (public)" "$BASE_URL/api/health"

# Admin
check_json_ok "Admin business" "$BASE_URL/api/admin/business"
check_json_ok "Admin businesses" "$BASE_URL/api/admin/businesses"
check_json_ok "Admin statistics" "$BASE_URL/api/admin/statistics"
check_json_ok "Staff pending" "$BASE_URL/api/admin/staff/requests"
check_json_ok "Staff archived" "$BASE_URL/api/admin/staff/requests/archived"

# Tables/Menus
check_json_ok "Tables list" "$BASE_URL/api/admin/tables"
check_json_ok "Menus list" "$BASE_URL/api/admin/menus"

# QR public
check_json_ok "QR web index" "$BASE_URL/qr"

echo "All smoke checks passed."
