#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://localhost}"
TOKEN="${API_TOKEN:-}"
HEADER_AUTH=()
if [[ -n "$TOKEN" ]]; then
  HEADER_AUTH=(-H "Authorization: Bearer $TOKEN")
fi

pass() { echo -e "[PASS] $1"; }
fail() { echo -e "[FAIL] $1"; exit 1; }

check_json_ok() {
  local name="$1"; shift
  local url="$1"; shift
  local code
  code=$(curl -sS -o /dev/null -w "%{http_code}" -H "Accept: application/json" "${HEADER_AUTH[@]}" "$url") || true
  if [[ "$code" =~ ^2 ]]; then pass "$name ($code)"; else fail "$name -> HTTP $code ($url)"; fi
}

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
