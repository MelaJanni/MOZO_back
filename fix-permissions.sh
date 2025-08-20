#!/bin/bash

# ============================================================================
#  Script de saneo de permisos para Laravel en producción
#  Uso: sudo ./fix-permissions.sh [ruta_del_proyecto]
#  Personaliza WEB_USER / DEPLOY_USER si tu entorno difiere.
# ============================================================================
set -euo pipefail

PROJECT_PATH="${1:-/var/www/vhosts/mozoqr.com/httpdocs}"
WEB_USER="www-data"
# Usuario que hace git pull / despliega (ajusta si es otro)
DEPLOY_USER="$(logname 2>/dev/null || echo deploy)"

echo "==> Path: $PROJECT_PATH"
if [ ! -d "$PROJECT_PATH" ]; then
	echo "[ERROR] Ruta inválida" >&2; exit 1
fi

echo "==> Detectando usuario php-fpm (si procede)"
PHP_FPM_USER=$(ps -eo user,comm | grep -E 'php-fpm|php8|php7' | grep -v root | head -n1 | awk '{print $1}' || true)
if [ -n "$PHP_FPM_USER" ]; then
	WEB_USER="$PHP_FPM_USER"
fi
echo "WEB_USER=${WEB_USER}  DEPLOY_USER=${DEPLOY_USER}"

echo "==> Ajustando propietario recursivo (esto puede tardar)"
chown -R ${DEPLOY_USER}:${WEB_USER} "$PROJECT_PATH"

echo "==> Asegurando grupo correcto para lectura web"
find "$PROJECT_PATH" -type d -exec chgrp "$WEB_USER" {} + 2>/dev/null || true
find "$PROJECT_PATH" -type f -exec chgrp "$WEB_USER" {} + 2>/dev/null || true

echo "==> Permisos base (755 dirs / 644 files)"
find "$PROJECT_PATH" -type d -exec chmod 755 {} +
find "$PROJECT_PATH" -type f -exec chmod 644 {} +

echo "==> Directorios escribibles (storage, cache, logs)"
chmod -R 775 "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"

echo "==> Creando subdirectorios críticos si faltan"
mkdir -p "$PROJECT_PATH/storage/framework/cache" \
				 "$PROJECT_PATH/storage/framework/sessions" \
				 "$PROJECT_PATH/storage/framework/views" \
				 "$PROJECT_PATH/storage/logs"

echo "==> Ajustando permisos setgid en directorios para herencia de grupo"
find "$PROJECT_PATH/storage" -type d -exec chmod g+s {} + 2>/dev/null || true
find "$PROJECT_PATH/bootstrap/cache" -type d -exec chmod g+s {} + 2>/dev/null || true

if command -v setfacl >/dev/null 2>&1; then
	echo "==> Aplicando ACL (lectura para web, escritura compartida)"
	setfacl -R -m u:${WEB_USER}:rX -m u:${DEPLOY_USER}:rX "$PROJECT_PATH" || true
	setfacl -R -m u:${WEB_USER}:rwX -m u:${DEPLOY_USER}:rwX "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
	setfacl -dR -m u:${WEB_USER}:rwX -m u:${DEPLOY_USER}:rwX "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache" || true
else
	echo "[Aviso] setfacl no instalado; saltando ACLs avanzadas"
fi

echo "==> Haciendo script ejecutable"
chmod +x "$PROJECT_PATH/fix-permissions.sh" || true

echo "==> Reiniciando servicios"
if systemctl status php8.3-fpm >/dev/null 2>&1; then systemctl restart php8.3-fpm; fi
if systemctl status php8.2-fpm >/dev/null 2>&1; then systemctl restart php8.2-fpm; fi
if systemctl status nginx >/dev/null 2>&1; then systemctl reload nginx; fi

echo "==> Limpiando cachés Laravel"
cd "$PROJECT_PATH"
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

echo "==> Verificación rápida de lectura de la vista problemática"
TEST_FILE="$PROJECT_PATH/resources/views/qr/table-page.blade.php"
if sudo -u "$WEB_USER" test -r "$TEST_FILE"; then
	echo "[OK] El usuario web puede leer la vista."
else
	echo "[ERROR] El usuario web NO puede leer $TEST_FILE" >&2
	ls -l "$TEST_FILE" || true
	getfacl "$TEST_FILE" 2>/dev/null || true
fi

echo "==> Completado"